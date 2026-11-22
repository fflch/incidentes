# Incidentes

## API de entrada de alertas

Os monitores externos enviam alertas com `POST /api/alerts`. Cada alerta novo:

- entra como **pendente**;
- passa pelas **regras de atribuição automática** (por nome ou tipo do serviço);
- é **agrupado** com alertas similares já existentes do mesmo serviço.

### Configuração

Defina a chave no `.env` do sistema:

```env
# Gere com: openssl rand -hex 32
ALERTS_API_KEY=troque-por-uma-chave-longa-e-aleatoria
```

Sem `ALERTS_API_KEY` configurada, a API recusa todas as requisições.

### Autenticação

Envie a chave em um destes cabeçalhos:

- `X-API-Key: <chave>`
- `Authorization: Bearer <chave>`

Chave ausente ou errada recebe `401`.

### Campos (JSON)

| Campo             | Obrigatório | Descrição |
|-------------------|-------------|-----------|
| `type`            | sim         | Tipo do serviço (ex.: `drupal`, `laravel`, `docker`). Até 100 caracteres. |
| `id_externo`      | sim         | Identificador do alerta na origem. Até 255 caracteres. |
| `service_name`    | não         | Nome do serviço (ex.: a URL do site). Até 255 caracteres. |
| `data_ocorrencia` | não         | Quando o erro aconteceu. Sem fuso (`2026-11-20 10:15:00`) é lido como horário de Brasília; com fuso (`2026-11-20T13:15:00+00:00`) é convertido. Se omitido, usa o horário do recebimento. |
| `mensagem`        | sim         | Texto do erro. |

O par `type` + `id_externo` identifica o alerta. Reenviar o mesmo par **não duplica nem altera** o alerta existente, como o antigo `INSERT IGNORE`.

### Respostas

| Status | Quando | Corpo |
|--------|--------|-------|
| `201`  | Alerta criado | `{"created": true, "id": 42, "parent_id": null, "atribuido": 3}` |
| `200`  | Alerta já existia | `{"created": false, "id": 42, ...}` |
| `401`  | Chave ausente ou inválida | `{"message": "Chave de API inválida ou ausente."}` |
| `422`  | Campos inválidos | `{"message": "...", "errors": {...}}` |

`parent_id` preenchido indica que o alerta foi agrupado sob outro mais antigo. `atribuido` é o id do responsável definido por uma regra (ou `null`).

### Exemplo com curl

```bash
curl -X POST https://incidentes.exemplo.usp.br/api/alerts \
  -H "X-API-Key: $ALERTS_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "laravel",
    "id_externo": "9f86d081884c7d659a2feaa0c55ad015",
    "service_name": "portal.fflch.usp.br",
    "data_ocorrencia": "2026-11-20 10:15:00",
    "mensagem": "Erro de App (Laravel 500):\nSQLSTATE[HY000] [2002] Connection refused"
  }'
```

### Exemplo em Python (monitor do Swarm)

Troque o `INSERT IGNORE` direto no banco por uma chamada à API. O monitor não precisa mais de acesso ao MariaDB: basta a URL e a chave.

```python
import os
import requests

ALERTS_API_URL = os.getenv("ALERTS_API_URL", "https://incidentes.exemplo.usp.br/api/alerts")
ALERTS_API_KEY = os.getenv("ALERTS_API_KEY", "")


def send_alert(type_, id_externo, service_name, data_ocorrencia, mensagem):
    """Envia um alerta para o sistema de incidentes. Retorna True se foi criado agora."""
    try:
        resp = requests.post(
            ALERTS_API_URL,
            headers={"X-API-Key": ALERTS_API_KEY, "Accept": "application/json"},
            json={
                "type": type_,
                "id_externo": id_externo,
                "service_name": service_name,
                # datetime sem fuso, já em horário de Brasília (como adjust_datetime() devolve)
                "data_ocorrencia": data_ocorrencia.strftime("%Y-%m-%d %H:%M:%S"),
                "mensagem": mensagem,
            },
            timeout=10,
        )
        resp.raise_for_status()
        return resp.json().get("created", False)
    except requests.RequestException as e:
        print(f"Erro ao enviar alerta {type_}/{id_externo}: {e}")
        return False
```

Em cada função de coleta, o bloco:

```python
cursor.execute(insert_query, (
    resolve_type(type_map, stack_name),
    task_id,
    full_service_name,
    data_ocorrencia,
    mensagem_completa
))
conn.commit()
```

passa a ser:

```python
send_alert(
    resolve_type(type_map, stack_name),
    task_id,
    full_service_name,
    data_ocorrencia,
    mensagem_completa,
)
```

Depois disso, `get_db_connection()`, `conn`, `cursor`, `insert_query`, o pacote `mysql-connector-python` e as variáveis `DB_*` podem ser removidos do monitor.

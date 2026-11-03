<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alerta Atribuído</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e1e4e8;
        }
        .header {
            background-color: #0d6efd;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .content {
            padding: 24px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #fff3cd;
            color: #664d03;
        }
        .info-table {
            width: 100%;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .info-table th, .info-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        .info-table th {
            color: #6c757d;
            width: 35%;
            font-weight: 600;
        }
        .error-box {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 12px;
            margin-top: 18px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 250px;
            overflow-y: auto;
        }
        .btn {
            display: inline-block;
            background-color: #0d6efd;
            color: #ffffff !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 24px;
            text-align: center;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestão de Alertas</h1>
        </div>
        <div class="content">
            <p style="font-size: 16px; margin-top: 0;">
                @if($tipoDestinatario === 'responsavel')
                    Olá, <strong>{{ $alert->atribuidoUser?->name }}</strong>! Um novo incidente foi atribuído a você:
                @else
                    Novo incidente registrado e atribuído no sistema:
                @endif
            </p>

            <div style="margin-bottom: 16px;">
                <span class="badge">{{ ucfirst($alert->status_resolucao) }}</span>
            </div>

            <table class="info-table">
                <tr>
                    <th>Serviço:</th>
                    <td><strong>{{ $alert->service_name ?? '—' }}</strong></td>
                </tr>
                <tr>
                    <th>Tipo:</th>
                    <td>{{ $alert->type }}</td>
                </tr>
                <tr>
                    <th>ID Externo:</th>
                    <td>{{ $alert->id_externo }}</td>
                </tr>
                <tr>
                    <th>Ocorrência:</th>
                    <td>{{ $alert->data_ocorrencia?->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <th>Responsável:</th>
                    <td>{{ $alert->atribuidoUser?->name ?? 'Não atribuído' }}</td>
                </tr>
            </table>

            <div style="margin-top: 20px;">
                <strong style="font-size: 13px; color: #495057;">Mensagem do Erro:</strong>
                <div class="error-box">{{ Str::limit($alert->mensagem, 800) }}</div>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/alerts/' . $alert->id) }}" class="btn">Visualizar Alerta no Sistema</a>
            </div>
        </div>
        <div class="footer">
            Este é um e-mail automático.
        </div>
    </div>
</body>
</html>


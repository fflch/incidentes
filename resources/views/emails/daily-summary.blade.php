<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo Diário de Pendências</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e1e4e8;
        }
        .header {
            background-color: #212529;
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
        .stats-grid {
            display: flex;
            gap: 12px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            flex: 1;
        }
        .stat-card .num {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }
        .stat-card .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
        }
        table.alerts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        table.alerts-table th, table.alerts-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        table.alerts-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            background-color: #0d6efd;
            color: #ffffff !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
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
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            background-color: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Resumo Diário de Pendências</h1>
            <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.85;">{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</p>
        </div>
        <div class="content">
            @if($destinatario)
                <p style="font-size: 15px; margin-top: 0;">
                    Olá, <strong>{{ $destinatario->name }}</strong>! Você possui <strong>{{ $alerts->count() }}</strong> {{ $alerts->count() === 1 ? 'alerta pendente atribuído' : 'alertas pendentes atribuídos' }}:
                </p>
            @else
                <p style="font-size: 15px; margin-top: 0;">
                    Olá! Este é o relatório diário de incidentes em aberto:
                </p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="num">{{ $totalGeral }}</div>
                        <div class="label">Total Pendentes</div>
                    </div>
                    <div class="stat-card">
                        <div class="num" style="color: #dc3545;">{{ $totalSemAtribuicao }}</div>
                        <div class="label">Não Atribuídos</div>
                    </div>
                    <div class="stat-card">
                        <div class="num" style="color: #198754;">{{ $totalGeral - $totalSemAtribuicao }}</div>
                        <div class="label">Com Responsável</div>
                    </div>
                </div>
            @endif

            @if($alerts->count() > 0)
                <table class="alerts-table">
                    <thead>
                        <tr>
                            <th>Serviço</th>
                            <th>Data Ocorrência</th>
                            @if(!$destinatario)
                                <th>Responsável</th>
                            @endif
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr>
                                <td>
                                    <strong>{{ $alert->service_name ?? $alert->type }}</strong>
                                    <div style="font-size: 11px; color: #6c757d;">{{ $alert->id_externo }}</div>
                                </td>
                                <td>{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</td>
                                @if(!$destinatario)
                                    <td>
                                        @if($alert->atribuidoUser)
                                            {{ $alert->atribuidoUser->name }}
                                        @else
                                            <span style="color: #dc3545; font-weight: bold;">Não atribuído</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <a href="{{ url('/alerts/' . $alert->id) }}" style="color: #0d6efd; text-decoration: none; font-weight: 500;">Ver &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="padding: 20px; background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 6px; color: #2e7d32; text-align: center; margin-top: 15px;">
                    🎉 <strong>Excelente! Não há nenhuma pendência em aberto.</strong>
                </div>
            @endif

            <div style="text-align: center; margin-top: 24px;">
                @if($destinatario)
                    <a href="{{ url('/meus-alertas') }}" class="btn">Acessar Meus Alertas</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="btn">Acessar Painel</a>
                @endif
            </div>
        </div>
        <div class="footer">
            Este é um e-mail diário automático.
        </div>
    </div>
</body>
</html>


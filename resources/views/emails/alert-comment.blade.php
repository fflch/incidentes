<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo comentário no alerta</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; color: #333333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e1e4e8;">
        <div style="background-color: #0d6efd; color: #ffffff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 22px; font-weight: 600;">Gestão de Alertas</h1>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 16px; margin-top: 0;">
                Olá, <strong>{{ $alert->atribuidoUser?->name }}</strong>!
                <strong>{{ $comment->user?->name ?? 'Alguém' }}</strong> comentou em um alerta atribuído a você:
            </p>

            <table style="width: 100%; border-collapse: collapse; margin-top: 8px;">
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; width: 35%; font-size: 14px; border-bottom: 1px solid #e9ecef;">Serviço:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;"><strong>{{ $alert->service_name ?? $alert->type }}</strong></td>
                </tr>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Ocorrência:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">{{ $alert->data_ocorrencia?->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Status:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">{{ ucfirst($alert->status_resolucao) }}</td>
                </tr>
            </table>

            <div style="margin-top: 20px; background-color: #f8f9fa; border-left: 4px solid #0d6efd; padding: 12px 16px; font-size: 14px; white-space: pre-wrap;">{{ $comment->comentario }}</div>
            <div style="font-size: 12px; color: #6c757d; margin-top: 6px;">
                {{ $comment->user?->name ?? 'Usuário' }} · {{ $comment->created_at?->format('d/m/Y H:i') }}
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/alerts/' . $alert->id) }}#comentarios" style="display: inline-block; background-color: #0d6efd; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; margin-top: 24px;">Ver alerta e responder</a>
            </div>
        </div>
        <div style="background-color: #f8f9fa; padding: 16px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef;">
            Este é um e-mail automático.
        </div>
    </div>
</body>
</html>

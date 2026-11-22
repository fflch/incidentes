<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alerta resolvido</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; color: #333333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e1e4e8;">
        <div style="background-color: #198754; color: #ffffff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 22px; font-weight: 600;">Alerta resolvido</h1>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 16px; margin-top: 0;">
                O alerta <strong>{{ $alert->service_name ?? $alert->type }}</strong> (#{{ $alert->id }}) foi resolvido
                por <strong>{{ $alert->resolvidoPor?->name ?? 'usuário desconhecido' }}</strong>.
            </p>

            <table style="width: 100%; border-collapse: collapse; margin-top: 8px;">
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; width: 40%; font-size: 14px; border-bottom: 1px solid #e9ecef;">Tempo em aberto:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;"><strong>{{ $tempoAberto ?? '—' }}</strong></td>
                </tr>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Repetições:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">
                        <strong>{{ $repeticoes }}</strong>
                        ({{ $totalOcorrencias }} {{ $totalOcorrencias === 1 ? 'ocorrência' : 'ocorrências' }} no total)
                    </td>
                </tr>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Primeira ocorrência:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">{{ $primeiraOcorrencia?->format('d/m/Y H:i') }}</td>
                </tr>
                @if ($repeticoes > 0)
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Última ocorrência:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">{{ $ultimaOcorrencia?->format('d/m/Y H:i') }}</td>
                </tr>
                @endif
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Resolvido em:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">{{ $alert->dataResolucao()?->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; color: #6c757d; font-size: 14px; border-bottom: 1px solid #e9ecef;">Responsável:</th>
                    <td style="padding: 8px 12px; font-size: 14px; border-bottom: 1px solid #e9ecef;">{{ $alert->atribuidoUser?->name ?? 'Não atribuído' }}</td>
                </tr>
            </table>

            @if (filled($alert->comentario))
            <div style="font-size: 13px; color: #6c757d; margin-top: 20px;">Comentário de resolução:</div>
            <div style="margin-top: 6px; background-color: #f8f9fa; border-left: 4px solid #198754; padding: 12px 16px; font-size: 14px; white-space: pre-wrap;">{{ $alert->comentario }}</div>
            @endif

            <div style="text-align: center;">
                <a href="{{ url('/alerts/' . $alert->id) }}" style="display: inline-block; background-color: #198754; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; margin-top: 24px;">Ver alerta</a>
            </div>
        </div>
        <div style="background-color: #f8f9fa; padding: 16px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef;">
            Este é um e-mail automático.
        </div>
    </div>
</body>
</html>

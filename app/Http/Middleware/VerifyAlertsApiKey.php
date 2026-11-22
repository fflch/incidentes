<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticação simples da API de alertas: a requisição deve trazer a chave
 * definida em ALERTS_API_KEY no cabeçalho "X-API-Key" ou como
 * "Authorization: Bearer <chave>". Sem chave configurada, a API fica fechada.
 */
class VerifyAlertsApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.alerts_api.key');
        $given = (string) ($request->header('X-API-Key') ?: $request->bearerToken());

        if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
            return response()->json(['message' => 'Chave de API inválida ou ausente.'], 401);
        }

        return $next($request);
    }
}

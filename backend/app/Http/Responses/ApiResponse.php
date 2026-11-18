<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Formato ÚNICO de resposta da API.
 *
 * Sucesso: { "success": true,  "message": "...|null", "data": ..., "meta": {...}? }
 * Erro:    { "success": false, "message": "...", "code": "validation_error", "errors": {...}?, "detail": "..."? }
 */
final class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $body = ['success' => true, 'message' => $message, 'data' => $data];

        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function created(mixed $data, ?string $message = null): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * @param  array<string, string[]>  $errors  erros por campo (validação)
     * @param  string|null  $detail  detalhe técnico (ex.: mensagem da exceção)
     */
    public static function error(string $message, ErrorCode $code, int $status, array $errors = [], ?string $detail = null): JsonResponse
    {
        $body = ['success' => false, 'message' => $message, 'code' => $code->value];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }
        if ($detail !== null) {
            $body['detail'] = $detail;
        }

        return response()->json($body, $status);
    }
}

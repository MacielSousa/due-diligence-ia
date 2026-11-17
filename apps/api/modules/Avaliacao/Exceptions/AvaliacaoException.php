<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

final class AvaliacaoException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->getCode() ?: 422);
    }
}

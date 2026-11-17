<?php

declare(strict_types=1);

namespace Modules\Auditoria\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auditoria\Contracts\AuditoriaLlm;

class AuditoriaController
{
    public function avaliacao(int $avaliacao, AuditoriaLlm $auditoria): JsonResponse
    {
        return response()->json(['data' => $auditoria->daAvaliacao($avaliacao)]);
    }
}

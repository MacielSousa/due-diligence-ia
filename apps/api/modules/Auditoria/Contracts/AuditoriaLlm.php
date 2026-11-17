<?php

declare(strict_types=1);

namespace Modules\Auditoria\Contracts;

interface AuditoriaLlm
{
    /** @param array<string, mixed> $registro */
    public function registrar(array $registro): void;

    /** @return list<array<string, mixed>> */
    public function daAvaliacao(int $avaliacaoId): array;
}

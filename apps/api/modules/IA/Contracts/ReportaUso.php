<?php

declare(strict_types=1);

namespace Modules\IA\Contracts;

/** Clientes de LLM que conseguem informar modelo e tokens da última chamada (para auditoria). */
interface ReportaUso
{
    /** @return array{modelo: string, tokens_entrada: int, tokens_saida: int}|null */
    public function ultimoUso(): ?array;
}

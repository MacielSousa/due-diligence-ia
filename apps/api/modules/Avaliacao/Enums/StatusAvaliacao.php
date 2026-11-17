<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Enums;

enum StatusAvaliacao: string
{
    case Pendente = 'pendente';
    case Processando = 'processando';
    case Concluida = 'concluida';
    case Falhou = 'falhou';

    public function emAndamento(): bool
    {
        return in_array($this, [self::Pendente, self::Processando], true);
    }
}

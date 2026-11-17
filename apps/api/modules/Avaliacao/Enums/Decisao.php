<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Enums;

enum Decisao: string
{
    case Aprovado = 'aprovado';
    case AprovadoComRessalvas = 'aprovado_com_ressalvas';
    case Reprovado = 'reprovado';
}

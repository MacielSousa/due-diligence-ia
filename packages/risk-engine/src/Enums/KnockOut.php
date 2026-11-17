<?php

declare(strict_types=1);

namespace RiskEngine\Enums;

/**
 * Sinais eliminatórios: se qualquer um for confirmado com evidência,
 * a classificação é ELEVADO, independentemente do score.
 */
enum KnockOut: string
{
    case SlaveLabor = 'lista_suja_trabalho_escravo';
    case Ineligible = 'empresa_inidonea_ceis_cnep';
    case Bankruptcy = 'falencia';
    case InactiveCnpj = 'cnpj_inapto_ou_baixado';
    case Corruption = 'condenacao_corrupcao';

    public function label(): string
    {
        return match ($this) {
            self::SlaveLabor => 'Consta na Lista Suja do trabalho escravo',
            self::Ineligible => 'Empresa inidônea ou suspensa (CEIS/CNEP)',
            self::Bankruptcy => 'Falência decretada',
            self::InactiveCnpj => 'CNPJ inapto, suspenso ou baixado',
            self::Corruption => 'Condenação por corrupção / Lei Anticorrupção',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $k) => $k->value, self::cases());
    }
}

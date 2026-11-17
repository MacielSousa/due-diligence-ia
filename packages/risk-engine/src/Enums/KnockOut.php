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

    /**
     * Tipos de documento que podem comprovar este sinal. Um sinal eliminatório vindo de outro
     * tipo de documento é rejeitado: ex. "Lista Suja" só vale da consulta à Lista Suja.
     *
     * @return list<DocumentType>
     */
    public function sourceTypes(): array
    {
        return match ($this) {
            self::SlaveLabor => [DocumentType::SlaveLaborList],
            self::Ineligible => [DocumentType::CeisCnep],
            self::Bankruptcy => [DocumentType::FinancialStatement, DocumentType::Lawsuits, DocumentType::CnpjCard],
            self::InactiveCnpj => [DocumentType::CnpjCard],
            self::Corruption => [DocumentType::Lawsuits, DocumentType::CeisCnep],
        };
    }

    public function acceptsFrom(DocumentType $type): bool
    {
        return in_array($type, $this->sourceTypes(), true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $k) => $k->value, self::cases());
    }
}

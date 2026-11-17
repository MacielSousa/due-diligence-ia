<?php

declare(strict_types=1);

namespace RiskEngine\Enums;

enum DocumentType: string
{
    case CnpjCard = 'cartao_cnpj';
    case FederalCertificate = 'certidao_federal';
    case LaborCertificate = 'cndt';
    case SlaveLaborList = 'lista_suja';
    case CeisCnep = 'ceis_cnep';
    case FinancialStatement = 'balanco';
    case TechnicalCertificate = 'atestado_tecnico';
    case Lawsuits = 'processos_judiciais';
    case News = 'noticias';
    case Other = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::CnpjCard => 'Cartão CNPJ',
            self::FederalCertificate => 'Certidão negativa federal',
            self::LaborCertificate => 'Certidão negativa de débitos trabalhistas (CNDT)',
            self::SlaveLaborList => 'Consulta à Lista Suja do trabalho escravo',
            self::CeisCnep => 'Consulta CEIS/CNEP',
            self::FinancialStatement => 'Balanço patrimonial / DRE',
            self::TechnicalCertificate => 'Atestado de capacidade técnica',
            self::Lawsuits => 'Processos judiciais',
            self::News => 'Notícias / mídia',
            self::Other => 'Outro',
        };
    }

    /** Validade em dias (null = não vence). Documento vencido reduz a confiança. */
    public function validityDays(): ?int
    {
        return match ($this) {
            self::FederalCertificate, self::LaborCertificate => 180,
            self::SlaveLaborList, self::CeisCnep, self::CnpjCard => 30,
            self::FinancialStatement => 365,
            default => null,
        };
    }

    /**
     * Documentos mínimos para uma avaliação com confiança total.
     *
     * @return list<self>
     */
    public static function required(): array
    {
        return [
            self::CnpjCard,
            self::FederalCertificate,
            self::LaborCertificate,
            self::SlaveLaborList,
            self::CeisCnep,
            self::FinancialStatement,
            self::TechnicalCertificate,
        ];
    }
}

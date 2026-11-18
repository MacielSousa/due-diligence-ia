<?php

namespace App\Services\Ia;

use App\Models\Anexo;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

/** Extrai e limpa o texto de um anexo antes de mandar para o modelo. */
class ExtratorDeTexto
{
    public function extrair(Anexo $anexo): string
    {
        if ($anexo->mime !== 'application/pdf') {
            throw new DocumentoIlegivelException('Imagem sem texto: a leitura de imagens (OCR) ainda não está disponível.');
        }

        try {
            $texto = (new Parser)->parseFile(Storage::disk(Anexo::DISCO)->path($anexo->caminho))->getText();
        } catch (Throwable $e) {
            throw new DocumentoIlegivelException('Não foi possível ler o PDF: '.$e->getMessage(), previous: $e);
        }

        $texto = self::limpar($texto);
        if (mb_strlen($texto) < 20) {
            throw new DocumentoIlegivelException('O PDF não tem texto extraível (pode ser digitalizado como imagem).');
        }

        return $texto;
    }

    /**
     * Tira marca d'água, cabeçalho e rodapé que confundem o modelo.
     * (o aviso "DOCUMENTO FICTÍCIO... SEM VALOR LEGAL" fazia o modelo achar que não havia nada a analisar)
     */
    public static function limpar(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", ' '], $texto);
        $texto = preg_replace('/^.*(DOCUMENTO FICT[IÍ]CIO|SEM VALOR LEGAL).*$\n?/miu', '', $texto);
        $texto = preg_replace('/^\s*P[aá]gina \d+ de \d+\s*$\n?/miu', '', $texto);
        $texto = preg_replace('/[ ]{2,}/', ' ', $texto);

        return trim(preg_replace("/\n{3,}/", "\n\n", $texto));
    }
}

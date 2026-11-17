<?php

declare(strict_types=1);

namespace Modules\Documentos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RiskEngine\Enums\DocumentType;

class StoreDocumentoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(DocumentType::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'emitido_em' => ['nullable', 'date', 'before_or_equal:today'],
            // Texto colado OU arquivo .txt (extração de PDF/OCR fica fora da PoC).
            'conteudo' => ['required_without:arquivo', 'nullable', 'string', 'max:200000'],
            'arquivo' => ['required_without:conteudo', 'nullable', 'file', 'mimes:txt', 'max:2048'],
        ];
    }

    public function texto(): string
    {
        return $this->hasFile('arquivo')
            ? (string) $this->file('arquivo')->get()
            : (string) $this->input('conteudo');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Fornecedores\Support\Cnpj;

class StoreFornecedorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge(['cnpj' => Cnpj::limpar((string) $this->input('cnpj'))]);
        }
    }

    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'unique:fornecedores,cnpj',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! Cnpj::valido((string) $value)) {
                        $fail('CNPJ inválido.');
                    }
                },
            ],
            'segmento' => ['sometimes', 'string', 'max:100'],
            'valor_contrato_estimado' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Rules\CnpjValido;
use App\Rules\CpfValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Usado no POST (criar) e no PUT (atualizar).
 * O tipo (pf/pj) só pode ser definido na criação.
 */
class TerceiroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documento')) {
            $this->merge(['documento' => preg_replace('/\D/', '', (string) $this->input('documento'))]);
        }
    }

    public function rules(): array
    {
        $terceiro = $this->route('terceiro');
        $tipo = $terceiro?->tipo ?? $this->input('tipo');

        return [
            'tipo' => [$terceiro ? 'prohibited' : 'required', Rule::in(['pf', 'pj'])],
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'documento' => [
                'bail', // para na primeira regra que falhar (não mostra "DV inválido" se nem tem 11 dígitos)
                'required',
                $tipo === 'pj' ? 'digits:14' : 'digits:11',
                $tipo === 'pj' ? new CnpjValido : new CpfValido,
                Rule::unique('terceiros', 'documento')->ignore($terceiro?->id),
            ],
            'pep' => ['sometimes', 'boolean'],
        ];
    }
}

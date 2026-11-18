<?php

namespace App\Http\Requests;

use App\Models\Apontamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApontamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(Apontamento::TIPOS)],
            'ano' => ['required', 'integer', 'between:1900,' . date('Y')],
        ];
    }
}

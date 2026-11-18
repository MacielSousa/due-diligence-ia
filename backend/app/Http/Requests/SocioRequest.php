<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empresa = $this->route('terceiro');

        return [
            'socio_id' => [
                'required',
                'integer',
                Rule::exists('terceiros', 'id'),
                Rule::notIn([$empresa->id]), // não pode ser sócia de si mesma
                Rule::unique('socios', 'socio_id')->where('empresa_id', $empresa->id),
            ],
            'participacao' => ['required', 'numeric', 'gt:0', 'max:100'],
        ];
    }

    /** Regras que dependem do estado do banco, rodadas depois das regras acima. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $empresa = $this->route('terceiro');

                if (! $empresa->isPj()) {
                    $validator->errors()->add('socio_id', __('api.socio.apenas_pj'));

                    return;
                }

                $total = (float) $empresa->socios()->sum('participacao') + (float) $this->input('participacao');
                if ($total > 100) {
                    $validator->errors()->add('participacao', __('api.socio.soma_excede', ['total' => $total]));
                }
            },
        ];
    }
}

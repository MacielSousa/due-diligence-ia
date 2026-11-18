<?php

namespace App\Http\Requests;

use App\Models\Anexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnexoRequest extends FormRequest
{
    /** Tamanho máximo em KB (o upload_max_filesize padrão do PHP é 2M). */
    public const TAMANHO_MAXIMO_KB = 2048;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria' => ['required', Rule::in(Anexo::CATEGORIAS)],
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . self::TAMANHO_MAXIMO_KB],
        ];
    }
}

<?php

namespace App\Rules;

use App\Domain\DueDiligence\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Confere os dígitos verificadores do CNPJ (mesma regra do domínio). */
class CnpjValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Documento::cnpjValido($value)) {
            $fail('validation.cnpj')->translate();
        }
    }
}

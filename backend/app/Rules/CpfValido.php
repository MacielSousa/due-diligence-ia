<?php

namespace App\Rules;

use App\Domain\DueDiligence\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Confere os dígitos verificadores do CPF (mesma regra do domínio). */
class CpfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Documento::cpfValido($value)) {
            $fail('validation.cpf')->translate();
        }
    }
}

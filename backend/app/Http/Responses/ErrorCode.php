<?php

namespace App\Http\Responses;

/** Códigos de erro estáveis: o frontend decide o que fazer pelo `code`, não pelo texto. */
enum ErrorCode: string
{
    case Validation = 'validation_error';       // 422 - campos inválidos (vem com `errors`)
    case BusinessRule = 'business_rule';        // 422 - regra de negócio do domínio violada
    case NotFound = 'not_found';                // 404
    case MethodNotAllowed = 'method_not_allowed'; // 405
    case DomainIncomplete = 'domain_incomplete'; // 501 - classes do desafio ainda não implementadas
    case Http = 'http_error';                   // outros erros HTTP
    case Internal = 'internal_error';           // 500
}

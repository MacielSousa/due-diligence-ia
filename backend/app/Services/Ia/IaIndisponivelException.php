<?php

namespace App\Services\Ia;

use RuntimeException;

/** O Ollama não respondeu (fora do ar, modelo inexistente, timeout). */
class IaIndisponivelException extends RuntimeException {}

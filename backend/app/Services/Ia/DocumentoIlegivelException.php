<?php

namespace App\Services\Ia;

use RuntimeException;

/** Não foi possível extrair texto do documento (imagem sem OCR, PDF escaneado, arquivo corrompido). */
class DocumentoIlegivelException extends RuntimeException {}

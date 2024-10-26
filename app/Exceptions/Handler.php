<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Models\ErrorLog;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        // Adicione tipos de exceção que você não deseja relatar, se necessário
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Salvar os detalhes do erro no banco de dados
            ErrorLog::create([
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
    }
}

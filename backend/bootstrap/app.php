<?php

use App\Http\Middleware\DefinirIdioma;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorCode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // prepend: o idioma é definido antes do route model binding (404 já sai traduzido)
        $middleware->api(prepend: [DefinirIdioma::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $ehApi = fn (Request $request) => $request->is('api/*');

        $exceptions->shouldRenderJsonWhen($ehApi);

        // Todos os erros da API saem no formato padrão do ApiResponse (a ordem importa: do mais específico ao genérico)
        $exceptions->render(function (ValidationException $e, Request $request) use ($ehApi) {
            if ($ehApi($request)) {
                return ApiResponse::error(__('api.validacao'), ErrorCode::Validation, 422, $e->errors());
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($ehApi) {
            if ($ehApi($request)) {
                return ApiResponse::error(__('api.nao_encontrado'), ErrorCode::NotFound, 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($ehApi) {
            if ($ehApi($request)) {
                return ApiResponse::error(__('api.metodo_nao_permitido'), ErrorCode::MethodNotAllowed, 405);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($ehApi) {
            if ($ehApi($request)) {
                return ApiResponse::error($e->getMessage() ?: __('api.erro_http'), ErrorCode::Http, $e->getStatusCode());
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) use ($ehApi) {
            if ($ehApi($request)) {
                // detalhe técnico só em ambiente de desenvolvimento (APP_DEBUG=true)
                return ApiResponse::error(__('api.erro_interno'), ErrorCode::Internal, 500, detail: config('app.debug') ? $e->getMessage() : null);
            }
        });
    })->create();

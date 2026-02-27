<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
protected function unauthenticated($request, AuthenticationException $exception)
{
    // Si la solicitud es una API, devolvemos un JSON con el error 401 (Unauthorized)
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    
    // De lo contrario, redirigimos (comportamiento por defecto, aunque puedes eliminar esta parte si solo usas API)
    return redirect()->guest(route('login'));
}
}

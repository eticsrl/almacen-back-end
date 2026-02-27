<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    // Código modificado para APIs/SPAs

    protected function redirectTo($request)
    {
        // Si la solicitud NO espera una respuesta JSON (es decir, NO es una llamada de Vue/Axios)
        if (! $request->expectsJson()) {
            // En lugar de redirigir, puedes devolver null para evitar el error.
            // O si quieres redirigir a una ruta de Blade, asegúrate de que exista la ruta 'login'.
            // Ya que usas Vue/Axios, lo mejor es que devuelva null o la redirección al frontend.
            return null;

            // Alternativamente, si quieres que Laravel devuelva un error 401:
            // abort(401);
        }
        // Si espera JSON (petición de Vue/Axios), simplemente no devolvemos nada, 
        // y el middleware retornará automáticamente un código 401 Unauthorized.


    }
    /*protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }*/
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimiter
{
    /**
     * El rate limiter instance.
     */
    protected RateLimiter $limiter;

    /**
     * Crear una nueva instancia del middleware.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Maneja una solicitud entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $maxAttempts  // Máximo de intentos permitidos
     */
    public function handle(Request $request, Closure $next, ?string $maxAttempts = '5'): Response
    {
        $key = $this->resolveRequestSignature($request);

        // Verificar si excede el límite
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        // Incrementar contador de intentos
        $this->limiter->hit($key);

        $response = $next($request);

        // Si la respuesta no es exitosa, incrementar el contador
        if (!$response->isSuccessful()) {
            $this->limiter->hit($key);
        }

        return $response;
    }

    /**
     * Resuelve la firma de la solicitud para rate limiting.
     *
     * Combina IP + username para rate limiting dual, ofreciendo máxima seguridad.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->ip() . // IP del usuario
            '|' .
            $request->input('username', 'no-username') // Username del formulario
        );
    }

    /**
     * Construye la respuesta cuando se excede el límite.
     *
     * @param  string  $key
     * @param  string  $maxAttempts
     */
    protected function buildResponse(string $key, string $maxAttempts): Response
    {
        $seconds = $this->limiter->availableIn($key);
        $minutes = ceil($seconds / 60);

        // Determinar el formato de respuesta según el tipo de solicitud
        if (request()->expectsJson()) {
            return response()->json([
                'message' => Lang::get('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => $minutes,
                ]),
                'retry_after' => $seconds,
                'available_in_minutes' => $minutes,
            ], 429);
        }

        // Para solicitudes web tradicionales, redirigir con mensaje de error
        return back()
            ->withInput(request()->only('username', 'remember'))
            ->withErrors([
                'username' => Lang::get('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => $minutes,
                ]),
            ]);
    }
}
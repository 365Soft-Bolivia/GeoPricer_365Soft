<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PublicAuth
{
    /**
     * Maneja una solicitud entrante para autenticación pública.
     *
     * Este middleware verifica que el usuario esté autenticado y tenga
     * permisos para acceder a las rutas públicas del sistema.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('public.login')
                ->with('message', 'Debes iniciar sesión para acceder a esta sección.');
        }

        $user = Auth::user();

        // Verificar que el usuario tenga un rol válido para acceso público
        // Roles permitidos: user, client, guest, admin
        if (!$user->roles || $user->roles->isEmpty()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('public.login')
                ->with('error', 'Tu cuenta no tiene los permisos necesarios. Contacta al administrador.');
        }

        $userRoleName = $user->roles->first()->name;

        // Verificar que el rol sea permitido para el sistema público
        $allowedRoles = ['user', 'client', 'guest', 'admin'];
        if (!in_array($userRoleName, $allowedRoles)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('public.login')
                ->with('error', 'Acceso no autorizado. Tu cuenta no tiene permisos para este sistema.');
        }

        // Verificar que el usuario esté activo
        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('public.login')
                ->with('error', 'Tu cuenta está desactivada. Contacta al administrador.');
        }

        // Verificar que el login esté habilitado
        if ($user->login !== 'enable') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('public.login')
                ->with('error', 'El acceso a tu cuenta ha sido deshabilitado. Contacta al administrador.');
        }

        return $next($request);
    }
}
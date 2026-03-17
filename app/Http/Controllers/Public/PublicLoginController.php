<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicLoginController extends Controller
{
    /**
     * Mostrar el formulario de login público.
     */
    public function showLoginForm(Request $request): Response
    {
        return Inertia::render('Public/PublicLogin', [
            'status' => $request->session()->get('status'),
            'message' => $request->session()->get('message'),
            'error' => $request->session()->get('error'),
        ]);
    }

    /**
     * Manejar el intento de login público con auditoría completa.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validar datos del formulario
        $credentials = $request->validate([
            'username' => ['required', 'string', 'min:3'],
            'password' => ['required', 'string'],
        ]);

        // Capturar información del cliente para auditoría
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $username = $request->input('username');

        // Registrar intento de login en auditoría
        $loginAttempt = LoginAttempt::create([
            'email' => $username, // Guardamos el username en el campo email para compatibilidad
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'successful' => false,
            'failure_reason' => null,
            'attempted_at' => now(),
        ]);

        // Buscar usuario por username
        $user = User::withoutGlobalScopes()->where('username', $username)->first();

        // Verificar si el usuario existe
        if (!$user) {
            $loginAttempt->update([
                'successful' => false,
                'failure_reason' => 'User not found'
            ]);

            throw ValidationException::withMessages([
                'username' => 'Las credenciales proporcionadas no son correctas.',
            ]);
        }

        // Verificar estado del usuario
        if ($user->status !== 'active') {
            $loginAttempt->update([
                'successful' => false,
                'failure_reason' => 'Account inactive: ' . $user->status
            ]);

            throw ValidationException::withMessages([
                'username' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        // Verificar si el login está habilitado
        if ($user->login !== 'enable') {
            $loginAttempt->update([
                'successful' => false,
                'failure_reason' => 'Login disabled'
            ]);

            throw ValidationException::withMessages([
                'username' => 'El acceso a tu cuenta ha sido deshabilitado. Contacta al administrador.',
            ]);
        }

        // Verificar credenciales (email y contraseña)
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            $loginAttempt->update([
                'successful' => false,
                'failure_reason' => 'Invalid credentials'
            ]);

            throw ValidationException::withMessages([
                'username' => 'Las credenciales proporcionadas no son correctas.',
            ]);
        }

        // Verificar que el usuario tenga un rol válido para acceso público
        if (!$user->roles || $user->roles->isEmpty()) {
            Auth::logout();
            $loginAttempt->update([
                'successful' => false,
                'failure_reason' => 'No roles assigned'
            ]);

            return back()->withErrors([
                'username' => 'Tu cuenta no tiene los permisos necesarios. Contacta al administrador.',
            ]);
        }

        $userRoleName = $user->roles->first()->name;
        $allowedRoles = ['user', 'client', 'guest', 'admin'];

        if (!in_array($userRoleName, $allowedRoles)) {
            Auth::logout();
            $loginAttempt->update([
                'successful' => false,
                'failure_reason' => 'Unauthorized role: ' . $userRoleName
            ]);

            return back()->withErrors([
                'username' => 'Acceso no autorizado. Tu cuenta no tiene permisos para este sistema.',
            ]);
        }

        // Login exitoso - actualizar intento y regenerar sesión
        $loginAttempt->update(['successful' => true]);

        $request->session()->regenerate();
        $request->session()->put('login_time', now());

        // Redirigir a la página solicitada o al home público
        return redirect()->intended(route('public.home', absolute: false))
            ->with('success', '¡Bienvenido! Has iniciado sesión correctamente.');
    }

    /**
     * Cerrar la sesión del usuario público.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget('login_time');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.login')
            ->with('success', 'Has cerrado sesión correctamente.');
    }
}
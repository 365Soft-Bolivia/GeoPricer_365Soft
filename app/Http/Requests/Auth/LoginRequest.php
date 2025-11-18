<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function validateCredentials(): User
    {
        $this->ensureIsNotRateLimited();

        // DEBUG: Ver qué email se está intentando
        \Log::info('🔍 Intento de login:', ['email' => $this->input('email')]);

        // Buscar usuario SIN el scope de activos
        $user = User::withoutGlobalScopes()->where('email', $this->input('email'))->first();

        // DEBUG: Ver si encontró el usuario
        \Log::info('👤 Usuario encontrado:', [
            'existe' => $user ? 'SI' : 'NO',
            'status' => $user ? $user->status : 'N/A',
            'id' => $user ? $user->id : 'N/A'
        ]);

        // Si el usuario no existe
        if (!$user) {
            RateLimiter::hit($this->throttleKey());
            \Log::warning('❌ Usuario no encontrado');

            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas no son correctas.',
            ]);
        }

        // Verificar el estado ANTES de validar la contraseña
        \Log::info('✅ Verificando estado:', [
            'status_actual' => $user->status,
            'es_active' => $user->status === 'active' ? 'SI' : 'NO'
        ]);

        if ($user->status !== 'active') {
            RateLimiter::hit($this->throttleKey());
            \Log::warning('❌ Cuenta INACTIVA para: ' . $user->email);

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        // Validar la contraseña
        if (!Auth::getProvider()->validateCredentials($user, ['password' => $this->input('password')])) {
            RateLimiter::hit($this->throttleKey());
            \Log::warning('❌ Contraseña incorrecta para: ' . $user->email);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas no son correctas.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        \Log::info('✅ Login exitoso para: ' . $user->email);

        return $user;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return $this->string('email')
            ->lower()
            ->append('|' . $this->ip())
            ->transliterate()
            ->value();
    }
}
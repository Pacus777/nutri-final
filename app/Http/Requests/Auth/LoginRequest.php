<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Usuario;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cor_usu' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt([
            'cor_usu' => $this->input('cor_usu'),
            'password' => $this->input('password'),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'cor_usu' => trans('auth.failed'),
            ]);
        }

        $usuario = Auth::user();

        if (! $usuario instanceof Usuario) {
            Auth::logout();

            throw ValidationException::withMessages([
                'cor_usu' => 'No fue posible autenticar tu usuario.',
            ]);
        }

        if (! $usuario->estaActivo()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'cor_usu' => 'Tu cuenta no está activa para ingresar al sistema.',
            ]);
        }

        $rolesActivos = $usuario->roles()
            ->where('est_rol', 'activo')
            ->exists();

        if (! $rolesActivos) {
            Auth::logout();

            throw ValidationException::withMessages([
                'cor_usu' => 'Tu usuario no tiene un rol activo asignado.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'cor_usu' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('cor_usu')) . '|' . $this->ip()
        );
    }
}
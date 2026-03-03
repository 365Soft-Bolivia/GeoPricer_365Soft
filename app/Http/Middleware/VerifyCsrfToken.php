<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs que deben estar exentas de la protección CSRF.
     * Agrega cualquier ruta que quieras permitir sin enviar el token.
     *
     * @var array
     */
    protected $except = [
        // proceso de importación JSON sin token
        'admin/data-import/process',
    ];
}

import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../wayfinder'
// Re-export commonly used top-level routes from admin to match existing imports
export { dashboard, login, register } from './admin'
/**
* @see \Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::logout
 * @see vendor/laravel/fortify/src/Http/Controllers/AuthenticatedSessionController.php:100
 * @route '/fortify-disabled/logout'
 */
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/fortify-disabled/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::logout
 * @see vendor/laravel/fortify/src/Http/Controllers/AuthenticatedSessionController.php:100
 * @route '/fortify-disabled/logout'
 */
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::logout
 * @see vendor/laravel/fortify/src/Http/Controllers/AuthenticatedSessionController.php:100
 * @route '/fortify-disabled/logout'
 */
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

    /**
* @see \Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::logout
 * @see vendor/laravel/fortify/src/Http/Controllers/AuthenticatedSessionController.php:100
 * @route '/fortify-disabled/logout'
 */
    const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: logout.url(options),
        method: 'post',
    })

            /**
* @see \Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::logout
 * @see vendor/laravel/fortify/src/Http/Controllers/AuthenticatedSessionController.php:100
 * @route '/fortify-disabled/logout'
 */
        logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: logout.url(options),
            method: 'post',
        })
    
    logout.form = logoutForm
/**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
    const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: home.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
        homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: home.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Public\HomeController::home
 * @see app/Http/Controllers/Public/HomeController.php:17
 * @route '/'
 */
        homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: home.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    home.form = homeForm
/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
export const listar = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listar.url(options),
    method: 'get',
})

listar.definition = {
    methods: ["get","head"],
    url: '/admin/listar',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
listar.url = (options?: RouteQueryOptions) => {
    return listar.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
listar.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listar.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
listar.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: listar.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
    const listarForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: listar.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
        listarForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: listar.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/listar'
 */
        listarForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: listar.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    listar.form = listarForm
/**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/{id}/toggle-status'
 */
export const toggleStatus = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleStatus.url(args, options),
    method: 'post',
})

toggleStatus.definition = {
    methods: ["post"],
    url: '/admin/{id}/toggle-status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/{id}/toggle-status'
 */
toggleStatus.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return toggleStatus.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/{id}/toggle-status'
 */
toggleStatus.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleStatus.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/{id}/toggle-status'
 */
    const toggleStatusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: toggleStatus.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/{id}/toggle-status'
 */
        toggleStatusForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: toggleStatus.url(args, options),
            method: 'post',
        })
    
    toggleStatus.form = toggleStatusForm
/**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/{id}'
 */
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/admin/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/{id}'
 */
update.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return update.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/{id}'
 */
update.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/{id}'
 */
    const updateForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/{id}'
 */
        updateForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/{id}'
 */
export const destroy = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/{id}'
 */
destroy.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return destroy.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/{id}'
 */
destroy.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/{id}'
 */
    const destroyForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/{id}'
 */
        destroyForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
export const accesos = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accesos.url(options),
    method: 'get',
})

accesos.definition = {
    methods: ["get","head"],
    url: '/accesos',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
accesos.url = (options?: RouteQueryOptions) => {
    return accesos.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
accesos.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accesos.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
accesos.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: accesos.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
    const accesosForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: accesos.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
        accesosForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accesos.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/accesos'
 */
        accesosForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accesos.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    accesos.form = accesosForm
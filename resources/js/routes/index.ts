import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../wayfinder'
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
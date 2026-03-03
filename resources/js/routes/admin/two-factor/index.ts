import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/settings/two-factor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
    const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
        showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
 * @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:28
 * @route '/admin/settings/two-factor'
 */
        showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
    /**
     * Enable two-factor (POST)
     */
    export const enable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
        url: enable.url(options),
        method: 'post',
    })

    enable.definition = {
        methods: ["post"],
        url: '/admin/settings/two-factor/enable',
    } satisfies RouteDefinition<["post"]>

    enable.url = (options?: RouteQueryOptions) => {
        return enable.definition.url + queryParams(options)
    }

    enable.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
        url: enable.url(options),
        method: 'post',
    })

    const enableForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: enable.url(options),
        method: 'post',
    })

    enable.form = enableForm

    /**
     * Disable two-factor (POST)
     */
    export const disable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
        url: disable.url(options),
        method: 'post',
    })

    disable.definition = {
        methods: ["post"],
        url: '/admin/settings/two-factor/disable',
    } satisfies RouteDefinition<["post"]>

    disable.url = (options?: RouteQueryOptions) => {
        return disable.definition.url + queryParams(options)
    }

    disable.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
        url: disable.url(options),
        method: 'post',
    })

    const disableForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: disable.url(options),
        method: 'post',
    })

    disable.form = disableForm

    const twoFactor = {
        show: Object.assign(show, show),
        enable: Object.assign(enable, enable),
        disable: Object.assign(disable, disable),
    }

export default twoFactor
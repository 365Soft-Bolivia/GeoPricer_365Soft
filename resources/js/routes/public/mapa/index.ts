import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
export const propiedades = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: propiedades.url(options),
    method: 'get',
})

propiedades.definition = {
    methods: ["get","head"],
    url: '/mapa-propiedades',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
propiedades.url = (options?: RouteQueryOptions) => {
    return propiedades.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
propiedades.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: propiedades.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
propiedades.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: propiedades.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
    const propiedadesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: propiedades.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
        propiedadesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: propiedades.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:42
 * @route '/mapa-propiedades'
 */
        propiedadesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: propiedades.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    propiedades.form = propiedadesForm
const mapa = {
    propiedades: Object.assign(propiedades, propiedades),
}

export default mapa
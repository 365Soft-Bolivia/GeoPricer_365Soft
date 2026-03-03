import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import contacto6f4417 from './contacto'
import propiedad from './propiedad'
import mapa from './mapa'
/**
 * @see routes/public.php:10
 * @route '/contacto'
 */
export const contacto = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contacto.url(options),
    method: 'get',
})

contacto.definition = {
    methods: ["get","head"],
    url: '/contacto',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/public.php:10
 * @route '/contacto'
 */
contacto.url = (options?: RouteQueryOptions) => {
    return contacto.definition.url + queryParams(options)
}

/**
 * @see routes/public.php:10
 * @route '/contacto'
 */
contacto.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contacto.url(options),
    method: 'get',
})
/**
 * @see routes/public.php:10
 * @route '/contacto'
 */
contacto.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: contacto.url(options),
    method: 'head',
})

    /**
 * @see routes/public.php:10
 * @route '/contacto'
 */
    const contactoForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: contacto.url(options),
        method: 'get',
    })

            /**
 * @see routes/public.php:10
 * @route '/contacto'
 */
        contactoForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: contacto.url(options),
            method: 'get',
        })
            /**
 * @see routes/public.php:10
 * @route '/contacto'
 */
        contactoForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: contacto.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    contacto.form = contactoForm
/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
 */
export const propiedades = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: propiedades.url(options),
    method: 'get',
})

propiedades.definition = {
    methods: ["get","head"],
    url: '/propiedades',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
 */
propiedades.url = (options?: RouteQueryOptions) => {
    return propiedades.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
 */
propiedades.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: propiedades.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
 */
propiedades.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: propiedades.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
 */
    const propiedadesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: propiedades.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
 */
        propiedadesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: propiedades.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Public\PropiedadPublicController::propiedades
 * @see app/Http/Controllers/Public/PropiedadPublicController.php:27
 * @route '/propiedades'
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
/**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
export const sobre = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sobre.url(options),
    method: 'get',
})

sobre.definition = {
    methods: ["get","head"],
    url: '/sobre-nosotros',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
sobre.url = (options?: RouteQueryOptions) => {
    return sobre.definition.url + queryParams(options)
}

/**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
sobre.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sobre.url(options),
    method: 'get',
})
/**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
sobre.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sobre.url(options),
    method: 'head',
})

    /**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
    const sobreForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: sobre.url(options),
        method: 'get',
    })

            /**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
        sobreForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: sobre.url(options),
            method: 'get',
        })
            /**
 * @see routes/public.php:30
 * @route '/sobre-nosotros'
 */
        sobreForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: sobre.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    sobre.form = sobreForm
const publicMethod = {
    contacto: Object.assign(contacto, contacto6f4417),
propiedades: Object.assign(propiedades, propiedades),
propiedad: Object.assign(propiedad, propiedad),
mapa: Object.assign(mapa, mapa),
sobre: Object.assign(sobre, sobre),
}

export default publicMethod
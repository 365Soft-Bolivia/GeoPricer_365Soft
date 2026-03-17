import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import api from './api'
/**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
export const mapa = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mapa.url(options),
    method: 'get',
})

mapa.definition = {
    methods: ["get","head"],
    url: '/admin/ubicaciones/mapa',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
mapa.url = (options?: RouteQueryOptions) => {
    return mapa.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
mapa.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mapa.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
mapa.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mapa.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
    const mapaForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: mapa.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
        mapaForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: mapa.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ProductLocationController::mapa
 * @see app/Http/Controllers/ProductLocationController.php:366
 * @route '/admin/ubicaciones/mapa'
 */
        mapaForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: mapa.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    mapa.form = mapaForm
/**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
export const asignar = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: asignar.url(options),
    method: 'get',
})

asignar.definition = {
    methods: ["get","head"],
    url: '/admin/ubicaciones/asignar',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
asignar.url = (options?: RouteQueryOptions) => {
    return asignar.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
asignar.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: asignar.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
asignar.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: asignar.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
    const asignarForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: asignar.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
        asignarForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: asignar.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ProductLocationController::asignar
 * @see app/Http/Controllers/ProductLocationController.php:430
 * @route '/admin/ubicaciones/asignar'
 */
        asignarForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: asignar.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    asignar.form = asignarForm
/**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
export const editar = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editar.url(options),
    method: 'get',
})

editar.definition = {
    methods: ["get","head"],
    url: '/admin/ubicaciones/editar',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
editar.url = (options?: RouteQueryOptions) => {
    return editar.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
editar.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editar.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
editar.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editar.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
    const editarForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: editar.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
        editarForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editar.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ProductLocationController::editar
 * @see app/Http/Controllers/ProductLocationController.php:467
 * @route '/admin/ubicaciones/editar'
 */
        editarForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editar.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    editar.form = editarForm
const ubicaciones = {
    mapa: Object.assign(mapa, mapa),
asignar: Object.assign(asignar, asignar),
editar: Object.assign(editar, editar),
api: Object.assign(api, api),
}

export default ubicaciones
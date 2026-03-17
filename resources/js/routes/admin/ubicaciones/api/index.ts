import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
 */
export const listar = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listar.url(options),
    method: 'get',
})

listar.definition = {
    methods: ["get","head"],
    url: '/admin/ubicaciones/api/listar',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
 */
listar.url = (options?: RouteQueryOptions) => {
    return listar.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
 */
listar.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listar.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
 */
listar.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: listar.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
 */
    const listarForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: listar.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
 */
        listarForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: listar.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ProductLocationController::listar
 * @see app/Http/Controllers/ProductLocationController.php:71
 * @route '/admin/ubicaciones/api/listar'
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
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
export const show = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/ubicaciones/api/{product}/obtener',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
show.url = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { product: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    product: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        product: typeof args.product === 'object'
                ? args.product.id
                : args.product,
                }

    return show.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
show.get = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
show.head = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
    const showForm = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
        showForm.get = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ProductLocationController::show
 * @see app/Http/Controllers/ProductLocationController.php:117
 * @route '/admin/ubicaciones/api/{product}/obtener'
 */
        showForm.head = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
/**
* @see \App\Http\Controllers\ProductLocationController::store
 * @see app/Http/Controllers/ProductLocationController.php:146
 * @route '/admin/ubicaciones/api/{product}'
 */
export const store = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/ubicaciones/api/{product}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProductLocationController::store
 * @see app/Http/Controllers/ProductLocationController.php:146
 * @route '/admin/ubicaciones/api/{product}'
 */
store.url = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { product: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    product: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        product: typeof args.product === 'object'
                ? args.product.id
                : args.product,
                }

    return store.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::store
 * @see app/Http/Controllers/ProductLocationController.php:146
 * @route '/admin/ubicaciones/api/{product}'
 */
store.post = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::store
 * @see app/Http/Controllers/ProductLocationController.php:146
 * @route '/admin/ubicaciones/api/{product}'
 */
    const storeForm = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::store
 * @see app/Http/Controllers/ProductLocationController.php:146
 * @route '/admin/ubicaciones/api/{product}'
 */
        storeForm.post = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(args, options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\ProductLocationController::toggleStatus
 * @see app/Http/Controllers/ProductLocationController.php:206
 * @route '/admin/ubicaciones/api/{product}/toggle-status'
 */
export const toggleStatus = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleStatus.url(args, options),
    method: 'post',
})

toggleStatus.definition = {
    methods: ["post"],
    url: '/admin/ubicaciones/api/{product}/toggle-status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProductLocationController::toggleStatus
 * @see app/Http/Controllers/ProductLocationController.php:206
 * @route '/admin/ubicaciones/api/{product}/toggle-status'
 */
toggleStatus.url = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { product: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    product: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        product: typeof args.product === 'object'
                ? args.product.id
                : args.product,
                }

    return toggleStatus.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::toggleStatus
 * @see app/Http/Controllers/ProductLocationController.php:206
 * @route '/admin/ubicaciones/api/{product}/toggle-status'
 */
toggleStatus.post = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleStatus.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::toggleStatus
 * @see app/Http/Controllers/ProductLocationController.php:206
 * @route '/admin/ubicaciones/api/{product}/toggle-status'
 */
    const toggleStatusForm = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: toggleStatus.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::toggleStatus
 * @see app/Http/Controllers/ProductLocationController.php:206
 * @route '/admin/ubicaciones/api/{product}/toggle-status'
 */
        toggleStatusForm.post = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: toggleStatus.url(args, options),
            method: 'post',
        })
    
    toggleStatus.form = toggleStatusForm
/**
* @see \App\Http\Controllers\ProductLocationController::destroy
 * @see app/Http/Controllers/ProductLocationController.php:242
 * @route '/admin/ubicaciones/api/{product}'
 */
export const destroy = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/ubicaciones/api/{product}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProductLocationController::destroy
 * @see app/Http/Controllers/ProductLocationController.php:242
 * @route '/admin/ubicaciones/api/{product}'
 */
destroy.url = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { product: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    product: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        product: typeof args.product === 'object'
                ? args.product.id
                : args.product,
                }

    return destroy.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::destroy
 * @see app/Http/Controllers/ProductLocationController.php:242
 * @route '/admin/ubicaciones/api/{product}'
 */
destroy.delete = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::destroy
 * @see app/Http/Controllers/ProductLocationController.php:242
 * @route '/admin/ubicaciones/api/{product}'
 */
    const destroyForm = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::destroy
 * @see app/Http/Controllers/ProductLocationController.php:242
 * @route '/admin/ubicaciones/api/{product}'
 */
        destroyForm.delete = (args: { product: number | { id: number } } | [product: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\ProductLocationController::cercanos
 * @see app/Http/Controllers/ProductLocationController.php:270
 * @route '/admin/ubicaciones/api/cercanos'
 */
export const cercanos = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cercanos.url(options),
    method: 'post',
})

cercanos.definition = {
    methods: ["post"],
    url: '/admin/ubicaciones/api/cercanos',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProductLocationController::cercanos
 * @see app/Http/Controllers/ProductLocationController.php:270
 * @route '/admin/ubicaciones/api/cercanos'
 */
cercanos.url = (options?: RouteQueryOptions) => {
    return cercanos.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::cercanos
 * @see app/Http/Controllers/ProductLocationController.php:270
 * @route '/admin/ubicaciones/api/cercanos'
 */
cercanos.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cercanos.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::cercanos
 * @see app/Http/Controllers/ProductLocationController.php:270
 * @route '/admin/ubicaciones/api/cercanos'
 */
    const cercanosForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: cercanos.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::cercanos
 * @see app/Http/Controllers/ProductLocationController.php:270
 * @route '/admin/ubicaciones/api/cercanos'
 */
        cercanosForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: cercanos.url(options),
            method: 'post',
        })
    
    cercanos.form = cercanosForm
const api = {
    listar: Object.assign(listar, listar),
show: Object.assign(show, show),
store: Object.assign(store, store),
toggleStatus: Object.assign(toggleStatus, toggleStatus),
destroy: Object.assign(destroy, destroy),
cercanos: Object.assign(cercanos, cercanos),
}

export default api
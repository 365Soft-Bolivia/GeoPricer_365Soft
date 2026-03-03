import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ProductImageController::store
 * @see app/Http/Controllers/ProductImageController.php:18
 * @route '/admin/productos/{productId}/imagenes'
 */
export const store = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/productos/{productId}/imagenes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProductImageController::store
 * @see app/Http/Controllers/ProductImageController.php:18
 * @route '/admin/productos/{productId}/imagenes'
 */
store.url = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { productId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    productId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        productId: args.productId,
                }

    return store.definition.url
            .replace('{productId}', parsedArgs.productId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductImageController::store
 * @see app/Http/Controllers/ProductImageController.php:18
 * @route '/admin/productos/{productId}/imagenes'
 */
store.post = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ProductImageController::store
 * @see app/Http/Controllers/ProductImageController.php:18
 * @route '/admin/productos/{productId}/imagenes'
 */
    const storeForm = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductImageController::store
 * @see app/Http/Controllers/ProductImageController.php:18
 * @route '/admin/productos/{productId}/imagenes'
 */
        storeForm.post = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(args, options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\ProductImageController::setPrimary
 * @see app/Http/Controllers/ProductImageController.php:100
 * @route '/admin/productos/{productId}/imagenes/{imageId}/principal'
 */
export const setPrimary = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: setPrimary.url(args, options),
    method: 'post',
})

setPrimary.definition = {
    methods: ["post"],
    url: '/admin/productos/{productId}/imagenes/{imageId}/principal',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProductImageController::setPrimary
 * @see app/Http/Controllers/ProductImageController.php:100
 * @route '/admin/productos/{productId}/imagenes/{imageId}/principal'
 */
setPrimary.url = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    productId: args[0],
                    imageId: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        productId: args.productId,
                                imageId: args.imageId,
                }

    return setPrimary.definition.url
            .replace('{productId}', parsedArgs.productId.toString())
            .replace('{imageId}', parsedArgs.imageId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductImageController::setPrimary
 * @see app/Http/Controllers/ProductImageController.php:100
 * @route '/admin/productos/{productId}/imagenes/{imageId}/principal'
 */
setPrimary.post = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: setPrimary.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ProductImageController::setPrimary
 * @see app/Http/Controllers/ProductImageController.php:100
 * @route '/admin/productos/{productId}/imagenes/{imageId}/principal'
 */
    const setPrimaryForm = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: setPrimary.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductImageController::setPrimary
 * @see app/Http/Controllers/ProductImageController.php:100
 * @route '/admin/productos/{productId}/imagenes/{imageId}/principal'
 */
        setPrimaryForm.post = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: setPrimary.url(args, options),
            method: 'post',
        })
    
    setPrimary.form = setPrimaryForm
/**
* @see \App\Http\Controllers\ProductImageController::deleteMethod
 * @see app/Http/Controllers/ProductImageController.php:133
 * @route '/admin/productos/{productId}/imagenes/{imageId}'
 */
export const deleteMethod = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

deleteMethod.definition = {
    methods: ["delete"],
    url: '/admin/productos/{productId}/imagenes/{imageId}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProductImageController::deleteMethod
 * @see app/Http/Controllers/ProductImageController.php:133
 * @route '/admin/productos/{productId}/imagenes/{imageId}'
 */
deleteMethod.url = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    productId: args[0],
                    imageId: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        productId: args.productId,
                                imageId: args.imageId,
                }

    return deleteMethod.definition.url
            .replace('{productId}', parsedArgs.productId.toString())
            .replace('{imageId}', parsedArgs.imageId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductImageController::deleteMethod
 * @see app/Http/Controllers/ProductImageController.php:133
 * @route '/admin/productos/{productId}/imagenes/{imageId}'
 */
deleteMethod.delete = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\ProductImageController::deleteMethod
 * @see app/Http/Controllers/ProductImageController.php:133
 * @route '/admin/productos/{productId}/imagenes/{imageId}'
 */
    const deleteMethodForm = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deleteMethod.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductImageController::deleteMethod
 * @see app/Http/Controllers/ProductImageController.php:133
 * @route '/admin/productos/{productId}/imagenes/{imageId}'
 */
        deleteMethodForm.delete = (args: { productId: string | number, imageId: string | number } | [productId: string | number, imageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deleteMethod.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    deleteMethod.form = deleteMethodForm
/**
* @see \App\Http\Controllers\ProductImageController::reorder
 * @see app/Http/Controllers/ProductImageController.php:182
 * @route '/admin/productos/{productId}/imagenes/reordenar'
 */
export const reorder = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(args, options),
    method: 'post',
})

reorder.definition = {
    methods: ["post"],
    url: '/admin/productos/{productId}/imagenes/reordenar',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProductImageController::reorder
 * @see app/Http/Controllers/ProductImageController.php:182
 * @route '/admin/productos/{productId}/imagenes/reordenar'
 */
reorder.url = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { productId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    productId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        productId: args.productId,
                }

    return reorder.definition.url
            .replace('{productId}', parsedArgs.productId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductImageController::reorder
 * @see app/Http/Controllers/ProductImageController.php:182
 * @route '/admin/productos/{productId}/imagenes/reordenar'
 */
reorder.post = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ProductImageController::reorder
 * @see app/Http/Controllers/ProductImageController.php:182
 * @route '/admin/productos/{productId}/imagenes/reordenar'
 */
    const reorderForm = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: reorder.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ProductImageController::reorder
 * @see app/Http/Controllers/ProductImageController.php:182
 * @route '/admin/productos/{productId}/imagenes/reordenar'
 */
        reorderForm.post = (args: { productId: string | number } | [productId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: reorder.url(args, options),
            method: 'post',
        })
    
    reorder.form = reorderForm
const images = {
    store: Object.assign(store, store),
setPrimary: Object.assign(setPrimary, setPrimary),
delete: Object.assign(deleteMethod, deleteMethod),
reorder: Object.assign(reorder, reorder),
}

export default images
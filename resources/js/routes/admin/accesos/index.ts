import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
 */
export const listar = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listar.url(options),
    method: 'get',
})

listar.definition = {
    methods: ["get","head"],
    url: '/admin/accesos/listar',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
 */
listar.url = (options?: RouteQueryOptions) => {
    return listar.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
 */
listar.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listar.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
 */
listar.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: listar.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
 */
    const listarForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: listar.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
 */
        listarForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: listar.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AccesosController::listar
 * @see app/Http/Controllers/AccesosController.php:0
 * @route '/admin/accesos/listar'
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
 * @route '/admin/accesos/{id}/toggle-status'
 */
export const toggleStatus = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleStatus.url(args, options),
    method: 'post',
})

toggleStatus.definition = {
    methods: ["post"],
    url: '/admin/accesos/{id}/toggle-status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/accesos/{id}/toggle-status'
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
 * @route '/admin/accesos/{id}/toggle-status'
 */
toggleStatus.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleStatus.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/accesos/{id}/toggle-status'
 */
    const toggleStatusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: toggleStatus.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AccesosController::toggleStatus
 * @see app/Http/Controllers/AccesosController.php:166
 * @route '/admin/accesos/{id}/toggle-status'
 */
        toggleStatusForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: toggleStatus.url(args, options),
            method: 'post',
        })
    
    toggleStatus.form = toggleStatusForm
/**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin/accesos'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/accesos',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin/accesos'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin/accesos'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin/accesos'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AccesosController::store
 * @see app/Http/Controllers/AccesosController.php:72
 * @route '/admin/accesos'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/accesos/{id}'
 */
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/admin/accesos/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/accesos/{id}'
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
 * @route '/admin/accesos/{id}'
 */
update.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\AccesosController::update
 * @see app/Http/Controllers/AccesosController.php:123
 * @route '/admin/accesos/{id}'
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
 * @route '/admin/accesos/{id}'
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
 * @route '/admin/accesos/{id}'
 */
export const destroy = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/accesos/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/accesos/{id}'
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
 * @route '/admin/accesos/{id}'
 */
destroy.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AccesosController::destroy
 * @see app/Http/Controllers/AccesosController.php:192
 * @route '/admin/accesos/{id}'
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
 * @route '/admin/accesos/{id}'
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
const accesos = {
    listar: Object.assign(listar, listar),
toggleStatus: Object.assign(toggleStatus, toggleStatus),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default accesos
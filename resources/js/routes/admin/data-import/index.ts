import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DataImportController::process
 * @see app/Http/Controllers/DataImportController.php:158
 * @route '/admin/data-import/process'
 */
export const process = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/admin/data-import/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataImportController::process
 * @see app/Http/Controllers/DataImportController.php:158
 * @route '/admin/data-import/process'
 */
process.url = (options?: RouteQueryOptions) => {
    return process.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataImportController::process
 * @see app/Http/Controllers/DataImportController.php:158
 * @route '/admin/data-import/process'
 */
process.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\DataImportController::process
 * @see app/Http/Controllers/DataImportController.php:158
 * @route '/admin/data-import/process'
 */
    const processForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: process.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\DataImportController::process
 * @see app/Http/Controllers/DataImportController.php:158
 * @route '/admin/data-import/process'
 */
        processForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: process.url(options),
            method: 'post',
        })
    
    process.form = processForm
/**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
export const categories = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

categories.definition = {
    methods: ["get","head"],
    url: '/admin/data-import/categories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
categories.url = (options?: RouteQueryOptions) => {
    return categories.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
categories.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
categories.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: categories.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
    const categoriesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: categories.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
        categoriesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: categories.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\DataImportController::categories
 * @see app/Http/Controllers/DataImportController.php:1380
 * @route '/admin/data-import/categories'
 */
        categoriesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: categories.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    categories.form = categoriesForm
const dataImport = {
    process: Object.assign(process, process),
categories: Object.assign(categories, categories),
}

export default dataImport
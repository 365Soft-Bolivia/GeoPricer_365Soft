import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:98
 * @route '/admin/data-reorder/analyze'
 */
export const analyze = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: analyze.url(options),
    method: 'post',
})

analyze.definition = {
    methods: ["post"],
    url: '/admin/data-reorder/analyze',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:98
 * @route '/admin/data-reorder/analyze'
 */
analyze.url = (options?: RouteQueryOptions) => {
    return analyze.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:98
 * @route '/admin/data-reorder/analyze'
 */
analyze.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: analyze.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:98
 * @route '/admin/data-reorder/analyze'
 */
    const analyzeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: analyze.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:98
 * @route '/admin/data-reorder/analyze'
 */
        analyzeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: analyze.url(options),
            method: 'post',
        })
    
    analyze.form = analyzeForm
/**
* @see \App\Http\Controllers\DataReorderController::analyzeOutsideBolivia
 * @see app/Http/Controllers/DataReorderController.php:1136
 * @route '/admin/data-reorder/analyze-outside-bolivia'
 */
export const analyzeOutsideBolivia = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: analyzeOutsideBolivia.url(options),
    method: 'post',
})

analyzeOutsideBolivia.definition = {
    methods: ["post"],
    url: '/admin/data-reorder/analyze-outside-bolivia',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataReorderController::analyzeOutsideBolivia
 * @see app/Http/Controllers/DataReorderController.php:1136
 * @route '/admin/data-reorder/analyze-outside-bolivia'
 */
analyzeOutsideBolivia.url = (options?: RouteQueryOptions) => {
    return analyzeOutsideBolivia.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataReorderController::analyzeOutsideBolivia
 * @see app/Http/Controllers/DataReorderController.php:1136
 * @route '/admin/data-reorder/analyze-outside-bolivia'
 */
analyzeOutsideBolivia.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: analyzeOutsideBolivia.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\DataReorderController::analyzeOutsideBolivia
 * @see app/Http/Controllers/DataReorderController.php:1136
 * @route '/admin/data-reorder/analyze-outside-bolivia'
 */
    const analyzeOutsideBoliviaForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: analyzeOutsideBolivia.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\DataReorderController::analyzeOutsideBolivia
 * @see app/Http/Controllers/DataReorderController.php:1136
 * @route '/admin/data-reorder/analyze-outside-bolivia'
 */
        analyzeOutsideBoliviaForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: analyzeOutsideBolivia.url(options),
            method: 'post',
        })
    
    analyzeOutsideBolivia.form = analyzeOutsideBoliviaForm
const dataReorder = {
    analyze: Object.assign(analyze, analyze),
analyzeOutsideBolivia: Object.assign(analyzeOutsideBolivia, analyzeOutsideBolivia),
}

export default dataReorder
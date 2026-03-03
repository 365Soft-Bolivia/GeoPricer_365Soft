import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:97
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
 * @see app/Http/Controllers/DataReorderController.php:97
 * @route '/admin/data-reorder/analyze'
 */
analyze.url = (options?: RouteQueryOptions) => {
    return analyze.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:97
 * @route '/admin/data-reorder/analyze'
 */
analyze.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: analyze.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:97
 * @route '/admin/data-reorder/analyze'
 */
    const analyzeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: analyze.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\DataReorderController::analyze
 * @see app/Http/Controllers/DataReorderController.php:97
 * @route '/admin/data-reorder/analyze'
 */
        analyzeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: analyze.url(options),
            method: 'post',
        })
    
    analyze.form = analyzeForm
const dataReorder = {
    analyze: Object.assign(analyze, analyze),
}

export default dataReorder
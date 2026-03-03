import { queryParams, type RouteQueryOptions, type RouteDefinition } from '@/wayfinder'

export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/two-factor',
} satisfies RouteDefinition<["get","head"]>

show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

export const enable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

enable.definition = {
    methods: ["post"],
    url: '/admin/two-factor',
} satisfies RouteDefinition<["post"]>

enable.url = (options?: RouteQueryOptions) => {
    return enable.definition.url + queryParams(options)
}

export const disable = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disable.url(options),
    method: 'delete',
})

disable.definition = {
    methods: ["delete"],
    url: '/admin/two-factor',
} satisfies RouteDefinition<["delete"]>

disable.url = (options?: RouteQueryOptions) => {
    return disable.definition.url + queryParams(options)
}

export default { show, enable, disable }

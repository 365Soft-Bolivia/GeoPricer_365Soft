import { queryParams, type RouteQueryOptions, type RouteDefinition } from '@/wayfinder'

export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/admin/settings/password',
} satisfies RouteDefinition<["get","head"]>

edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

export default { edit }

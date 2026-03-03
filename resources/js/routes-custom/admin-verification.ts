import { queryParams, type RouteQueryOptions, type RouteDefinition } from '@/wayfinder'

export const send = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

send.definition = {
    methods: ["post"],
    url: '/admin/email/verification-notification',
} satisfies RouteDefinition<["post"]>

send.url = (options?: RouteQueryOptions) => {
    return send.definition.url + queryParams(options)
}

export default { send }

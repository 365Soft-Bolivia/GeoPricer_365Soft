import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'

/**
 * Two-factor challenge (login) - store (POST)
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/two-factor-challenge',
} satisfies RouteDefinition<["post"]>

store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

const login = {
    store: Object.assign(store, store),
}

export default login

import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'

/**
 * QR code for two-factor (GET)
 */
export const qrCode = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrCode.url(options),
    method: 'get',
})

qrCode.definition = {
    methods: ["get","head"],
    url: '/user/two-factor-qr-code',
} satisfies RouteDefinition<["get","head"]>

qrCode.url = (options?: RouteQueryOptions) => {
    return qrCode.definition.url + queryParams(options)
}

/**
 * Secret key for manual setup (GET)
 */
export const secretKey = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: secretKey.url(options),
    method: 'get',
})

secretKey.definition = {
    methods: ["get","head"],
    url: '/user/two-factor-secret',
} satisfies RouteDefinition<["get","head"]>

secretKey.url = (options?: RouteQueryOptions) => {
    return secretKey.definition.url + queryParams(options)
}

/**
 * Recovery codes (GET)
 */
export const recoveryCodes = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recoveryCodes.url(options),
    method: 'get',
})

recoveryCodes.definition = {
    methods: ["get","head"],
    url: '/user/two-factor-recovery-codes',
} satisfies RouteDefinition<["get","head"]>

recoveryCodes.url = (options?: RouteQueryOptions) => {
    return recoveryCodes.definition.url + queryParams(options)
}

/**
 * Regenerate recovery codes (POST)
 */
export const regenerateRecoveryCodes = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateRecoveryCodes.url(options),
    method: 'post',
})

regenerateRecoveryCodes.definition = {
    methods: ["post"],
    url: '/user/two-factor-recovery-codes',
} satisfies RouteDefinition<["post"]>

regenerateRecoveryCodes.url = (options?: RouteQueryOptions) => {
    return regenerateRecoveryCodes.definition.url + queryParams(options)
}

export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/user/two-factor',
} satisfies RouteDefinition<["post"]>

confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

const twoFactor = {
    qrCode,
    secretKey,
    recoveryCodes,
    regenerateRecoveryCodes,
    confirm,
}

export default twoFactor

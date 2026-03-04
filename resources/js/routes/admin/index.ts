import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import register702019 from './register'
import loginDf2c2a from './login'
import password from './password'
import verification from './verification'
import profile from './profile'
import appearance from './appearance'
import twoFactor from './two-factor'
import accesos974a49 from './accesos'
import rolesF85c84 from './roles'
import ubicaciones6330a0 from './ubicaciones'
import proyectos from './proyectos'
import dataImport8a61c1 from './data-import'
import dataReorder01e44d from './data-reorder'
/**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/admin',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})
/**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

    /**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
    const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: home.url(options),
        method: 'get',
    })

            /**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
        homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: home.url(options),
            method: 'get',
        })
            /**
 * @see [serialized-closure]:2
 * @route '/admin'
 */
        homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: home.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    home.form = homeForm
/**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/admin/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})
/**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

    /**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
    const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dashboard.url(options),
        method: 'get',
    })

            /**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
        dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url(options),
            method: 'get',
        })
            /**
 * @see [serialized-closure]:2
 * @route '/admin/dashboard'
 */
        dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dashboard.form = dashboardForm
/**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
export const categorias = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categorias.url(options),
    method: 'get',
})

categorias.definition = {
    methods: ["get","head"],
    url: '/admin/categorias',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
categorias.url = (options?: RouteQueryOptions) => {
    return categorias.definition.url + queryParams(options)
}

/**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
categorias.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categorias.url(options),
    method: 'get',
})
/**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
categorias.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: categorias.url(options),
    method: 'head',
})

    /**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
    const categoriasForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: categorias.url(options),
        method: 'get',
    })

            /**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
        categoriasForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: categorias.url(options),
            method: 'get',
        })
            /**
 * @see [serialized-closure]:2
 * @route '/admin/categorias'
 */
        categoriasForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: categorias.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    categorias.form = categoriasForm
/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
export const register = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: register.url(options),
    method: 'get',
})

register.definition = {
    methods: ["get","head"],
    url: '/admin/register',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
register.url = (options?: RouteQueryOptions) => {
    return register.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
register.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: register.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
register.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: register.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
    const registerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: register.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
        registerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: register.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
 * @see app/Http/Controllers/Auth/RegisteredUserController.php:21
 * @route '/admin/register'
 */
        registerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: register.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    register.form = registerForm
/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
export const login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/admin/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
    const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: login.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
        loginForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: login.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:16
 * @route '/admin/login'
 */
        loginForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: login.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    login.form = loginForm
/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:38
 * @route '/admin/logout'
 */
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/admin/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:38
 * @route '/admin/logout'
 */
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:38
 * @route '/admin/logout'
 */
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:38
 * @route '/admin/logout'
 */
    const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: logout.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:38
 * @route '/admin/logout'
 */
        logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: logout.url(options),
            method: 'post',
        })
    
    logout.form = logoutForm
/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
export const accesos = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accesos.url(options),
    method: 'get',
})

accesos.definition = {
    methods: ["get","head"],
    url: '/admin/accesos',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
accesos.url = (options?: RouteQueryOptions) => {
    return accesos.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
accesos.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accesos.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
accesos.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: accesos.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
    const accesosForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: accesos.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
        accesosForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accesos.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AccesosController::accesos
 * @see app/Http/Controllers/AccesosController.php:14
 * @route '/admin/accesos'
 */
        accesosForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accesos.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    accesos.form = accesosForm
/**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
export const roles = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: roles.url(options),
    method: 'get',
})

roles.definition = {
    methods: ["get","head"],
    url: '/admin/roles',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
roles.url = (options?: RouteQueryOptions) => {
    return roles.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
roles.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: roles.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
roles.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: roles.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
    const rolesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: roles.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
        rolesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: roles.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\RolesController::roles
 * @see app/Http/Controllers/RolesController.php:11
 * @route '/admin/roles'
 */
        rolesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: roles.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    roles.form = rolesForm
/**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
export const ubicaciones = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ubicaciones.url(options),
    method: 'get',
})

ubicaciones.definition = {
    methods: ["get","head"],
    url: '/admin/ubicaciones',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
ubicaciones.url = (options?: RouteQueryOptions) => {
    return ubicaciones.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
ubicaciones.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ubicaciones.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
ubicaciones.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ubicaciones.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
    const ubicacionesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: ubicaciones.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
        ubicacionesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: ubicaciones.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ProductLocationController::ubicaciones
 * @see app/Http/Controllers/ProductLocationController.php:20
 * @route '/admin/ubicaciones'
 */
        ubicacionesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: ubicaciones.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    ubicaciones.form = ubicacionesForm
/**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
export const dataImport = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataImport.url(options),
    method: 'get',
})

dataImport.definition = {
    methods: ["get","head"],
    url: '/admin/data-import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
dataImport.url = (options?: RouteQueryOptions) => {
    return dataImport.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
dataImport.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataImport.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
dataImport.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dataImport.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
    const dataImportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dataImport.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
        dataImportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dataImport.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\DataImportController::dataImport
 * @see app/Http/Controllers/DataImportController.php:150
 * @route '/admin/data-import'
 */
        dataImportForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dataImport.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dataImport.form = dataImportForm
/**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
export const dataReorder = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataReorder.url(options),
    method: 'get',
})

dataReorder.definition = {
    methods: ["get","head"],
    url: '/admin/data-reorder',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
dataReorder.url = (options?: RouteQueryOptions) => {
    return dataReorder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
dataReorder.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataReorder.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
dataReorder.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dataReorder.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
    const dataReorderForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dataReorder.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
        dataReorderForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dataReorder.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\DataReorderController::dataReorder
 * @see app/Http/Controllers/DataReorderController.php:89
 * @route '/admin/data-reorder'
 */
        dataReorderForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dataReorder.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dataReorder.form = dataReorderForm
const admin = {
    home: Object.assign(home, home),
dashboard: Object.assign(dashboard, dashboard),
categorias: Object.assign(categorias, categorias),
register: Object.assign(register, register702019),
login: Object.assign(login, loginDf2c2a),
password: Object.assign(password, password),
verification: Object.assign(verification, verification),
logout: Object.assign(logout, logout),
profile: Object.assign(profile, profile),
appearance: Object.assign(appearance, appearance),
twoFactor: Object.assign(twoFactor, twoFactor),
accesos: Object.assign(accesos, accesos974a49),
roles: Object.assign(roles, rolesF85c84),
ubicaciones: Object.assign(ubicaciones, ubicaciones6330a0),
proyectos: Object.assign(proyectos, proyectos),
dataImport: Object.assign(dataImport, dataImport8a61c1),
dataReorder: Object.assign(dataReorder, dataReorder01e44d),
}

export default admin
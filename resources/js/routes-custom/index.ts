/**
 * Static route helpers — completely independent of Wayfinder.
 * Todas las rutas se definen aquí de forma estática y segura.
 */

type Method = 'get' | 'post' | 'put' | 'delete';

function makeRoute(url: string, method: Method = 'get') {
  const fn = () => url;
  fn.url = () => url;
  return fn;
}

/** GET /admin/dashboard */
export const dashboard = makeRoute('/admin/dashboard');

/** GET /admin/login */
export const login = makeRoute('/admin/login');

/** GET /admin/register */
export const register = makeRoute('/admin/register');

/** POST /admin/logout */
export const logout = makeRoute('/admin/logout', 'post');

/** GET / */
export const home = makeRoute('/');

/** GET /admin/proyectos */
export const proyectos = makeRoute('/admin/proyectos');

/** POST /admin/proyectos (crear nuevo) */
export const proyectosStore = makeRoute('/admin/proyectos', 'post');

// Sub-rutas de proyectos (funciones simples que retornan URLs)
export const proyectosShow = (id: number) => `/admin/proyectos/${id}`;
export const proyectosEdit = (id: number) => `/admin/proyectos/${id}/editar`;
export const proyectosUpdate = (id: number) => `/admin/proyectos/${id}`;
export const proyectosToggle = (id: number) => `/admin/proyectos/${id}/toggle`;
export const proyectosDestroy = (id: number) => `/admin/proyectos/${id}`;

/** GET /admin/categorias */
export const categorias = makeRoute('/admin/categorias');

/** GET /admin/accesos */
export const accesos = makeRoute('/admin/accesos');

/** GET /admin/roles */
export const roles = makeRoute('/admin/roles');

/** GET /admin/ubicaciones - Rutas completas del módulo */
export const ubicaciones = {
  // Ruta raíz
  url: () => '/admin/ubicaciones',
  
  // Sub-rutas principales - son funciones invocables
  mapa: (options?: any) => ({
    url: () => '/admin/ubicaciones/mapa',
    get: () => ({ url: '/admin/ubicaciones/mapa', method: 'get' as const }),
    form: () => ({ action: '/admin/ubicaciones/mapa', method: 'get' as const }),
  }),
  
  asignar: (options?: any) => ({
    url: () => '/admin/ubicaciones/asignar',
    get: () => ({ url: '/admin/ubicaciones/asignar', method: 'get' as const }),
    form: () => ({ action: '/admin/ubicaciones/asignar', method: 'get' as const }),
  }),
  
  editar: (options?: any) => ({
    url: () => '/admin/ubicaciones/editar',
    get: () => ({ url: '/admin/ubicaciones/editar', method: 'get' as const }),
    form: () => ({ action: '/admin/ubicaciones/editar', method: 'get' as const }),
  }),
  
  // Endpoints de API
  api: {
    listar: {
      url: () => '/admin/ubicaciones/api/listar',
      get: () => ({ url: '/admin/ubicaciones/api/listar', method: 'get' as const }),
    },
    
    store: (productId: number | { id: number }) => {
      const id = typeof productId === 'number' ? productId : productId.id;
      return {
        url: () => `/admin/ubicaciones/api/${id}`,
        form: () => ({ 
          action: `/admin/ubicaciones/api/${id}`, 
          method: 'post' as const
        }),
      };
    },
    
    show: (productId: number | { id: number }) => {
      const id = typeof productId === 'number' ? productId : productId.id;
      return {
        url: () => `/admin/ubicaciones/api/${id}/obtener`,
        get: () => ({ 
          url: `/admin/ubicaciones/api/${id}/obtener`, 
          method: 'get' as const
        }),
      };
    },
    
    toggleActive: (productId: number | { id: number }) => {
      const id = typeof productId === 'number' ? productId : productId.id;
      return {
        url: () => `/admin/ubicaciones/api/${id}/toggle-status`,
      };
    },
    
    destroy: (productId: number | { id: number }) => {
      const id = typeof productId === 'number' ? productId : productId.id;
      return {
        url: () => `/admin/ubicaciones/api/${id}`,
      };
    },
    
    nearby: {
      url: () => '/admin/ubicaciones/api/cercanos',
      form: () => ({ 
        action: '/admin/ubicaciones/api/cercanos', 
        method: 'post' as const
      }),
    },
  },
};

/** GET /admin/data-import */
export const dataImport = makeRoute('/admin/data-import');

/** GET /admin/data-reorder */
export const dataReorder = makeRoute('/admin/data-reorder');

/** Group all admin routes for navigation */
export const admin = {
  dashboard,
  proyectos,
  proyectosStore,
  proyectosShow,
  proyectosEdit,
  proyectosUpdate,
  proyectosToggle,
  proyectosDestroy,
  categorias,
  accesos: {
    listar: accesos,
  },
  roles,
  ubicaciones,
  dataImport,
  dataReorder,
};

/** Group all public routes for navigation */
export const publicRoutes = {
  home,
};
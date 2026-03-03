/**
 * Static route helpers — independent of Wayfinder-generated files.
 * These DO NOT import from @/routes or @/wayfinder because wayfinder:generate
 * wipes and rewrites those directories at build time on Railway.
 *
 * Each export is a callable function (returning the URL string) that also
 * has .url() and .form() methods, matching the Wayfinder route API.
 */

type Method = 'get' | 'post' | 'put' | 'delete';

function makeRoute(url: string, method: Method = 'get') {
  const fn = () => url;
  fn.url = () => url;
  fn.form = () => ({ action: url, method });
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
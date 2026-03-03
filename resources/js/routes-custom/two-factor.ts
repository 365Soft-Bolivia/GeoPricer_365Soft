/**
 * Static Fortify two-factor route helpers.
 * Callable functions with .url() and .form() — matching the Wayfinder API.
 */

type Method = 'get' | 'post' | 'put' | 'delete';

function makeRoute(url: string, method: Method = 'get') {
    const fn = () => url;
    fn.url = () => url;
    fn.form = () => ({ action: url, method });
    return fn;
}

/** POST /user/two-factor */
export const confirm = makeRoute('/user/two-factor', 'post');

/** POST /user/two-factor-recovery-codes */
export const regenerateRecoveryCodes = makeRoute('/user/two-factor-recovery-codes', 'post');

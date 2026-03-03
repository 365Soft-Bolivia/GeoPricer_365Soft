/**
 * Static Fortify two-factor route helpers.
 * These intentionally do NOT import from @/routes/two-factor (Wayfinder-generated)
 * because that directory is wiped and regenerated at build time on Railway,
 * and Fortify routes may not be registered during the artisan bootstrap phase.
 */

export const confirm = {
    url: () => '/user/two-factor',
    form: () => ({ action: '/user/two-factor', method: 'post' as const }),
};

export const regenerateRecoveryCodes = {
    url: () => '/user/two-factor-recovery-codes',
    form: () => ({ action: '/user/two-factor-recovery-codes', method: 'post' as const }),
};

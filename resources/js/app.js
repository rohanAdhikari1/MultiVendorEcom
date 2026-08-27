/**
 * Sitewide motion/interaction helpers, registered as reusable Alpine.js
 * components. Livewire 4 auto-starts its bundled Alpine instance (see
 * SupportAutoInjectedAssets), so `window.Alpine` is available inside the
 * `alpine:init` listener before that initial DOM walk happens — this is
 * the standard documented pattern for extending Livewire's Alpine from
 * app.js, no separate Alpine install/import needed.
 */

function prefersReducedMotion() {
    return typeof window !== 'undefined'
        && typeof window.matchMedia === 'function'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

document.addEventListener('alpine:init', () => {
    /**
     * Scroll-triggered reveal: pairs with `x-intersect.once="reveal()"` on
     * an element. Motion-sensitive users (prefers-reduced-motion) start
     * already "revealed" so they get an instant, static render instead of
     * a fade/translate — see resources/views/components/ui/reveal.blade.php.
     */
    window.Alpine.data('scrollReveal', () => ({
        revealed: prefersReducedMotion(),
        reveal() {
            this.revealed = true;
        },
    }));

    /**
     * Count-up number animation for stat tiles. Eases from 0 to `value`
     * over `duration`ms using an ease-out cubic curve. Reduced-motion
     * users skip straight to the final formatted value.
     *
     * Used by resources/views/components/ui/stat-counter.blade.php.
     */
    window.Alpine.data('statCounter', ({ value, decimals = 0, duration = 900 }) => ({
        display: '',
        format(n) {
            return n.toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        },
        start() {
            const target = Number(value) || 0;

            if (prefersReducedMotion()) {
                this.display = this.format(target);
                return;
            }

            this.display = this.format(0);
            const startTime = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.display = this.format(target * eased);

                if (progress < 1) {
                    requestAnimationFrame(tick);
                } else {
                    this.display = this.format(target);
                }
            };

            requestAnimationFrame(tick);
        },
    }));
});

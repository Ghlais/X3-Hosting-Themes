/* X3 Depth 1.3.0. Decorative progressive enhancement; no network or business state. */
(() => {
    'use strict';
    if (window.__x3DepthVersion) return;
    window.__x3DepthVersion = '1.3.0';
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const precise = window.matchMedia('(hover: hover) and (pointer: fine)');
    const key = 'x3_depth_motion';
    let optedOut = false, active = null, frame = 0, pending = null;
    try { optedOut = localStorage.getItem(key) === 'off'; } catch (_) { /* Private storage can be unavailable. */ }
    const enabled = () => !optedOut && !reduced.matches && document.body?.dataset.motion !== 'off';
    const clear = element => {
        if (!element) return;
        ['--x3-dx', '--x3-dy', '--x3-light-x', '--x3-light-y'].forEach(p => element.style.removeProperty(p));
    };
    const reset = () => {
        if (frame) cancelAnimationFrame(frame);
        frame = 0; pending = null; clear(active); active = null;
    };
    const sync = () => {
        reset();
        const on = enabled();
        document.documentElement.dataset.x3DepthMotion = on ? 'on' : 'off';
        document.querySelectorAll('[data-x3-motion-toggle]').forEach(button => {
            button.hidden = reduced.matches || document.body?.dataset.motion === 'off';
            button.textContent = on ? button.dataset.pause : button.dataset.resume;
            button.setAttribute('aria-pressed', String(!on));
        });
    };
    const surface = target => target instanceof Element ? target.closest('[data-x3-depth], .x3-category-card, .x3-product-card') : null;
    document.addEventListener('pointermove', event => {
        if (!enabled() || !precise.matches || event.pointerType !== 'mouse') return;
        const next = surface(event.target);
        if (active !== next) { clear(active); active = next; }
        if (!active) return;
        const box = active.getBoundingClientRect();
        if (!box.width || !box.height) return;
        pending = { element:active, x:Math.max(-1, Math.min(1, (event.clientX-box.left)/box.width*2-1)), y:Math.max(-1, Math.min(1, (event.clientY-box.top)/box.height*2-1)) };
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            if (!pending || !enabled()) return;
            const {element, x, y} = pending;
            const tilt = element.dataset.x3Depth === 'rack' ? 5 : 1.6;
            element.style.setProperty('--x3-dx', `${(x*tilt).toFixed(2)}deg`);
            element.style.setProperty('--x3-dy', `${(-y*tilt).toFixed(2)}deg`);
            element.style.setProperty('--x3-light-x', `${((x+1)*50).toFixed(1)}%`);
            element.style.setProperty('--x3-light-y', `${((y+1)*50).toFixed(1)}%`);
        });
    }, {passive:true});
    document.addEventListener('pointerout', event => { if (active && (!(event.relatedTarget instanceof Node) || !active.contains(event.relatedTarget))) reset(); }, {passive:true});
    document.addEventListener('click', event => {
        const button = event.target instanceof Element ? event.target.closest('[data-x3-motion-toggle]') : null;
        if (!button) return;
        optedOut = !optedOut;
        try { localStorage.setItem(key, optedOut ? 'off' : 'on'); } catch (_) { /* Optional preference only. */ }
        sync();
    });
    window.addEventListener('blur', reset);
    window.addEventListener('storage', event => { if (event.key === key) { optedOut = event.newValue === 'off'; sync(); } });
    document.addEventListener('visibilitychange', () => { if (document.hidden) reset(); });
    reduced.addEventListener('change', sync);
    precise.addEventListener('change', sync);
    document.addEventListener('livewire:navigating', reset);
    document.addEventListener('livewire:navigated', sync);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', sync, {once:true});
    else sync();
})();

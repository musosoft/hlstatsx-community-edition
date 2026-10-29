/**
 * Bootstrap HLStatsX JS
 *
 * Minimal bootstrap – no axios since this app uses blade + fetch API.
 */

// Expose jQuery-free helpers if needed in the future.
window.hlstatsReady = (fn) => {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
};

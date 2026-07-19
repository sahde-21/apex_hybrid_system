/**
 * SCF PWA bootstrap — registers the service worker and surfaces install/update UX.
 * Livewire HTML and auth endpoints are never cache-first (handled in sw.js).
 */

import Chart from 'chart.js/auto';

const isBrowser = typeof window !== 'undefined';

if (isBrowser) {
    window.Chart = Chart;

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('biChart', (config = {}) => ({
            chart: null,
            config,
            render() {
                if (! this.$refs.canvas || ! window.Chart) {
                    return;
                }

                if (this.chart) {
                    this.chart.destroy();
                }

                const type = this.config.type === 'area' ? 'line' : (this.config.type || 'bar');
                const datasets = (this.config.datasets || []).map((dataset) => ({
                    ...dataset,
                    backgroundColor: dataset.backgroundColor || [
                        'rgba(14, 165, 233, 0.55)',
                        'rgba(16, 185, 129, 0.55)',
                        'rgba(245, 158, 11, 0.55)',
                        'rgba(244, 63, 94, 0.55)',
                        'rgba(99, 102, 241, 0.55)',
                        'rgba(20, 184, 166, 0.55)',
                        'rgba(168, 85, 247, 0.55)',
                        'rgba(100, 116, 139, 0.55)',
                    ],
                    borderColor: dataset.borderColor || 'rgb(14, 165, 233)',
                    borderWidth: 2,
                }));

                this.chart = new window.Chart(this.$refs.canvas.getContext('2d'), {
                    type,
                    data: {
                        labels: this.config.labels || [],
                        datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                        scales: ['doughnut', 'pie', 'polarArea'].includes(type)
                            ? {}
                            : {
                                y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.2)' } },
                                x: { grid: { display: false } },
                            },
                    },
                });
            },
            destroy() {
                this.chart?.destroy();
            },
        }));
    });
}

function canUseServiceWorker() {
    return isBrowser
        && 'serviceWorker' in navigator
        && window.isSecureContext;
}

async function registerServiceWorker() {
    if (! canUseServiceWorker()) {
        return null;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/',
            updateViaCache: 'none',
        });

        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;
            if (! worker) {
                return;
            }

            worker.addEventListener('statechange', () => {
                if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                    window.dispatchEvent(new CustomEvent('scf:pwa-update-available', {
                        detail: { registration },
                    }));
                }
            });
        });

        // Periodic update checks (Google guidance: check regularly while app is open)
        setInterval(() => {
            registration.update().catch(() => {});
        }, 60 * 60 * 1000);

        return registration;
    } catch (error) {
        console.warn('[SCF PWA] Service worker registration failed', error);
        return null;
    }
}

function bindInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.__scfDeferredInstallPrompt = event;
        window.dispatchEvent(new CustomEvent('scf:pwa-install-available', {
            detail: { prompt: event },
        }));
    });

    window.addEventListener('appinstalled', () => {
        window.__scfDeferredInstallPrompt = null;
        window.dispatchEvent(new CustomEvent('scf:pwa-installed'));
    });
}

function bindUpdateReload() {
    let refreshing = false;

    navigator.serviceWorker?.addEventListener('controllerchange', () => {
        if (refreshing) {
            return;
        }
        refreshing = true;
        window.location.reload();
    });

    window.addEventListener('scf:pwa-apply-update', (event) => {
        const registration = event.detail?.registration;
        const waiting = registration?.waiting;
        if (waiting) {
            waiting.postMessage({ type: 'SKIP_WAITING' });
        }
    });
}

function enhanceTouchTargets() {
    document.documentElement.classList.add('scf-pwa');

    if (window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true) {
        document.documentElement.classList.add('scf-pwa-installed');
    }
}

if (isBrowser) {
    bindInstallPrompt();
    enhanceTouchTargets();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            registerServiceWorker();
            bindUpdateReload();
        });
    } else {
        registerServiceWorker();
        bindUpdateReload();
    }
}

export async function promptPwaInstall() {
    const deferred = window.__scfDeferredInstallPrompt;
    if (! deferred) {
        return false;
    }

    deferred.prompt();
    const choice = await deferred.userChoice;
    window.__scfDeferredInstallPrompt = null;

    return choice.outcome === 'accepted';
}

window.scfPwa = {
    promptInstall: promptPwaInstall,
};

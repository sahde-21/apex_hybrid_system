@props([
    'compact' => false,
])

<div
    x-data="{
        canInstall: false,
        updateAvailable: false,
        registration: null,
        installed: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
        init() {
            window.addEventListener('scf:pwa-install-available', () => { this.canInstall = true });
            window.addEventListener('scf:pwa-installed', () => { this.canInstall = false; this.installed = true });
            window.addEventListener('scf:pwa-update-available', (e) => {
                this.updateAvailable = true;
                this.registration = e.detail.registration;
            });
            if (window.__scfDeferredInstallPrompt) {
                this.canInstall = true;
            }
        },
        async install() {
            if (window.scfPwa?.promptInstall) {
                await window.scfPwa.promptInstall();
            }
            this.canInstall = false;
        },
        applyUpdate() {
            window.dispatchEvent(new CustomEvent('scf:pwa-apply-update', {
                detail: { registration: this.registration },
            }));
        }
    }"
    class="contents"
>
    <template x-if="canInstall && !installed">
        <div @class([
            'flex items-center gap-2',
            'rounded-xl border border-sky-500/30 bg-sky-500/10 px-3 py-2 text-sm text-sky-100' => ! $compact,
        ])>
            <flux:button size="sm" variant="primary" icon="arrow-down-tray" x-on:click="install()">
                {{ __('Install app') }}
            </flux:button>
            @unless ($compact)
                <span class="hidden text-xs text-sky-200/80 sm:inline">{{ __('Add SCF to your home screen') }}</span>
            @endunless
        </div>
    </template>

    <template x-if="updateAvailable">
        <div class="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-100">
            <span>{{ __('Update available') }}</span>
            <flux:button size="sm" variant="filled" x-on:click="applyUpdate()">
                {{ __('Refresh') }}
            </flux:button>
        </div>
    </template>
</div>

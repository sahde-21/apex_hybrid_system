<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950 lg:flex">
        <flux:sidebar sticky collapsible="mobile" class="scf-sidebar border-e border-zinc-200/90 bg-white/95 dark:border-zinc-800 dark:bg-zinc-950/95">
            <flux:sidebar.header class="border-b border-zinc-100 px-2 dark:border-zinc-800/80">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('scf.platform')" class="grid">
                    @can('dashboard.read')
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('scf.dashboard') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.sales')" class="grid">
                    @if (Route::has('pos.terminal') && \App\Support\Navigation::canAccessRoute('pos.terminal'))
                        <flux:sidebar.item icon="calculator" :href="route('pos.terminal')" :current="request()->routeIs('pos.*')" wire:navigate>
                            {{ __('scf.pos') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('sale-orders.index') && \App\Support\Navigation::canAccessRoute('sale-orders.index'))
                        <flux:sidebar.item icon="shopping-bag" :href="route('sale-orders.index')" :current="request()->routeIs('sale-orders.*')" wire:navigate>
                            {{ __('scf.sale_orders') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('quotations.index') && \App\Support\Navigation::canAccessRoute('quotations.index'))
                        <flux:sidebar.item icon="document-duplicate" :href="route('quotations.index')" :current="request()->routeIs('quotations.*')" wire:navigate>
                            {{ __('scf.quotations') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('invoices.index') && \App\Support\Navigation::canAccessRoute('invoices.index'))
                        <flux:sidebar.item icon="receipt-percent" :href="route('invoices.index')" :current="request()->routeIs('invoices.*')" wire:navigate>
                            {{ __('scf.invoices') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.purchasing')" class="grid">
                    @if (Route::has('purchase-orders.index') && \App\Support\Navigation::canAccessRoute('purchase-orders.index'))
                        <flux:sidebar.item icon="truck" :href="route('purchase-orders.index')" :current="request()->routeIs('purchase-orders.*')" wire:navigate>
                            {{ __('scf.purchase_orders') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('bills.index') && \App\Support\Navigation::canAccessRoute('bills.index'))
                        <flux:sidebar.item icon="document-text" :href="route('bills.index')" :current="request()->routeIs('bills.*')" wire:navigate>
                            {{ __('scf.bills') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('supplier-evaluations.index') && \App\Support\Navigation::canAccessRoute('supplier-evaluations.index'))
                        <flux:sidebar.item icon="star" :href="route('supplier-evaluations.index')" :current="request()->routeIs('supplier-evaluations.*')" wire:navigate>
                            {{ __('scf.supplier_evaluations') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.inventory')" class="grid">
                    @if (Route::has('products.index') && \App\Support\Navigation::canAccessRoute('products.index'))
                        <flux:sidebar.item icon="cube" :href="route('products.index')" :current="request()->routeIs('products.*')" wire:navigate>
                            {{ __('scf.products') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('variants.index') && \App\Support\Navigation::canAccessRoute('variants.index'))
                        <flux:sidebar.item icon="squares-2x2" :href="route('variants.index')" :current="request()->routeIs('variants.*')" wire:navigate>
                            {{ __('scf.variants') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('price-lists.index') && \App\Support\Navigation::canAccessRoute('price-lists.index'))
                        <flux:sidebar.item icon="tag" :href="route('price-lists.index')" :current="request()->routeIs('price-lists.*')" wire:navigate>
                            {{ __('scf.price_lists') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('warehouses.index') && \App\Support\Navigation::canAccessRoute('warehouses.index'))
                        <flux:sidebar.item icon="building-storefront" :href="route('warehouses.index')" :current="request()->routeIs('warehouses.*')" wire:navigate>
                            {{ __('scf.warehouses') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('inventory-adjustments.index') && \App\Support\Navigation::canAccessRoute('inventory-adjustments.index'))
                        <flux:sidebar.item icon="adjustments-horizontal" :href="route('inventory-adjustments.index')" :current="request()->routeIs('inventory-adjustments.*')" wire:navigate>
                            {{ __('scf.inventory_adjustments') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.warehouse')" class="grid">
                    @if (Route::has('shipping-methods.index') && \App\Support\Navigation::canAccessRoute('shipping-methods.index'))
                        <flux:sidebar.item icon="truck" :href="route('shipping-methods.index')" :current="request()->routeIs('shipping-methods.*')" wire:navigate>
                            {{ __('scf.shipping_methods') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('delivery-trips.index') && \App\Support\Navigation::canAccessRoute('delivery-trips.index'))
                        <flux:sidebar.item icon="map" :href="route('delivery-trips.index')" :current="request()->routeIs('delivery-trips.*')" wire:navigate>
                            {{ __('scf.delivery_trips') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('stock-transfers.index') && \App\Support\Navigation::canAccessRoute('stock-transfers.index'))
                        <flux:sidebar.item icon="arrows-right-left" :href="route('stock-transfers.index')" :current="request()->routeIs('stock-transfers.*')" wire:navigate>
                            {{ __('scf.stock_transfers') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('floor-plans.index') && \App\Support\Navigation::canAccessRoute('floor-plans.index'))
                        <flux:sidebar.item icon="map-pin" :href="route('floor-plans.index')" :current="request()->routeIs('floor-plans.*')" wire:navigate>
                            {{ __('scf.floor_plans') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('vehicle-maintenance.index') && \App\Support\Navigation::canAccessRoute('vehicle-maintenance.index'))
                        <flux:sidebar.item icon="wrench-screwdriver" :href="route('vehicle-maintenance.index')" :current="request()->routeIs('vehicle-maintenance.*')" wire:navigate>
                            {{ __('scf.vehicle_maintenance') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.manufacturing')" class="grid">
                    @if (Route::has('production-orders.index') && \App\Support\Navigation::canAccessRoute('production-orders.index'))
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('production-orders.index')" :current="request()->routeIs('production-orders.*')" wire:navigate>
                            {{ __('scf.production_orders') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('bill-of-materials.index') && \App\Support\Navigation::canAccessRoute('bill-of-materials.index'))
                        <flux:sidebar.item icon="list-bullet" :href="route('bill-of-materials.index')" :current="request()->routeIs('bill-of-materials.*')" wire:navigate>
                            {{ __('scf.bill_of_materials') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('quality-controls.index') && \App\Support\Navigation::canAccessRoute('quality-controls.index'))
                        <flux:sidebar.item icon="shield-check" :href="route('quality-controls.index')" :current="request()->routeIs('quality-controls.*')" wire:navigate>
                            {{ __('scf.quality_control') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.crm')" class="grid">
                    @if (Route::has('leads.index') && \App\Support\Navigation::canAccessRoute('leads.index'))
                        <flux:sidebar.item icon="funnel" :href="route('leads.index')" :current="request()->routeIs('leads.*')" wire:navigate>
                            {{ __('scf.lead_management') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('customer-feedback.index') && \App\Support\Navigation::canAccessRoute('customer-feedback.index'))
                        <flux:sidebar.item icon="hand-thumb-up" :href="route('customer-feedback.index')" :current="request()->routeIs('customer-feedback.*')" wire:navigate>
                            {{ __('scf.customer_feedback') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('crm-interactions.index') && \App\Support\Navigation::canAccessRoute('crm-interactions.index'))
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('crm-interactions.index')" :current="request()->routeIs('crm-interactions.*')" wire:navigate>
                            {{ __('scf.crm_interactions') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.hr')" class="grid">
                    @if (Route::has('employees.index') && \App\Support\Navigation::canAccessRoute('employees.index'))
                        <flux:sidebar.item icon="identification" :href="route('employees.index')" :current="request()->routeIs('employees.*')" wire:navigate>
                            {{ __('scf.employees') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('payrolls.index') && \App\Support\Navigation::canAccessRoute('payrolls.index'))
                        <flux:sidebar.item icon="currency-dollar" :href="route('payrolls.index')" :current="request()->routeIs('payrolls.*')" wire:navigate>
                            {{ __('scf.payroll') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('attendance.index') && \App\Support\Navigation::canAccessRoute('attendance.index'))
                        <flux:sidebar.item icon="clock" :href="route('attendance.index')" :current="request()->routeIs('attendance.*')" wire:navigate>
                            {{ __('scf.attendance') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('leave-requests.index') && \App\Support\Navigation::canAccessRoute('leave-requests.index'))
                        <flux:sidebar.item icon="calendar-days" :href="route('leave-requests.index')" :current="request()->routeIs('leave-requests.*')" wire:navigate>
                            {{ __('scf.leave_requests') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('shifts.index') && \App\Support\Navigation::canAccessRoute('shifts.index'))
                        <flux:sidebar.item icon="arrow-path" :href="route('shifts.index')" :current="request()->routeIs('shifts.*')" wire:navigate>
                            {{ __('scf.shift_management') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('performance-reviews.index') && \App\Support\Navigation::canAccessRoute('performance-reviews.index'))
                        <flux:sidebar.item icon="star" :href="route('performance-reviews.index')" :current="request()->routeIs('performance-reviews.*')" wire:navigate>
                            {{ __('scf.performance_reviews') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.accounting')" class="grid">
                    @if (Route::has('chart-of-accounts.index') && \App\Support\Navigation::canAccessRoute('chart-of-accounts.index'))
                        <flux:sidebar.item icon="queue-list" :href="route('chart-of-accounts.index')" :current="request()->routeIs('chart-of-accounts.*')" wire:navigate>
                            {{ __('scf.accounting_engine.coa_title') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('fiscal-periods.index') && \App\Support\Navigation::canAccessRoute('fiscal-periods.index'))
                        <flux:sidebar.item icon="calendar-days" :href="route('fiscal-periods.index')" :current="request()->routeIs('fiscal-periods.*')" wire:navigate>
                            {{ __('scf.accounting_engine.fiscal_periods_title') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('currencies.index') && \App\Support\Navigation::canAccessRoute('currencies.index'))
                        <flux:sidebar.item icon="currency-dollar" :href="route('currencies.index')" :current="request()->routeIs('currencies.*')" wire:navigate>
                            {{ __('scf.accounting_engine.currencies_title') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('expenses.index') && \App\Support\Navigation::canAccessRoute('expenses.index'))
                        <flux:sidebar.item icon="banknotes" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')" wire:navigate>
                            {{ __('scf.expenses') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('journal-entries.index') && \App\Support\Navigation::canAccessRoute('journal-entries.index'))
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('journal-entries.index')" :current="request()->routeIs('journal-entries.*')" wire:navigate>
                            {{ __('scf.journal_entries') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('ledger.index') && \App\Support\Navigation::canAccessRoute('ledger.index'))
                        <flux:sidebar.item icon="book-open" :href="route('ledger.index')" :current="request()->routeIs('ledger.*')" wire:navigate>
                            {{ __('scf.accounting_engine.ledger_title') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('statements.index') && \App\Support\Navigation::canAccessRoute('statements.index'))
                        <flux:sidebar.item icon="presentation-chart-bar" :href="route('statements.index')" :current="request()->routeIs('statements.*')" wire:navigate>
                            {{ __('scf.accounting_engine.statements_title') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('payments.index') && \App\Support\Navigation::canAccessRoute('payments.index'))
                        <flux:sidebar.item icon="credit-card" :href="route('payments.index')" :current="request()->routeIs('payments.*')" wire:navigate>
                            {{ __('scf.payments') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('tax-rates.index') && \App\Support\Navigation::canAccessRoute('tax-rates.index'))
                        <flux:sidebar.item icon="calculator" :href="route('tax-rates.index')" :current="request()->routeIs('tax-rates.*')" wire:navigate>
                            {{ __('scf.tax_rates') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('fixed-assets.index') && \App\Support\Navigation::canAccessRoute('fixed-assets.index'))
                        <flux:sidebar.item icon="building-library" :href="route('fixed-assets.index')" :current="request()->routeIs('fixed-assets.*')" wire:navigate>
                            {{ __('scf.fixed_assets') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('budgets.index') && \App\Support\Navigation::canAccessRoute('budgets.index'))
                        <flux:sidebar.item icon="chart-pie" :href="route('budgets.index')" :current="request()->routeIs('budgets.*')" wire:navigate>
                            {{ __('scf.budgeting') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('bank-reconciliations.index') && \App\Support\Navigation::canAccessRoute('bank-reconciliations.index'))
                        <flux:sidebar.item icon="building-office-2" :href="route('bank-reconciliations.index')" :current="request()->routeIs('bank-reconciliations.*')" wire:navigate>
                            {{ __('scf.bank_reconciliation') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('financial-reports.index') && \App\Support\Navigation::canAccessRoute('financial-reports.index'))
                        <flux:sidebar.item icon="chart-bar" :href="route('financial-reports.index')" :current="request()->routeIs('financial-reports.*')" wire:navigate>
                            {{ __('scf.financial_reports') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.projects')" class="grid">
                    @if (Route::has('contracts.index') && \App\Support\Navigation::canAccessRoute('contracts.index'))
                        <flux:sidebar.item icon="document-check" :href="route('contracts.index')" :current="request()->routeIs('contracts.*')" wire:navigate>
                            {{ __('scf.contracts') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('project-tasks.index') && \App\Support\Navigation::canAccessRoute('project-tasks.index'))
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('project-tasks.index')" :current="request()->routeIs('project-tasks.*')" wire:navigate>
                            {{ __('scf.project_tasks') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('time-logs.index') && \App\Support\Navigation::canAccessRoute('time-logs.index'))
                        <flux:sidebar.item icon="clock" :href="route('time-logs.index')" :current="request()->routeIs('time-logs.*')" wire:navigate>
                            {{ __('scf.time_logs') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.marketing')" class="grid">
                    @if (Route::has('campaigns.index') && \App\Support\Navigation::canAccessRoute('campaigns.index'))
                        <flux:sidebar.item icon="megaphone" :href="route('campaigns.index')" :current="request()->routeIs('campaigns.*')" wire:navigate>
                            {{ __('scf.campaigns') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('loyalty-programs.index') && \App\Support\Navigation::canAccessRoute('loyalty-programs.index'))
                        <flux:sidebar.item icon="gift" :href="route('loyalty-programs.index')" :current="request()->routeIs('loyalty-programs.*')" wire:navigate>
                            {{ __('scf.loyalty_programs') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('coupons.index') && \App\Support\Navigation::canAccessRoute('coupons.index'))
                        <flux:sidebar.item icon="ticket" :href="route('coupons.index')" :current="request()->routeIs('coupons.*')" wire:navigate>
                            {{ __('scf.coupons') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('gift-cards.index') && \App\Support\Navigation::canAccessRoute('gift-cards.index'))
                        <flux:sidebar.item icon="credit-card" :href="route('gift-cards.index')" :current="request()->routeIs('gift-cards.*')" wire:navigate>
                            {{ __('scf.gift_cards') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('subscriptions.index') && \App\Support\Navigation::canAccessRoute('subscriptions.index'))
                        <flux:sidebar.item icon="arrow-path-rounded-square" :href="route('subscriptions.index')" :current="request()->routeIs('subscriptions.*')" wire:navigate>
                            {{ __('scf.subscriptions') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.support')" class="grid">
                    @if (Route::has('tickets.index') && \App\Support\Navigation::canAccessRoute('tickets.index'))
                        <flux:sidebar.item icon="lifebuoy" :href="route('tickets.index')" :current="request()->routeIs('tickets.*')" wire:navigate>
                            {{ __('scf.tickets') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('knowledge-base.index') && \App\Support\Navigation::canAccessRoute('knowledge-base.index'))
                        <flux:sidebar.item icon="book-open" :href="route('knowledge-base.index')" :current="request()->routeIs('knowledge-base.*')" wire:navigate>
                            {{ __('scf.knowledge_base') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.reports')" class="grid">
                    @if (Route::has('analytics.hub') && \App\Support\Navigation::canAccessRoute('analytics.hub'))
                        <flux:sidebar.item icon="presentation-chart-line" :href="route('analytics.hub')" :current="request()->routeIs('analytics.*')" wire:navigate>
                            {{ __('scf.bi.brand') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('financial-reports.index') && \App\Support\Navigation::canAccessRoute('financial-reports.index'))
                        <flux:sidebar.item icon="chart-bar-square" :href="route('financial-reports.index')" :current="request()->routeIs('financial-reports.*')" wire:navigate>
                            {{ __('scf.financial_reports') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.administration')" class="grid">
                    @if (Route::has('documents.index') && \App\Support\Navigation::canAccessRoute('documents.index'))
                        <flux:sidebar.item icon="folder-open" :href="route('documents.index')" :current="request()->routeIs('documents.*')" wire:navigate>
                            {{ __('scf.dms.brand') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('users.index') && \App\Support\Navigation::canAccessRoute('users.index'))
                        <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('scf.users') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('branches.index') && \App\Support\Navigation::canAccessRoute('branches.index'))
                        <flux:sidebar.item icon="building-office" :href="route('branches.index')" :current="request()->routeIs('branches.*')" wire:navigate>
                            {{ __('scf.branches') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('notifications.index') && \App\Support\Navigation::canAccessRoute('notifications.index'))
                        <flux:sidebar.item icon="bell-alert" :href="route('notifications.index')" :current="request()->routeIs('notifications.*')" wire:navigate>
                            {{ __('scf.notification_center.title') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('notification-templates.index') && \App\Support\Navigation::canAccessRoute('notification-templates.index'))
                        <flux:sidebar.item icon="bell" :href="route('notification-templates.index')" :current="request()->routeIs('notification-templates.*')" wire:navigate>
                            {{ __('scf.notification_templates') }}
                        </flux:sidebar.item>
                    @endif
                    @if (Route::has('audit-logs.index') && \App\Support\Navigation::canAccessRoute('audit-logs.index'))
                        <flux:sidebar.item icon="shield-exclamation" :href="route('audit-logs.index')" :current="request()->routeIs('audit-logs.*')" wire:navigate>
                            {{ __('scf.audit_logs') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('scf.contacts')" class="grid">
                    @if (Route::has('contacts.index') && \App\Support\Navigation::canAccessRoute('contacts.index'))
                        <flux:sidebar.item icon="users" :href="route('contacts.index')" :current="request()->routeIs('contacts.*')" wire:navigate>
                            {{ __('scf.contacts') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="space-y-2 border-t border-zinc-100 px-3 pb-2 pt-3 dark:border-zinc-800/80">
                <x-pwa-install-banner />
            </div>

            <flux:sidebar.nav class="pb-1">
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*') || request()->routeIs('appearance.*') || request()->routeIs('security.*')" wire:navigate>
                    {{ __('scf.settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <div class="hidden border-t border-zinc-100 p-2 dark:border-zinc-800/80 lg:block">
                <x-desktop-user-menu :name="auth()->user()->name" />
            </div>
        </flux:sidebar>

        <div class="min-w-0 flex-1">
            <header class="scf-topbar">
                <div class="scf-topbar-inner">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-3" inset="left" />

                    <div class="hidden min-w-0 lg:block">
                        <p class="truncate text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                            {{ $title ?? __('scf.dashboard') }}
                        </p>
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                            {{ config('pwa.name', config('app.name')) }}
                        </p>
                    </div>

                    <div class="ms-auto flex items-center gap-1.5 sm:gap-2">
                        <x-language-switcher />

                        @if (auth()->check() && Route::has('notifications.index') && \App\Support\Navigation::canAccessRoute('notifications.index'))
                            <livewire:notification-bell />
                        @endif

                        @if (Route::has('appearance.edit'))
                            <flux:button
                                :href="route('appearance.edit')"
                                variant="ghost"
                                size="sm"
                                icon="swatch"
                                class="hidden sm:inline-flex"
                                wire:navigate
                            >
                                {{ __('Appearance') }}
                            </flux:button>
                        @endif

                        <flux:dropdown position="bottom" align="end" class="lg:hidden">
                            <flux:profile
                                :initials="auth()->user()->initials()"
                                icon-trailing="chevron-down"
                                class="rounded-full ring-1 ring-zinc-200 dark:ring-zinc-700"
                            />

                            <flux:menu class="min-w-60">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>

                                <flux:menu.separator />

                                <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>
                                    {{ __('scf.settings') }}
                                </flux:menu.item>

                                <flux:menu.separator />

                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item
                                        as="button"
                                        type="submit"
                                        icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer"
                                        data-test="logout-button"
                                    >
                                        {{ __('scf.log_out') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </header>

            {{ $slot }}
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

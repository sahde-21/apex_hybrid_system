<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('scf.landing.brand') }} — {{ __('scf.landing.hero_title') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <meta name="theme-color" content="#020617">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SCF">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Syne:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --scf-navy: #020617;
            --scf-deep: #07111f;
            --scf-panel: rgba(15, 23, 42, 0.55);
            --scf-blue: #2563eb;
            --scf-sky: #38bdf8;
            --scf-mist: #94a3b8;
            --scf-text: #e2e8f0;
            --scf-display: 'Syne', ui-sans-serif, system-ui, sans-serif;
            --scf-body: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
        }
        .scf-landing { font-family: var(--scf-body); color: var(--scf-text); background: var(--scf-navy); }
        .scf-landing h1, .scf-landing h2, .scf-landing h3, .scf-brand { font-family: var(--scf-display); }
        .scf-hero-bg {
            background:
                radial-gradient(ellipse 90% 55% at 50% -15%, rgba(37, 99, 235, 0.55), transparent 55%),
                radial-gradient(ellipse 45% 40% at 85% 15%, rgba(56, 189, 248, 0.18), transparent 50%),
                radial-gradient(ellipse 40% 45% at 8% 70%, rgba(14, 165, 233, 0.12), transparent 55%),
                linear-gradient(165deg, #020617 0%, #07111f 45%, #020617 100%);
            animation: scf-gradient-shift 20s ease-in-out infinite alternate;
        }
        @keyframes scf-gradient-shift {
            from { filter: hue-rotate(0deg); }
            to { filter: hue-rotate(12deg); }
        }
        @keyframes scf-fade-up {
            from { opacity: 0; transform: translateY(28px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scf-float {
            from { transform: translateY(0); }
            to { transform: translateY(-10px); }
        }
        .scf-fade-up { animation: scf-fade-up 0.85s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .scf-d1 { animation-delay: 0.1s; }
        .scf-d2 { animation-delay: 0.22s; }
        .scf-d3 { animation-delay: 0.34s; }
        .scf-d4 { animation-delay: 0.46s; }
        .scf-grid-glow {
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.05) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 35%, #000 15%, transparent 75%);
        }
        .scf-glass {
            background: linear-gradient(145deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
            border: 1px solid rgba(148, 163, 184, 0.16);
            backdrop-filter: blur(18px);
            box-shadow: 0 20px 50px rgba(2, 6, 23, 0.45);
        }
        .scf-glass:hover {
            border-color: rgba(56, 189, 248, 0.35);
            transform: translateY(-3px);
            transition: border-color 0.25s ease, transform 0.25s ease;
        }
        .scf-module {
            border: 1px solid rgba(148, 163, 184, 0.12);
            background: rgba(15, 39, 68, 0.4);
            transition: border-color 0.25s ease, background 0.25s ease, transform 0.25s ease;
        }
        .scf-module:hover {
            border-color: rgba(56, 189, 248, 0.4);
            background: rgba(37, 99, 235, 0.18);
            transform: translateY(-2px);
        }
        .scf-price-featured {
            border-color: rgba(56, 189, 248, 0.5) !important;
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.2), 0 28px 70px rgba(2, 6, 23, 0.55);
        }
        .scf-preview {
            background:
                linear-gradient(160deg, rgba(15, 39, 68, 0.95), rgba(2, 6, 23, 0.98)),
                radial-gradient(circle at 28% 18%, rgba(37, 99, 235, 0.4), transparent 50%);
            border: 1px solid rgba(148, 163, 184, 0.18);
            animation: scf-float 6s ease-in-out infinite alternate;
        }
        .scf-btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.35);
        }
        .scf-btn-primary:hover { filter: brightness(1.08); }
        .scf-btn-ghost {
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.04);
        }
        .scf-btn-ghost:hover { border-color: rgba(56,189,248,0.45); background: rgba(255,255,255,0.08); }
        .scf-nav-link { color: var(--scf-mist); }
        .scf-nav-link:hover { color: #fff; }
        @media (prefers-reduced-motion: reduce) {
            .scf-hero-bg, .scf-fade-up, .scf-preview { animation: none !important; }
        }
    </style>
</head>
<body class="scf-landing antialiased">
@php
    $modules = array_keys(__('scf.modules'));
    $industries = ['industry_retail','industry_manufacturing','industry_distribution','industry_services','industry_hospitality','industry_healthcare'];
    $previews = ['preview_sales','preview_inventory','preview_accounting','preview_hr','preview_support','preview_crm'];
    $features = [1,2,3,4,5,6];
@endphp

{{-- Nav --}}
<header class="fixed inset-x-0 top-0 z-50 border-b border-white/5 bg-slate-950/75 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="scf-brand flex items-baseline gap-2 text-lg font-bold tracking-tight text-white sm:text-xl">
            <span>{{ __('scf.landing.brand') }}</span>
            <span class="rounded-md bg-sky-500/15 px-1.5 py-0.5 text-xs font-semibold text-sky-300">{{ __('scf.landing.brand_short') }}</span>
        </a>
        <nav class="hidden items-center gap-6 text-sm lg:flex">
            <a href="#features" class="scf-nav-link">{{ __('scf.landing.nav_features') }}</a>
            <a href="#industries" class="scf-nav-link">{{ __('scf.landing.nav_industries') }}</a>
            <a href="#modules" class="scf-nav-link">{{ __('scf.landing.nav_modules') }}</a>
            <a href="#screenshots" class="scf-nav-link">{{ __('scf.landing.nav_screenshots') }}</a>
            <a href="#pricing" class="scf-nav-link">{{ __('scf.landing.nav_pricing') }}</a>
            <a href="#faq" class="scf-nav-link">{{ __('scf.landing.nav_faq') }}</a>
            <a href="#contact" class="scf-nav-link">{{ __('scf.landing.nav_contact') }}</a>
        </nav>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="hidden items-center gap-1 rounded-lg border border-white/10 bg-white/5 p-0.5 text-xs sm:flex">
                @foreach (['en' => 'EN', 'ar' => 'AR', 'ckb' => 'CKB'] as $code => $label)
                    <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
                       class="rounded-md px-2 py-1 {{ app()->getLocale() === $code ? 'bg-sky-500/20 text-sky-300' : 'text-slate-400 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="scf-btn-primary rounded-xl px-4 py-2 text-sm font-semibold text-white">{{ __('scf.landing.dashboard') }}</a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="hidden text-sm text-slate-300 hover:text-white sm:inline">{{ __('scf.landing.login') }}</a>
                    <a href="{{ route('login') }}" class="scf-btn-primary rounded-xl px-4 py-2 text-sm font-semibold text-white">{{ __('scf.landing.cta_primary') }}</a>
                @endif
            @endauth
        </div>
    </div>
</header>

{{-- Hero --}}
<section class="scf-hero-bg relative overflow-hidden pt-24">
    <div class="scf-grid-glow pointer-events-none absolute inset-0"></div>
    <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-24">
        <div>
            <span class="scf-fade-up inline-flex items-center gap-2 rounded-full border border-sky-400/30 bg-sky-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-sky-300">
                {{ __('scf.landing.hero_badge') }}
            </span>
            <p class="scf-brand scf-fade-up scf-d1 mt-6 text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                {{ __('scf.landing.brand') }}
            </p>
            <h1 class="scf-fade-up scf-d2 mt-5 max-w-xl text-2xl font-semibold leading-tight text-sky-100 sm:text-4xl">
                {{ __('scf.landing.hero_title') }}
            </h1>
            <p class="scf-fade-up scf-d3 mt-5 max-w-xl text-base leading-relaxed text-slate-400 sm:text-lg">
                {{ __('scf.landing.hero_subtitle') }}
            </p>
            <div class="scf-fade-up scf-d4 mt-10 flex flex-wrap items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="scf-btn-primary rounded-xl px-6 py-3 text-sm font-semibold text-white">{{ __('scf.landing.dashboard') }}</a>
                @else
                    <a href="{{ Route::has('login') ? route('login') : url('/') }}" class="scf-btn-primary rounded-xl px-6 py-3 text-sm font-semibold text-white">
                        {{ __('scf.landing.cta_primary') }}
                    </a>
                @endauth
                <a href="#contact" class="scf-btn-ghost rounded-xl px-6 py-3 text-sm font-semibold text-slate-100">
                    {{ __('scf.landing.cta_secondary') }}
                </a>
            </div>
            <div class="scf-fade-up scf-d4 mt-12 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ([
                    ['50+', 'stats_modules'],
                    ['3', 'stats_locales'],
                    ['10', 'stats_roles'],
                    ['99.9%', 'stats_uptime'],
                ] as [$value, $label])
                    <div class="scf-glass rounded-2xl px-4 py-3">
                        <p class="text-xl font-bold text-white sm:text-2xl">{{ $value }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('scf.landing.'.$label) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="scf-fade-up scf-d3 relative">
            <div class="scf-preview rounded-3xl p-5 shadow-2xl sm:p-6">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-400/80"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400/80"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/80"></span>
                    </div>
                    <span class="text-xs text-slate-400">{{ __('scf.dashboard') }}</span>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ([['#38bdf8', '128'], ['#818cf8', '64'], ['#34d399', '42']] as [$color, $n])
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                            <div class="h-1.5 w-10 rounded bg-white/20"></div>
                            <p class="mt-3 text-2xl font-bold" style="color: {{ $color }}">{{ $n }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 space-y-2">
                    <div class="h-2 w-4/5 rounded-full bg-blue-500/40"></div>
                    <div class="h-2 w-full rounded-full bg-white/10"></div>
                    <div class="h-2 w-3/4 rounded-full bg-white/10"></div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="h-24 rounded-xl bg-gradient-to-br from-blue-600/30 to-transparent"></div>
                    <div class="h-24 rounded-xl bg-gradient-to-br from-sky-500/20 to-transparent"></div>
                </div>
            </div>
            <div class="pointer-events-none absolute -inset-6 -z-10 rounded-[2rem] bg-sky-500/20 blur-3xl"></div>
        </div>
    </div>
</section>

{{-- Features --}}
<section id="features" class="border-t border-white/5 bg-[#050b16] py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.features_section_title') }}</h2>
            <p class="mt-3 text-slate-400">{{ __('scf.landing.features_section_subtitle') }}</p>
        </div>
        <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as $i)
                <article class="scf-glass rounded-2xl p-6">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/15 text-sm font-bold text-sky-300">0{{ $i }}</div>
                    <h3 class="text-lg font-semibold text-white">{{ __('scf.landing.feature_'.$i.'_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ __('scf.landing.feature_'.$i.'_desc') }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Industries --}}
<section id="industries" class="border-t border-white/5 bg-slate-950 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.industries_title') }}</h2>
        <p class="mt-3 max-w-2xl text-slate-400">{{ __('scf.landing.industries_subtitle') }}</p>
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($industries as $industry)
                <div class="scf-glass rounded-2xl border-s-4 border-s-sky-500 px-5 py-6">
                    <h3 class="text-lg font-semibold text-white">{{ __('scf.landing.'.$industry) }}</h3>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ERP Modules --}}
<section id="modules" class="border-t border-white/5 bg-[#050b16] py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.modules_title') }}</h2>
        <p class="mt-3 max-w-2xl text-slate-400">{{ __('scf.landing.modules_subtitle') }}</p>
        <div class="mt-12 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach ($modules as $module)
                <div class="scf-module rounded-xl px-3 py-4 text-center text-sm font-medium text-slate-200">
                    {{ __('scf.modules.'.$module) }}
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Screenshots --}}
<section id="screenshots" class="border-t border-white/5 bg-slate-950 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.screenshots_title') }}</h2>
        <p class="mt-3 max-w-2xl text-slate-400">{{ __('scf.landing.screenshots_subtitle') }}</p>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($previews as $preview)
                <div class="scf-preview rounded-2xl p-5" style="animation-delay: {{ $loop->index * 0.4 }}s">
                    <div class="mb-4 flex gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                        <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                        <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                    </div>
                    <div class="space-y-2">
                        <div class="h-2 w-2/3 rounded bg-blue-500/40"></div>
                        <div class="h-2 w-full rounded bg-white/10"></div>
                        <div class="h-2 w-5/6 rounded bg-white/10"></div>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="h-14 rounded-lg bg-blue-600/25"></div>
                            <div class="h-14 rounded-lg bg-sky-500/20"></div>
                            <div class="h-14 rounded-lg bg-white/5"></div>
                        </div>
                    </div>
                    <p class="mt-4 text-sm font-medium text-sky-200">{{ __('scf.landing.'.$preview) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Pricing --}}
<section id="pricing" class="border-t border-white/5 bg-[#050b16] py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.pricing_title') }}</h2>
        <p class="mt-3 max-w-2xl text-slate-400">{{ __('scf.landing.pricing_subtitle') }}</p>
        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            <div class="scf-glass flex flex-col rounded-3xl p-8">
                <h3 class="text-xl font-bold text-white">{{ __('scf.landing.pricing_starter') }}</h3>
                <p class="mt-2 text-sm text-slate-400">{{ __('scf.landing.pricing_starter_desc') }}</p>
                <p class="mt-6"><span class="text-4xl font-extrabold text-white">{{ __('scf.landing.pricing_starter_price') }}</span><span class="text-slate-400">{{ __('scf.landing.pricing_starter_period') }}</span></p>
                <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-300">
                    <li>{{ __('scf.landing.perk_users', ['count' => 10]) }}</li>
                    <li>{{ __('scf.landing.perk_modules', ['count' => 20]) }}</li>
                    <li>{{ __('scf.landing.perk_support', ['level' => 'Email']) }}</li>
                </ul>
                <a href="{{ Route::has('login') ? route('login') : '#contact' }}" class="scf-btn-ghost mt-8 block rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-white">{{ __('scf.landing.cta_primary') }}</a>
            </div>
            <div class="scf-glass scf-price-featured relative flex flex-col rounded-3xl p-8">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-sky-400">{{ __('scf.landing.pricing_featured') }}</p>
                <h3 class="text-xl font-bold text-white">{{ __('scf.landing.pricing_growth') }}</h3>
                <p class="mt-2 text-sm text-slate-400">{{ __('scf.landing.pricing_growth_desc') }}</p>
                <p class="mt-6"><span class="text-4xl font-extrabold text-white">{{ __('scf.landing.pricing_growth_price') }}</span><span class="text-slate-400">{{ __('scf.landing.pricing_growth_period') }}</span></p>
                <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-300">
                    <li>{{ __('scf.landing.perk_users', ['count' => 50]) }}</li>
                    <li>{{ __('scf.landing.perk_modules', ['count' => 50]) }}</li>
                    <li>{{ __('scf.landing.perk_branches', ['count' => 5]) }}</li>
                    <li>{{ __('scf.landing.perk_api') }}</li>
                    <li>{{ __('scf.landing.perk_support', ['level' => 'Priority']) }}</li>
                </ul>
                <a href="{{ Route::has('login') ? route('login') : '#contact' }}" class="scf-btn-primary mt-8 block rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-white">{{ __('scf.landing.cta_primary') }}</a>
            </div>
            <div class="scf-glass flex flex-col rounded-3xl p-8">
                <h3 class="text-xl font-bold text-white">{{ __('scf.landing.pricing_enterprise') }}</h3>
                <p class="mt-2 text-sm text-slate-400">{{ __('scf.landing.pricing_enterprise_desc') }}</p>
                <p class="mt-6"><span class="text-4xl font-extrabold text-white">{{ __('scf.landing.pricing_enterprise_price') }}</span><span class="text-slate-400">{{ __('scf.landing.pricing_enterprise_period') }}</span></p>
                <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-300">
                    <li>{{ __('scf.landing.perk_users', ['count' => '∞']) }}</li>
                    <li>{{ __('scf.landing.perk_modules', ['count' => 50]) }}</li>
                    <li>{{ __('scf.landing.perk_sso') }}</li>
                    <li>{{ __('scf.landing.perk_custom') }}</li>
                    <li>{{ __('scf.landing.perk_sla') }}</li>
                </ul>
                <a href="#contact" class="scf-btn-ghost mt-8 block rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-white">{{ __('scf.landing.cta_secondary') }}</a>
            </div>
        </div>
    </div>
</section>

{{-- Testimonials --}}
<section class="border-t border-white/5 bg-slate-950 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.testimonials_title') }}</h2>
        <p class="mt-3 max-w-2xl text-slate-400">{{ __('scf.landing.testimonials_subtitle') }}</p>
        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @foreach ([1, 2, 3] as $i)
                <blockquote class="scf-glass rounded-3xl p-7">
                    <p class="text-base leading-relaxed text-slate-300">“{{ __('scf.landing.testimonial_'.$i.'_quote') }}”</p>
                    <footer class="mt-6">
                        <cite class="not-italic font-semibold text-white">{{ __('scf.landing.testimonial_'.$i.'_name') }}</cite>
                        <p class="mt-1 text-sm text-slate-500">{{ __('scf.landing.testimonial_'.$i.'_role') }}</p>
                    </footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="border-t border-white/5 bg-[#050b16] py-24">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.faq_title') }}</h2>
        <p class="mt-3 text-slate-400">{{ __('scf.landing.faq_subtitle') }}</p>
        <div class="mt-10 space-y-4">
            @foreach ([1, 2, 3, 4] as $i)
                <details class="scf-glass group rounded-2xl px-5 py-4">
                    <summary class="cursor-pointer list-none text-lg font-semibold text-white marker:content-none [&::-webkit-details-marker]:hidden">
                        <span class="flex items-center justify-between gap-4">
                            {{ __('scf.landing.faq_'.$i.'_q') }}
                            <span class="text-sky-400 transition group-open:rotate-45">+</span>
                        </span>
                    </summary>
                    <p class="mt-3 text-slate-400 leading-relaxed">{{ __('scf.landing.faq_'.$i.'_a') }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- Contact --}}
<section id="contact" class="relative overflow-hidden border-t border-white/5 py-24">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-blue-950 via-slate-950 to-slate-950"></div>
    <div class="pointer-events-none absolute inset-0 opacity-50" style="background: radial-gradient(ellipse at center, rgba(37,99,235,0.35), transparent 65%);"></div>
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('scf.landing.contact_title') }}</h2>
        <p class="mx-auto mt-4 max-w-xl text-slate-400">{{ __('scf.landing.contact_subtitle') }}</p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="mailto:sales@sahdicreatefuture.com" class="scf-btn-primary rounded-xl px-8 py-3 text-sm font-semibold text-white">
                {{ __('scf.landing.contact_email_label') }}
            </a>
            <a href="mailto:sales@sahdicreatefuture.com?subject=Book%20Demo" class="scf-btn-ghost rounded-xl px-8 py-3 text-sm font-semibold text-white">
                {{ __('scf.landing.contact_demo_label') }}
            </a>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="border-t border-white/5 bg-slate-950 py-16">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
        <div class="sm:col-span-2 lg:col-span-1">
            <p class="scf-brand text-lg font-bold text-white">{{ __('scf.landing.brand') }}</p>
            <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ __('scf.landing.footer_tagline') }}</p>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">{{ __('scf.landing.footer_product') }}</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-500">
                <li><a href="#features" class="hover:text-sky-400">{{ __('scf.landing.nav_features') }}</a></li>
                <li><a href="#modules" class="hover:text-sky-400">{{ __('scf.landing.nav_modules') }}</a></li>
                <li><a href="#pricing" class="hover:text-sky-400">{{ __('scf.landing.nav_pricing') }}</a></li>
                <li><a href="#faq" class="hover:text-sky-400">{{ __('scf.landing.nav_faq') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">{{ __('scf.landing.footer_company') }}</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-500">
                <li><a href="#contact" class="hover:text-sky-400">{{ __('scf.landing.footer_about') }}</a></li>
                <li><a href="#contact" class="hover:text-sky-400">{{ __('scf.landing.footer_careers') }}</a></li>
                <li><a href="#contact" class="hover:text-sky-400">{{ __('scf.landing.nav_contact') }}</a></li>
            </ul>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">{{ __('scf.landing.footer_legal') }}</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-500">
                <li><a href="#contact" class="hover:text-sky-400">{{ __('scf.landing.footer_privacy') }}</a></li>
                <li><a href="#contact" class="hover:text-sky-400">{{ __('scf.landing.footer_terms') }}</a></li>
                <li class="flex gap-3 pt-2">
                    <a href="?lang=en" class="hover:text-sky-400">EN</a>
                    <a href="?lang=ar" class="hover:text-sky-400">AR</a>
                    <a href="?lang=ckb" class="hover:text-sky-400">CKB</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="mx-auto mt-12 max-w-7xl border-t border-white/5 px-4 pt-8 text-sm text-slate-600 sm:px-6 lg:px-8">
        &copy; {{ date('Y') }} {{ __('scf.landing.brand') }}. {{ __('scf.landing.footer_rights') }}
    </div>
</footer>
</body>
</html>

<?php
#   TITLE   : Dashboard Home Page
#   VERSION : 2.1.0

$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$store_name = website_data('name') ?: 'My Store';
$store_domain = __DOMAIN__ ?? '';
$store_theme = __WEBSITE_THEME__ ?? 'default';
$store_url = __WEBSITE_URL__ ?? '#';
?>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body {
        background: #1b1b1c !important;
    }

    .dash-main {
        font-family: 'Inter', -apple-system, sans-serif;
    }
</style>

<?php @include_once "header.php"; ?>

<div class="grid-layout">
    <main class="dash-main overflow-x-hidden bg-[#1b1b1c] md:p-6 lg:p-8">
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[linear-gradient(135deg,#f5f7fa_0%,#edf2f7_48%,#ffffff_100%)] text-slate-900 shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
            <div class="absolute inset-0 opacity-70">
                <div class="absolute -right-20 top-[-5rem] h-64 w-64 rounded-full bg-emerald-200/70 blur-3xl"></div>
                <div class="absolute left-1/3 top-10 h-40 w-40 rounded-full bg-sky-200/70 blur-3xl"></div>
            </div>
            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.8fr)] lg:p-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Store dashboard
                    </div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                        Welcome back, <?php echo htmlspecialchars(__USERNAME__, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Here’s what’s happening with <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></span>.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="<?php echo $admin_base; ?>" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>Open Admin Panel</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>products" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                            <i class="bi bi-box-seam"></i>
                            <span>Manage Products</span>
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Store</p>
                        <p class="mt-2 truncate text-base font-semibold text-slate-950"><?php echo htmlspecialchars($store_domain, ENT_QUOTES, 'UTF-8'); ?></p>
                        <a href="<?php echo htmlspecialchars($store_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-[#008060] hover:text-[#006e52]">
                            Visit live store <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                        </a>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Theme</p>
                        <p class="mt-2 text-base font-semibold text-slate-950 capitalize"><?php echo htmlspecialchars($store_theme, ENT_QUOTES, 'UTF-8'); ?></p>
                        <a href="<?php echo $admin_base; ?>theme" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-[#008060] hover:text-[#006e52]">
                            Change theme <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 grid-cols-2 lg:grid-cols-4 mt-6">
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Products</span>
                    <i class="bi bi-box-seam text-violet-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?php echo class_exists('PDO') ? '—' : '—'; ?></p>
                <p class="mt-1 text-sm text-zinc-500">Catalog overview from admin</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Orders</span>
                    <i class="bi bi-bag text-emerald-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white">—</p>
                <p class="mt-1 text-sm text-zinc-500">Latest sales summary</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Analytics</span>
                    <i class="bi bi-graph-up text-sky-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white">Real-time</p>
                <p class="mt-1 text-sm text-zinc-500">Traffic and conversion tracking</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Status</span>
                    <i class="bi bi-check-circle text-amber-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white">Ready</p>
                <p class="mt-1 text-sm text-zinc-500">Your store is online</p>
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Quick actions</p>
                    <h2 class="mt-1 text-2xl font-semibold text-white">Start where you need to</h2>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="<?php echo $admin_base; ?>products" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-box-seam text-lg text-violet-300"></i>
                        <div>
                            <p class="text-sm font-semibold text-white">Products</p>
                            <p class="text-xs text-zinc-500">Update catalog</p>
                        </div>
                    </div>
                </a>
                <a href="<?php echo $admin_base; ?>orders" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-bag text-lg text-emerald-300"></i>
                        <div>
                            <p class="text-sm font-semibold text-white">Orders</p>
                            <p class="text-xs text-zinc-500">Track fulfillment</p>
                        </div>
                    </div>
                </a>
                <a href="<?php echo $admin_base; ?>analytics" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-graph-up text-lg text-sky-300"></i>
                        <div>
                            <p class="text-sm font-semibold text-white">Analytics</p>
                            <p class="text-xs text-zinc-500">View performance</p>
                        </div>
                    </div>
                </a>
                <a href="<?php echo $admin_base; ?>settings" class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-gear text-lg text-amber-300"></i>
                        <div>
                            <p class="text-sm font-semibold text-white">Settings</p>
                            <p class="text-xs text-zinc-500">Store preferences</p>
                        </div>
                    </div>
                </a>
            </div>
        </section>

        <section class="mt-6 relative overflow-hidden rounded-3xl border border-white/10 shadow-[0_18px_45px_rgba(0,0,0,0.24)]" id="carousel-wrapper">
            <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track">
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #0f172a 0%, #1b1038 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-violet-500/10 border border-violet-500/20">
                            <i class="bi bi-rocket-takeoff text-4xl text-violet-300"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-violet-300 text-xs font-bold uppercase tracking-[0.2em]">Getting started</span>
                            <h3 class="text-xl font-bold text-white mt-1">Launch your store in minutes</h3>
                            <p class="text-zinc-300 text-sm mt-2 max-w-lg">Add products, choose a theme, connect payments, and publish a polished storefront.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>products" class="shrink-0 bg-[#008060] hover:bg-[#006e52] text-white px-5 py-2.5 rounded-full text-sm font-medium transition-colors">
                            Add products <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #0f172a 0%, #0d2818 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                            <i class="bi bi-graph-up-arrow text-4xl text-emerald-300"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-emerald-300 text-xs font-bold uppercase tracking-[0.2em]">New feature</span>
                            <h3 class="text-xl font-bold text-white mt-1">Real-time analytics dashboard</h3>
                            <p class="text-zinc-300 text-sm mt-2 max-w-lg">Track page views, visitors, referrers, and device breakdowns without leaving the admin.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>analytics" class="shrink-0 bg-[#008060] hover:bg-[#006e52] text-white px-5 py-2.5 rounded-full text-sm font-medium transition-colors">
                            View analytics <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #0f172a 0%, #0d1528 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-sky-500/10 border border-sky-500/20">
                            <i class="bi bi-code-slash text-4xl text-sky-300"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-sky-300 text-xs font-bold uppercase tracking-[0.2em]">Developer</span>
                            <h3 class="text-xl font-bold text-white mt-1">Public store API & JavaScript SDK</h3>
                            <p class="text-zinc-300 text-sm mt-2 max-w-lg">Embed your products anywhere with the drop-in SDK or REST API.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>settings?tab=dev" class="shrink-0 bg-[#008060] hover:bg-[#006e52] text-white px-5 py-2.5 rounded-full text-sm font-medium transition-colors">
                            Developer settings <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #0f172a 0%, #28210d 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                            <i class="bi bi-trophy text-4xl text-amber-300"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-amber-300 text-xs font-bold uppercase tracking-[0.2em]">Growth tip</span>
                            <h3 class="text-xl font-bold text-white mt-1">Boost conversions with urgency</h3>
                            <p class="text-zinc-300 text-sm mt-2 max-w-lg">Flash sales and limited-time discounts create urgency. Pair with free delivery thresholds for maximum impact.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>discounts" class="shrink-0 bg-[#008060] hover:bg-[#006e52] text-white px-5 py-2.5 rounded-full text-sm font-medium transition-colors">
                            Create discount <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <button onclick="carouselPrev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-9 h-9 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button onclick="carouselNext()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-9 h-9 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2" id="carousel-dots">
                <button onclick="carouselGo(0)" class="w-2 h-2 rounded-full bg-white transition-all"></button>
                <button onclick="carouselGo(1)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(2)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(3)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
            </div>
        </section>

        <script>
            (function () {
                var current = 0, total = 4;
                var track = document.getElementById('carousel-track');
                var dots = document.getElementById('carousel-dots').children;
                var autoplay;
                function update() {
                    track.style.transform = 'translateX(-' + (current * 100) + '%)';
                    for (var i = 0; i < dots.length; i++) {
                        dots[i].className = i === current ? 'w-2 h-2 rounded-full bg-white transition-all scale-125' : 'w-2 h-2 rounded-full bg-white/30 transition-all';
                    }
                }
                window.carouselNext = function () { current = (current + 1) % total; update(); resetAutoplay(); };
                window.carouselPrev = function () { current = (current - 1 + total) % total; update(); resetAutoplay(); };
                window.carouselGo = function (i) { current = i; update(); resetAutoplay(); };
                function resetAutoplay() { clearInterval(autoplay); autoplay = setInterval(window.carouselNext, 8000); }
                resetAutoplay();
            })();
        </script>

        <section class="mt-6 rounded-3xl border border-white/10 bg-[#202123] overflow-hidden shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="px-5 py-3 border-b border-white/10 flex items-center justify-between bg-white/5">
                <div>
                    <h2 class="text-sm font-semibold text-white">Live preview</h2>
                    <p class="text-xs text-zinc-500">Your storefront as customers see it.</p>
                </div>
                <span class="text-zinc-500 text-xs font-mono select-all"><?php echo htmlspecialchars($store_domain, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <iframe src="<?php echo htmlspecialchars($store_url, ENT_QUOTES, 'UTF-8'); ?>" class="w-full border-none bg-white" style="height: 65vh;" frameborder="0"></iframe>
        </section>
    </main>
</div>

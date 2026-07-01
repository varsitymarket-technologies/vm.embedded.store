<?php 

#   TITLE   : Admin Settings General Page
#   DESC    : The Admin settings page for the control panel
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/07/01


?>

<!-- Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-white">Settings</h1>
    <p class="text-sm text-zinc-400 mt-1">
        Manage your store preferences, checkout, integrations and system configuration.
    </p>
</div>

<div class="space-y-8">

    <!-- Store -->
    <section>
        <div class="mb-4">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Store</h2>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden divide-y divide-zinc-800">

            <a href="?tab=branding" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-violet-500/10 flex items-center justify-center">
                        <i class="bi bi-shop text-violet-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Store Details</h3>
                        <p class="text-sm text-zinc-500">
                            Name, logo, description and branding.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

            <a href="?tab=currency" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/10 flex items-center justify-center">
                        <i class="bi bi-cash-stack text-amber-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Markets & Currency</h3>
                        <p class="text-sm text-zinc-500">
                            Currency formatting, regions and pricing.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

            <a href="?tab=domain" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-teal-500/10 flex items-center justify-center">
                        <i class="bi bi-globe2 text-teal-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Domains</h3>
                        <p class="text-sm text-zinc-500">
                            Connect custom domains and manage DNS.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

        </div>
    </section>

    <!-- Checkout -->
    <section>
        <div class="mb-4">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Checkout</h2>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden divide-y divide-zinc-800">

            <a href="?tab=payment" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                        <i class="bi bi-credit-card text-emerald-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Payments</h3>
                        <p class="text-sm text-zinc-500">
                            YOCO, PayPal, COD and payment providers.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

            <a href="?tab=email" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-sky-500/10 flex items-center justify-center">
                        <i class="bi bi-envelope text-sky-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Notifications</h3>
                        <p class="text-sm text-zinc-500">
                            SMTP, order emails and customer notifications.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

        </div>
    </section>

    <!-- Apps -->
    <section>
        <div class="mb-4">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Apps & Integrations</h2>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden divide-y divide-zinc-800">

            <a href="?tab=app" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                        <i class="bi bi-grid text-indigo-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Apps</h3>
                        <p class="text-sm text-zinc-500">
                            Installed apps, webhooks and integrations.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

            <a href="?tab=console" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-pink-500/10 flex items-center justify-center">
                        <i class="bi bi-phone text-pink-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Mobile App</h3>
                        <p class="text-sm text-zinc-500">
                            Configure mobile application access.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

        </div>
    </section>

    <!-- AI -->
    <section>
        <div class="mb-4">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Automation</h2>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden divide-y divide-zinc-800">

            <a href="?tab=agent" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-cyan-500/10 flex items-center justify-center">
                        <i class="bi bi-stars text-cyan-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">AI Assistant</h3>
                        <p class="text-sm text-zinc-500">
                            Configure prompts, tools and permissions.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

        </div>
    </section>

    <!-- Advanced -->
    <section>
        <div class="mb-4">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Advanced</h2>
        </div>

        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden divide-y divide-zinc-800">

            <a href="?tab=dev" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-orange-500/10 flex items-center justify-center">
                        <i class="bi bi-code-slash text-orange-400 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Developer</h3>
                        <p class="text-sm text-zinc-500">
                            API keys, CORS, SDKs and developer access.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

            <a href="?tab=deployment" class="flex items-center justify-between p-5 hover:bg-zinc-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-zinc-700/50 flex items-center justify-center">
                        <i class="bi bi-github text-zinc-300 text-lg"></i>
                    </div>

                    <div>
                        <h3 class="text-white font-medium">Deployment</h3>
                        <p class="text-sm text-zinc-500">
                            GitHub repository and deployment pipeline.
                        </p>
                    </div>
                </div>

                <i class="bi bi-chevron-right text-zinc-600"></i>
            </a>

        </div>
    </section>

</div>
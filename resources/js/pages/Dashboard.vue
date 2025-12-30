<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-slate-950 text-slate-50">
        <div class="mx-auto max-w-6xl px-6 py-12">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-300">PassNote</p>
                    <h1 class="mt-2 text-3xl font-bold text-white">Dashboard</h1>
                    <p class="mt-2 text-slate-300">Welcome back! Vault content will live here soon.</p>
                </div>
            </div>

            <div class="mt-10 rounded-2xl border border-slate-800 bg-slate-900/60 p-8 text-slate-200 shadow-xl shadow-black/30">
                <p class="text-slate-300">Nothing to show yet. Add dashboard widgets here.</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import UnlockKeysModal from './UnlockKeysModal.vue';
import { useKeyStore } from '../stores/keyStore';

type User = {
    name?: string;
    email?: string;
};

const props = defineProps<{
    user?: User | null;
}>();

const userLabel = computed(() => props.user?.name || props.user?.email || 'User');

const logoutForm = useForm({});
const { clearKeys } = useKeyStore();
const handleLogout = () => {
    clearKeys();
    logoutForm.post('/logout');
};
</script>

<template>
    <div class="flex min-h-screen bg-slate-950 text-slate-50">
        <aside class="flex w-72 flex-col border-r border-slate-800 bg-slate-900/60 px-6 py-8 shadow-xl shadow-black/30">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-300">PassNote</p>
                <h2 class="mt-2 text-xl font-bold text-white">Dashboard</h2>
            </div>

            <nav class="mt-10 space-y-1 text-sm font-semibold text-slate-200">
                <a class="block rounded-lg px-3 py-2 bg-slate-800/70 text-white">Overview</a>
            </nav>

            <div class="mt-auto">
                <div class="rounded-xl border border-slate-800 bg-slate-900 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Signed in</p>
                    <p class="mt-1 truncate text-sm font-semibold text-white">{{ userLabel }}</p>
                    <p class="truncate text-xs text-slate-400">{{ props.user?.email }}</p>
                </div>

                <form class="mt-4" @submit.prevent="handleLogout">
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-400 disabled:opacity-70"
                        :disabled="logoutForm.processing"
                    >
                        {{ logoutForm.processing ? 'Signing out...' : 'Log out' }}
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 px-8 py-10">
            <slot />
        </main>

        <UnlockKeysModal />
    </div>
</template>

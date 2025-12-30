<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { unlockPrivateKey } from '../services/cryptoService';
import { useKeyStore } from '../stores/keyStore';

const page = usePage();
const { privateKey, publicKey, setPrivateKey, setPublicKey, clearKeys } = useKeyStore();

const password = ref('');
const errorMessage = ref<string | null>(null);
const processing = ref(false);
const logoutForm = useForm({});

const encryptedPrivateKey = computed(() => page.props.auth?.encrypted_private_key ?? null);
const userPublicKey = computed(() => page.props.auth?.user?.public_key ?? null);

watch(
    userPublicKey,
    (value) => {
        if (value && !publicKey.value) {
            setPublicKey(value);
        }
    },
    { immediate: true }
);

const shouldPrompt = computed(() => {
    const missingPrivate = !privateKey.value;
    const missingPublic = !publicKey.value && !userPublicKey.value;
    return missingPrivate || missingPublic;
});

const unlock = async () => {
    errorMessage.value = null;

    if (!password.value) {
        errorMessage.value = 'Enter your password to unlock your keys.';
        return;
    }

    if (!encryptedPrivateKey.value || typeof encryptedPrivateKey.value !== 'string') {
        errorMessage.value = 'Encrypted private key is missing.';
        return;
    }

    processing.value = true;

    try {
        const decrypted = await unlockPrivateKey(encryptedPrivateKey.value, password.value);
        setPrivateKey(decrypted);
        if (userPublicKey.value && !publicKey.value) {
            setPublicKey(userPublicKey.value);
        }
        password.value = '';
    } catch (error) {
        errorMessage.value = 'Invalid password or corrupted key data.';
        console.error(error)
    } finally {
        processing.value = false;
    }
};

const handleLogout = () => {
    clearKeys();
    logoutForm.post('/logout');
};
</script>

<template>
    <div v-if="shouldPrompt" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 px-4 py-8">
        <div class="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-950 p-6 shadow-2xl shadow-black/60">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-300">Unlock Vault</p>
                <h2 class="mt-1 text-2xl font-bold text-white">Enter your password</h2>
                <p class="mt-2 text-sm text-slate-400">
                    Your private key is stored encrypted. Unlock it to view and share notes.
                </p>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="text-sm font-semibold text-white">Password</label>
                    <input
                        v-model="password"
                        type="password"
                        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    />
                </div>
                <p v-if="errorMessage" class="text-sm text-amber-300">{{ errorMessage }}</p>
                <button
                    type="button"
                    class="w-full rounded-lg bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="processing"
                    @click="unlock"
                >
                    {{ processing ? 'Unlocking...' : 'Unlock' }}
                </button>
                <button
                    type="button"
                    class="w-full rounded-lg border border-slate-700 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-amber-300 hover:text-white disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="logoutForm.processing"
                    @click="handleLogout"
                >
                    {{ logoutForm.processing ? 'Signing out...' : 'Log out' }}
                </button>
            </div>
        </div>
    </div>
</template>

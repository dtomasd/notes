<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { encryptPrivateKeyForStorage, generateUserKeyPair, unlockPrivateKey } from '../services/cryptoService';
import { useKeyStore } from '../stores/keyStore';

const view = ref<'login' | 'register'>('login');

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    public_key: '',
    private_key: '',
});

const handleLogin = () => {
    loginForm.post('/login', {
        onSuccess: async (page) => {
            const encrypted = page.props?.auth?.encrypted_private_key;
            const userPublicKey = page.props?.auth?.user?.public_key;
            if (!encrypted || typeof encrypted !== 'string') {
                return;
            }

            try {
                const privateKey = await unlockPrivateKey(encrypted, loginForm.password);
                const { setPrivateKey, setPublicKey } = useKeyStore();
                setPrivateKey(privateKey);
                if (userPublicKey) {
                    setPublicKey(userPublicKey);
                }
            } catch (error) {
                console.error('Unable to decrypt private key', error);
            }
        },
        onFinish: () => loginForm.reset('password'),
    });
};

const handleRegister = async () => {
    try {
        const { publicKey, privateKey } = await generateUserKeyPair();
        const { setPrivateKey, setPublicKey } = useKeyStore();
        setPrivateKey(privateKey);
        setPublicKey(publicKey);
        const encryptedPrivateKey = await encryptPrivateKeyForStorage(privateKey, registerForm.password);
        registerForm.public_key = publicKey;
        registerForm.private_key = JSON.stringify(encryptedPrivateKey);
    } catch (error) {
        console.error('Unable to generate key pair', error);
        return;
    }

    registerForm.post('/register', {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="PassNote" />

    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 text-slate-50">
        <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-12 lg:flex-row lg:items-center lg:gap-14">
            <section class="lg:w-1/2">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-300">PassNote</p>
                <h1 class="mt-4 text-4xl font-bold leading-tight text-white sm:text-5xl">
                    Keep your notes safe and reachable.
                </h1>
                <p class="mt-4 max-w-xl text-base text-slate-200 sm:text-lg">
                    Sign in to access your notes vault. Create a free account if you are new here.
                </p>
            </section>

            <section class="mt-10 w-full rounded-2xl border border-slate-700/80 bg-white/5 p-6 shadow-xl shadow-black/20 backdrop-blur lg:mt-0 lg:w-1/2">
                <div class="flex gap-2 rounded-xl bg-slate-900/60 p-1">
                    <button
                        type="button"
                        class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold transition hover:text-white"
                        :class="view === 'login' ? 'bg-white text-slate-900 shadow' : 'text-slate-200'"
                        @click="view = 'login'"
                    >
                        Login
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold transition hover:text-white"
                        :class="view === 'register' ? 'bg-white text-slate-900 shadow' : 'text-slate-200'"
                        @click="view = 'register'"
                    >
                        Register
                    </button>
                </div>

                <form v-if="view === 'login'" class="mt-6 space-y-4" @submit.prevent="handleLogin">
                    <div>
                        <label class="text-sm font-semibold text-white">Email</label>
                        <input
                            v-model="loginForm.email"
                            type="email"
                            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                            placeholder="you@example.com"
                            required
                        />
                        <p v-if="loginForm.errors.email" class="mt-2 text-sm text-amber-300">{{ loginForm.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-white">Password</label>
                        <input
                            v-model="loginForm.password"
                            type="password"
                            class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                            placeholder="********"
                            required
                        />
                        <p v-if="loginForm.errors.password" class="mt-2 text-sm text-amber-300">{{ loginForm.errors.password }}</p>
                    </div>
                    <div class="flex items-center justify-between text-sm text-slate-200">
                        <label class="inline-flex items-center gap-2">
                            <input
                                v-model="loginForm.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-amber-300 focus:ring-amber-300/60"
                            />
                            <span>Remember me</span>
                        </label>
                        <span class="text-xs text-slate-400">Use a trusted device</span>
                    </div>
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="loginForm.processing"
                    >
                        {{ loginForm.processing ? 'Signing in...' : 'Continue to PassNote' }}
                    </button>
                </form>

                <form v-else class="mt-6 space-y-4" @submit.prevent="handleRegister">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="text-sm font-semibold text-white">Name</label>
                            <input
                                v-model="registerForm.name"
                                type="text"
                                class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                                placeholder="Alex Doe"
                                required
                            />
                            <p v-if="registerForm.errors.name" class="mt-2 text-sm text-amber-300">{{ registerForm.errors.name }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-semibold text-white">Email</label>
                            <input
                                v-model="registerForm.email"
                                type="email"
                                class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                                placeholder="you@example.com"
                                required
                            />
                            <p v-if="registerForm.errors.email" class="mt-2 text-sm text-amber-300">{{ registerForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-white">Password</label>
                            <input
                                v-model="registerForm.password"
                                type="password"
                                class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                                placeholder="Create a password"
                                required
                            />
                            <p v-if="registerForm.errors.password" class="mt-2 text-sm text-amber-300">{{ registerForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-white">Confirm password</label>
                            <input
                                v-model="registerForm.password_confirmation"
                                type="password"
                                class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                                placeholder="Repeat password"
                                required
                            />
                            <p v-if="registerForm.errors.password_confirmation" class="mt-2 text-sm text-amber-300">
                                {{ registerForm.errors.password_confirmation }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="registerForm.processing"
                    >
                        {{ registerForm.processing ? 'Creating account...' : 'Create account' }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>

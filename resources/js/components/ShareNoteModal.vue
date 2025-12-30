<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { reencryptKeyForSharee } from '../services/cryptoService';
import { useKeyStore } from '../stores/keyStore';

type Sharee = {
    id: number;
    email?: string | null;
    name?: string | null;
};

type NotePayload = {
    id: number;
    title: string;
    encrypted_key?: string | null;
};

const props = defineProps<{
    show: boolean;
    note: NotePayload | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const email = ref('');
const sharees = ref<Sharee[]>([]);
const loadingSharees = ref(false);
const removing = ref<number | null>(null);
const errorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(null);

const { privateKey } = useKeyStore();
const shareForm = useForm({
    email: '',
    encrypted_key: '',
});
const deleteForm = useForm({});

const isVisible = computed(() => props.show);

const resetStatus = () => {
    errorMessage.value = null;
    successMessage.value = null;
    shareForm.clearErrors();
};

const fetchSharees = async () => {
    if (!props.note?.id) {
        sharees.value = [];
        return;
    }

    loadingSharees.value = true;
    resetStatus();

    try {
        const response = await fetch(`/notes/${props.note.id}/shares`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Unable to load sharees.');
        }

        const data = (await response.json()) as { sharees?: Sharee[] };
        sharees.value = data.sharees ?? [];
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Unable to load sharees.';
    } finally {
        loadingSharees.value = false;
    }
};

const fetchPublicKey = async (emailValue: string) => {
    const response = await fetch(`/users/public-key?email=${encodeURIComponent(emailValue)}`, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        const message = response.status === 404 ? 'User not found.' : 'Unable to fetch public key.';
        throw new Error(message);
    }

    const data = (await response.json()) as { public_key?: string | Record<string, unknown> | null };
    if (!data.public_key) {
        throw new Error('Public key is missing for that user.');
    }

    return data.public_key;
};

const shareNote = async () => {
    if (!props.note?.id) {
        return;
    }

    resetStatus();

    if (!email.value.trim()) {
        errorMessage.value = 'Please enter an email address.';
        return;
    }

        if (!props.note.encrypted_key) {
            errorMessage.value = 'Missing encrypted key for this note.';
            return;
        }

    if (!privateKey.value) {
        errorMessage.value = 'Missing private key for sharing.';
        return;
    }

    try {
        const recipientPublicKey = await fetchPublicKey(email.value.trim().toLowerCase());
        const encryptedKey = await reencryptKeyForSharee(
            privateKey.value,
            props.note.encrypted_key,
            recipientPublicKey
        );

        shareForm.email = email.value.trim().toLowerCase();
        shareForm.encrypted_key = encryptedKey;

        await shareForm.post(`/notes/${props.note.id}/shares`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: async () => {
                email.value = '';
                successMessage.value = 'Share added.';
                await fetchSharees();
            },
            onError: (errors) => {
                errorMessage.value = errors.email ?? errors.encrypted_key ?? 'Unable to share note.';
            },
        });
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Unable to share note.';
    }
};

const removeSharee = async (shareeId: number) => {
    if (!props.note?.id) {
        return;
    }

    resetStatus();
    removing.value = shareeId;

    try {
        await deleteForm.delete(`/notes/${props.note.id}/shares/${shareeId}`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: async () => {
                sharees.value = sharees.value.filter((sharee) => sharee.id !== shareeId);
                successMessage.value = 'Share removed.';
            },
            onError: () => {
                errorMessage.value = 'Unable to remove sharee.';
            },
        });
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Unable to remove sharee.';
    } finally {
        removing.value = null;
    }
};

const close = () => {
    email.value = '';
    sharees.value = [];
    resetStatus();
    emit('close');
};

watch(
    () => [props.show, props.note?.id],
    async ([show]) => {
        if (!show) {
            return;
        }
        await fetchSharees();
    }
);
</script>

<template>
    <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8" role="dialog" aria-modal="true">
        <div class="relative w-full max-w-2xl rounded-2xl border border-slate-800 bg-slate-950 p-6 shadow-2xl shadow-black/50">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-300">Share Note</p>
                    <h2 class="mt-1 text-2xl font-bold text-white">{{ props.note?.title ?? 'Untitled note' }}</h2>
                    <p class="mt-1 text-slate-400">Add people and manage access for this note.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                    @click="close"
                    aria-label="Close"
                >
                    ✕
                </button>
            </div>

            <div class="mt-6 space-y-3">
                <label class="text-sm font-semibold text-white">Share with</label>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="email"
                        type="email"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                        placeholder="friend@example.com"
                    />
                    <button
                        type="button"
                        class="rounded-lg bg-amber-300 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="shareForm.processing || !email.trim()"
                        @click="shareNote"
                    >
                        {{ shareForm.processing ? 'Sharing...' : 'Share' }}
                    </button>
                </div>
                <p v-if="errorMessage" class="text-sm text-amber-300">{{ errorMessage }}</p>
                <p v-if="successMessage" class="text-sm text-emerald-300">{{ successMessage }}</p>
            </div>

            <div class="mt-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-300">Shared With</h3>
                    <span class="text-xs text-slate-500">{{ sharees.length }} total</span>
                </div>
                <div class="mt-4 space-y-3">
                    <div v-if="loadingSharees" class="rounded-lg border border-slate-800 bg-slate-900/50 p-4 text-sm text-slate-400">
                        Loading sharees...
                    </div>
                    <div v-else>
                        <div v-if="sharees.length" class="space-y-3">
                            <div
                                v-for="sharee in sharees"
                                :key="sharee.id"
                                class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-3"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ sharee.name ?? 'Shared user' }}</p>
                                    <p class="text-xs text-slate-400">{{ sharee.email ?? 'Unknown email' }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-full p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white disabled:cursor-not-allowed disabled:opacity-70"
                                    :disabled="removing === sharee.id"
                                    @click="removeSharee(sharee.id)"
                                    aria-label="Remove sharee"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-slate-700 p-4 text-center text-sm text-slate-500">
                            No one has access yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

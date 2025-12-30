<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import { decryptNoteContent, encryptNewNoteForRecipient, encryptNoteContentForUpdate } from '../services/cryptoService';
import { useKeyStore } from '../stores/keyStore';

type NotePayload = {
    id?: number;
    title?: string;
    content?: string | null;
    encrypted_key?: string | null;
};

const props = defineProps<{
    show: boolean;
    inline?: boolean;
    note?: NotePayload | null;
    readOnly?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();

const form = useForm({
    title: '',
    content: '',
    encrypted_key: '',
});

const decryptedContent = ref<string | null>(null);
const { publicKey, privateKey } = useKeyStore();

const isEdit = computed(() => Boolean(props.note?.id));
const isReadOnly = computed(() => Boolean(props.readOnly));

const hydrateFromNote = async (note: NotePayload | null | undefined) => {
    form.reset();
    form.clearErrors();
    form.title = note?.title ?? '';
    form.content = note?.content ?? '';
    form.encrypted_key = note?.encrypted_key ?? '';
    decryptedContent.value = note?.content ?? '';

    if (!note?.content || !note?.encrypted_key || !privateKey.value) {
        return;
    }

    try {
        const plaintext = await decryptNoteContent(privateKey.value, note.encrypted_key, note.content);
        decryptedContent.value = plaintext;
    } catch (error) {
        console.error('Unable to decrypt note content', error);
    }
};

watch(
    () => [props.note, props.show],
    async ([note, show]) => {
        if (!props.inline && !show) {
            return;
        }

        await hydrateFromNote(note);
    },
    { immediate: true }
);

const isVisible = computed(() => (props.inline ? true : props.show));

const wrapperClasses = computed(() =>
    props.inline
        ? 'w-full'
        : 'fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8'
);

const panelClasses = computed(
    () =>
        'relative w-full ' +
        (props.inline ? 'max-w-4xl' : 'max-w-2xl') +
        ' rounded-2xl border border-slate-800 bg-slate-950 p-6 shadow-2xl shadow-black/50'
);

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = async () => {
    if (isReadOnly.value) {
        return;
    }

    form.transform((data) => data);

    const url = isEdit.value && props.note?.id ? `/notes/${props.note.id}` : '/notes';
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            if (!props.inline) {
                close();
            }
        },
    };

    // if (!form.encrypted_key) {
    //     try {
    //         const generateAesGcmKey = await generateAesGcmKeyBase64();

    //     } catch (error) {
    //         console.error('Unable to generate encrypted key', error);
    //         return;
    //     }
    // }

    if (isEdit.value) {
        if (!privateKey.value || !props.note?.encrypted_key) {
            console.error('Missing private key or encrypted key for note update');
            return;
        }

        try {
            const encryptedContent = await encryptNoteContentForUpdate(
                privateKey.value,
                props.note.encrypted_key,
                decryptedContent.value ?? ''
            );
            form.content = encryptedContent;
        } catch (error) {
            console.error('Unable to encrypt note content', error);
            return;
        }

        form.transform(({ encrypted_key, ...rest }) => rest);
        form.put(url, {
            ...options,
            onFinish: () => {
                form.transform((data) => data);
            },
        });
    } else {
        if (!publicKey.value) {
            console.error('Missing recipient public key for note encryption');
            return;
        }

        const { encryptedContent, encryptedKey } = await encryptNewNoteForRecipient(
            publicKey.value,
            decryptedContent.value ?? ''
        );

        form.content = encryptedContent;
        form.encrypted_key = encryptedKey;
        form.post(url, {
            ...options,
            onFinish: () => {
                form.transform((data) => data);
            },
        });
    }
};
</script>

<template>
    <div v-if="isVisible" :class="wrapperClasses" role="dialog" aria-modal="true">
        <div :class="panelClasses">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-300">
                        {{ isEdit ? 'Edit Note' : 'New Note' }}
                    </p>
                    <h2 class="mt-1 text-2xl font-bold text-white">{{ isEdit ? 'Update note' : 'Create a note' }}</h2>
                    <p class="mt-1 text-slate-400">Store encrypted content with an optional password.</p>
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

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-semibold text-white">Title</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                        placeholder="Enter a title"
                        required
                        :disabled="isReadOnly"
                    />
                    <p v-if="form.errors.title" class="mt-2 text-sm text-amber-300">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-white">Content</label>
                    <textarea
                        v-model="decryptedContent"
                        rows="6"
                        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                        placeholder="Write your note here"
                        required
                        :readonly="isReadOnly"
                    />
                    <p v-if="form.errors.content" class="mt-2 text-sm text-amber-300">{{ form.errors.content }}</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-800"
                        @click="close"
                    >
                        {{ isReadOnly ? 'Close' : 'Cancel' }}
                    </button>
                    <button
                        v-if="!isReadOnly"
                        type="submit"
                        class="rounded-lg bg-amber-300 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : isEdit ? 'Update note' : 'Create note' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthShell from '../components/AuthShell.vue';
import CreateNoteModal from '../components/CreateNoteModal.vue';
import ShareNoteModal from '../components/ShareNoteModal.vue';

type Note = {
    id: number;
    title: string;
    content: string | null;
    owner_id: number;
    created_at: string;
    updated_at: string;
    encrypted_key?: string | null;
    is_owner?: boolean;
};

const props = defineProps<{
    auth: {
        user: {
            name?: string;
            email?: string;
        } | null;
    };
    notes: Note[];
    shared_notes: Note[];
}>();

const showCreateModal = ref(false);
const selectedNote = ref<Note | null>(null);
const readOnlyNote = ref(false);
const showShareModal = ref(false);
const shareNote = ref<Note | null>(null);
const deletingNoteId = ref<number | null>(null);

const handleSaved = () => {
    showCreateModal.value = false;
    router.reload({ only: ['notes'] });
};

const openShare = (note: Note) => {
    shareNote.value = note;
    showShareModal.value = true;
};

const openNote = (note: Note, readOnly: boolean) => {
    selectedNote.value = note;
    readOnlyNote.value = readOnly;
    showCreateModal.value = true;
};

const deleteNote = (note: Note) => {
    if (!confirm(`Delete "${note.title}"? This cannot be undone.`)) {
        return;
    }

    deletingNoteId.value = note.id;
    router.delete(`/notes/${note.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['notes'] });
        },
        onFinish: () => {
            deletingNoteId.value = null;
        },
    });
};

const symbolPool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+{}[];:,.<>?/\\|';

const fakeCipher = (seed: number) => {
    const length = 48;
    let output = '';
    let value = seed || 1;

    for (let i = 0; i < length; i += 1) {
        value = (value * 9301 + 49297) % 233280;
        const idx = value % symbolPool.length;
        output += symbolPool.charAt(idx);
    }

    return output;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthShell :user="props.auth?.user">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-300">Overview</p>
                <h1 class="mt-2 text-3xl font-bold text-white">Welcome back</h1>
                <p class="mt-2 text-slate-300">Your latest notes are below.</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-300 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-200"
                @click="
                    selectedNote = null;
                    readOnlyNote = false;
                    showCreateModal = true;
                "
            >
                + New note
            </button>
        </div>

        <div class="mt-10 rounded-2xl border border-slate-800 bg-slate-900/60 p-8 text-slate-200 shadow-xl shadow-black/30">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">Notes</h2>
                <span class="text-sm text-slate-400">{{ props.notes.length }} total</span>
            </div>

            <div v-if="props.notes.length" class="mt-6 grid gap-4 md:grid-cols-2">
                <article
                    v-for="note in props.notes"
                    :key="note.id"
                    class="cursor-pointer rounded-xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm shadow-black/30 transition hover:border-amber-300/80"
                    @click="openNote(note, false)"
                >
                    <header class="flex items-start justify-between gap-2">
                        <h3 class="text-lg font-semibold text-white">{{ note.title }}</h3>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-full border border-slate-700 px-2 py-1 text-xs text-slate-200 transition hover:border-amber-300 hover:text-amber-200"
                                @click.stop="openShare(note)"
                            >
                                Share
                            </button>
                            <button
                                type="button"
                                class="rounded-full border border-slate-700 p-2 text-rose-200 transition hover:border-rose-300 hover:text-rose-200 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="deletingNoteId === note.id"
                                @click.stop="deleteNote(note)"
                                aria-label="Delete note"
                            >
                                <span v-if="deletingNoteId === note.id" class="text-xs">Deleting...</span>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M6 6l1 14h10l1-14" />
                                    <path d="M10 11v6" />
                                    <path d="M14 11v6" />
                                </svg>
                            </button>
                            <span class="text-xs text-slate-400">#{{ note.id }}</span>
                        </div>
                    </header>
                    <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-emerald-200/80">
                        <span class="font-mono tracking-[0.2em] blur-[1px]">
                            {{ fakeCipher(note.id) }}
                        </span>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Updated {{ new Date(note.updated_at).toLocaleDateString() }}</p>
                </article>
            </div>
            <div v-else class="mt-6 rounded-lg border border-dashed border-slate-700 p-6 text-center text-slate-400">
                No notes yet. Create one to get started.
            </div>
        </div>

        <div
            class="mt-10 rounded-2xl border border-slate-800 bg-slate-900/60 p-8 text-slate-200 shadow-xl shadow-black/30"
        >
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">Shared with me</h2>
                <span class="text-sm text-slate-400">{{ props.shared_notes.length }} total</span>
            </div>

            <div v-if="props.shared_notes.length" class="mt-6 grid gap-4 md:grid-cols-2">
                <article
                    v-for="note in props.shared_notes"
                    :key="note.id"
                    class="cursor-pointer rounded-xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm shadow-black/30 transition hover:border-amber-300/80"
                    @click="openNote(note, true)"
                >
                    <header class="flex items-start justify-between gap-2">
                        <h3 class="text-lg font-semibold text-white">{{ note.title }}</h3>
                        <span class="text-xs text-slate-400">#{{ note.id }}</span>
                    </header>
                    <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-emerald-200/80">
                        <span class="font-mono tracking-[0.2em] blur-[1px]">
                            {{ fakeCipher(note.id) }}
                        </span>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Updated {{ new Date(note.updated_at).toLocaleDateString() }}</p>
                </article>
            </div>
            <div v-else class="mt-6 rounded-lg border border-dashed border-slate-700 p-6 text-center text-slate-400">
                Nothing shared with you yet.
            </div>
        </div>

        <CreateNoteModal
            :show="showCreateModal"
            :note="selectedNote"
            :read-only="readOnlyNote"
            @close="showCreateModal = false"
            @saved="handleSaved"
        />
        <ShareNoteModal
            :show="showShareModal"
            :note="shareNote"
            @close="
                showShareModal = false;
                shareNote = null;
            "
        />
    </AuthShell>
</template>

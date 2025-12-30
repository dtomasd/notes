<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AuthShell from '../../components/AuthShell.vue';
import CreateNoteModal from '../../components/CreateNoteModal.vue';

const props = defineProps<{
    auth: {
        user: {
            name?: string;
            email?: string;
            public_key?: string | Record<string, unknown> | null;
        } | null;
    };
}>();

const handleClose = () => {
    router.visit('/dashboard');
};
</script>

<template>
    <Head title="Create Note" />

    <AuthShell :user="props.auth?.user">
        <CreateNoteModal :show="true" inline @close="handleClose" />
    </AuthShell>
</template>

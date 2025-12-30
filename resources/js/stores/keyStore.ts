import { ref, readonly } from 'vue';

const privateKey = ref<string | null>(null);
const publicKey = ref<string | Record<string, unknown> | null>(null);

export const useKeyStore = () => {
    const setPrivateKey = (value: string) => {
        privateKey.value = value;
    };

    const setPublicKey = (value: string | Record<string, unknown>) => {
        publicKey.value = value;
    };

    const clearKeys = () => {
        privateKey.value = null;
        publicKey.value = null;
    };

    return {
        privateKey: readonly(privateKey),
        publicKey: readonly(publicKey),
        setPrivateKey,
        setPublicKey,
        clearKeys,
    };
};

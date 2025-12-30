import {
    decryptFromSenderECDH,
    decryptPrivateKeyBase64,
    decryptWithKey,
    encryptForRecipientECDH,
    encryptPrivateKeyBase64,
    encryptWithKey,
    generateAesGcmKey,
    generateUserKeyPairBase64,
} from '../lib/crypto';

type EncryptedKeyPacket = {
    ephPublicJwk: Record<string, unknown>;
    salt: string;
    iv: string;
    ciphertext: string;
};

type EncryptedContentPacket = {
    ciphertext: string;
    iv: string;
};

const parseJson = <T>(value: string, errorMessage: string): T => {
    try {
        return JSON.parse(value) as T;
    } catch {
        throw new Error(errorMessage);
    }
};

const parseEncryptedKey = (encryptedKey: string): EncryptedKeyPacket => {
    const parsed = parseJson<EncryptedKeyPacket>(encryptedKey, 'Invalid encrypted key payload.');
    if (!parsed?.ephPublicJwk || !parsed?.salt || !parsed?.iv || !parsed?.ciphertext) {
        throw new Error('Invalid encrypted key payload.');
    }
    return parsed;
};

const parseEncryptedContent = (content: string): EncryptedContentPacket => {
    const parsed = parseJson<EncryptedContentPacket>(content, 'Invalid encrypted content payload.');
    if (!parsed?.ciphertext || !parsed?.iv) {
        throw new Error('Invalid encrypted content payload.');
    }
    return parsed;
};

export async function decryptNoteContent(
    privateKey: CryptoKey | string | Record<string, unknown>,
    encryptedKey: string,
    encryptedContent: string
) {
    const keyPacket = parseEncryptedKey(encryptedKey);
    const contentPacket = parseEncryptedContent(encryptedContent);
    const keyBytes = await decryptFromSenderECDH(privateKey, keyPacket);
    return decryptWithKey(keyBytes, contentPacket);
}

export async function encryptNewNoteForRecipient(
    recipientPublicKey: Record<string, unknown> | string,
    plaintext: string
) {
    const key = await generateAesGcmKey();
    const rawKey = (await crypto.subtle.exportKey('raw', key)) as ArrayBuffer;
    const encryptedContent = await encryptWithKey(rawKey, plaintext);
    const encryptedKey = await encryptForRecipientECDH(recipientPublicKey, rawKey);

    return {
        encryptedContent: JSON.stringify(encryptedContent),
        encryptedKey: JSON.stringify(encryptedKey),
    };
}

export async function encryptNoteContentForUpdate(
    privateKey: CryptoKey | string | Record<string, unknown>,
    encryptedKey: string,
    plaintext: string
) {
    const keyPacket = parseEncryptedKey(encryptedKey);
    const keyBytes = await decryptFromSenderECDH(privateKey, keyPacket);
    const encryptedContent = await encryptWithKey(keyBytes, plaintext);
    return JSON.stringify(encryptedContent);
}

export async function reencryptKeyForSharee(
    privateKey: CryptoKey | string | Record<string, unknown>,
    encryptedKey: string,
    recipientPublicKey: Record<string, unknown> | string
) {
    const keyPacket = parseEncryptedKey(encryptedKey);
    const keyBytes = await decryptFromSenderECDH(privateKey, keyPacket);
    const encryptedKeyPacket = await encryptForRecipientECDH(recipientPublicKey, keyBytes);
    return JSON.stringify(encryptedKeyPacket);
}

export async function unlockPrivateKey(encryptedPrivateKey: string, password: string) {
    const parsed = parseJson<{ ciphertext: string; iv: string; salt: string }>(
        encryptedPrivateKey,
        'Invalid encrypted private key payload.'
    );
    return decryptPrivateKeyBase64(parsed.ciphertext, password, parsed.iv, parsed.salt);
}

export async function generateUserKeyPair() {
    return generateUserKeyPairBase64();
}

export async function encryptPrivateKeyForStorage(privateKeyBase64: string, password: string) {
    return encryptPrivateKeyBase64(privateKeyBase64, password);
}

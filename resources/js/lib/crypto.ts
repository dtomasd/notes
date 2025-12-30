const arrayBufferToBase64 = (buffer: ArrayBuffer): string => {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((b) => {
        binary += String.fromCharCode(b);
    });
    return btoa(binary);
};

const base64ToArrayBuffer = (base64: string): ArrayBuffer => {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
};

const encoder = new TextEncoder();

export async function generateUserKeyPairBase64() {
    if (typeof crypto === 'undefined' || !crypto.subtle) {
        throw new Error('Web Crypto API is not available in this environment.');
    }

    const keypair = await crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        true,
        ['deriveKey', 'deriveBits']
    );

    const [publicKeyBuffer, privateKeyBuffer] = await Promise.all([
        crypto.subtle.exportKey('spki', keypair.publicKey),
        crypto.subtle.exportKey('pkcs8', keypair.privateKey),
    ]);

    return {
        publicKey: arrayBufferToBase64(publicKeyBuffer),
        privateKey: arrayBufferToBase64(privateKeyBuffer),
    };
}

export async function generateAesGcmKeyBase64() {
    if (typeof crypto === 'undefined' || !crypto.subtle) {
        throw new Error('Web Crypto API is not available in this environment.');
    }

    const key = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']);
    const rawKey = await crypto.subtle.exportKey('raw', key);
    return arrayBufferToBase64(rawKey);
}

const normalizeRawKey = (key: ArrayBuffer | Uint8Array) => (key instanceof Uint8Array ? key.buffer : key);

export async function encryptWithKey(key: ArrayBuffer | Uint8Array, input: string) {
    if (typeof crypto === 'undefined' || !crypto.subtle) {
        throw new Error('Web Crypto API is not available in this environment.');
    }

    const iv = crypto.getRandomValues(new Uint8Array(12));
    const keyBuffer = normalizeRawKey(key);
    const importKey = await crypto.subtle.importKey('raw', keyBuffer, { name: 'AES-GCM' }, false, ['encrypt']);
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, importKey, encoder.encode(input));

    return {
        ciphertext: arrayBufferToBase64(ciphertext),
        iv: arrayBufferToBase64(iv.buffer),
    };
}

export async function decryptWithKey(key: ArrayBuffer | Uint8Array, payload: { ciphertext: string; iv: string }) {
    if (typeof crypto === 'undefined' || !crypto.subtle) {
        throw new Error('Web Crypto API is not available in this environment.');
    }

    const keyBuffer = normalizeRawKey(key);
    const importKey = await crypto.subtle.importKey('raw', keyBuffer, { name: 'AES-GCM' }, false, ['decrypt']);
    const iv = new Uint8Array(base64ToArrayBuffer(payload.iv));
    const ciphertext = base64ToArrayBuffer(payload.ciphertext);
    const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, importKey, ciphertext);
    return new TextDecoder().decode(decrypted);
}

export async function encryptPrivateKeyBase64(privateKeyBase64: string, password: string) {
    if (typeof crypto === 'undefined' || !crypto.subtle) {
        throw new Error('Web Crypto API is not available in this environment.');
    }

    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const passwordKey = await crypto.subtle.importKey('raw', encoder.encode(password), 'PBKDF2', false, ['deriveKey']);
    const aesKey = await crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt, iterations: 150000, hash: 'SHA-256' },
        passwordKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt']
    );

    const encrypted = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        aesKey,
        encoder.encode(privateKeyBase64)
    );

    return {
        ciphertext: arrayBufferToBase64(encrypted),
        iv: arrayBufferToBase64(iv.buffer),
        salt: arrayBufferToBase64(salt.buffer),
    };
}

export async function decryptPrivateKeyBase64(
    ciphertextBase64: string,
    password: string,
    ivBase64: string,
    saltBase64: string
) {
    if (typeof crypto === 'undefined' || !crypto.subtle) {
        throw new Error('Web Crypto API is not available in this environment.');
    }

    const salt = new Uint8Array(base64ToArrayBuffer(saltBase64));
    const iv = new Uint8Array(base64ToArrayBuffer(ivBase64));
    const ciphertext = base64ToArrayBuffer(ciphertextBase64);

    const passwordKey = await crypto.subtle.importKey('raw', encoder.encode(password), 'PBKDF2', false, ['deriveKey']);
    const aesKey = await crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt, iterations: 150000, hash: 'SHA-256' },
        passwordKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['decrypt']
    );

    const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, aesKey, ciphertext);
    return new TextDecoder().decode(decrypted);
}

export async function generateAesGcmKey() {
    return crypto.subtle.generateKey(
        { name: "AES-GCM", length: 256 },
        true,
        ["encrypt", "decrypt"]
    );
}

export async function encryptForRecipientECDH(
    recipientPublicJwk: Record<string, unknown> | string,
    plaintextBytes: ArrayBuffer | Uint8Array
) {
    const te = new TextEncoder();
    const resolvedBytes = plaintextBytes instanceof Uint8Array ? plaintextBytes : new Uint8Array(plaintextBytes);
    const resolvedJwk = typeof recipientPublicJwk === 'string'
        ? (() => {
              try {
                  return JSON.parse(recipientPublicJwk);
              } catch {
                  return null;
              }
          })()
        : recipientPublicJwk;

    const recipientPublicKey = resolvedJwk
        ? await crypto.subtle.importKey(
              'jwk',
              resolvedJwk,
              { name: 'ECDH', namedCurve: 'P-256' },
              false,
              []
          )
        : await crypto.subtle.importKey(
              'spki',
              base64ToArrayBuffer(recipientPublicJwk as string),
              { name: 'ECDH', namedCurve: 'P-256' },
              false,
              []
          );

    // ephemeral keypair
    const eph = await crypto.subtle.generateKey(
        { name: "ECDH", namedCurve: "P-256" },
        true,
        ["deriveBits"]
    );

    // shared secret bits
    const sharedBits = await crypto.subtle.deriveBits(
        { name: "ECDH", public: recipientPublicKey },
        eph.privateKey,
        256
    );

    // derive AES key from shared secret (HKDF)
    const ikm = await crypto.subtle.importKey("raw", sharedBits, "HKDF", false, ["deriveKey"]);
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const info = te.encode("share-secret-v1"); // context binding
    const aesKey = await crypto.subtle.deriveKey(
        { name: "HKDF", hash: "SHA-256", salt, info },
        ikm,
        { name: "AES-GCM", length: 256 },
        false,
        ["encrypt", "decrypt"]
    );

    // encrypt
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, aesKey, resolvedBytes);

    // send eph public key + salt + iv + ciphertext
    const ephPublicJwk = await crypto.subtle.exportKey('jwk', eph.publicKey);

    return {
        ephPublicJwk,
        salt: arrayBufferToBase64(salt.buffer),
        iv: arrayBufferToBase64(iv.buffer),
        ciphertext: arrayBufferToBase64(ciphertext),
    };
}

export async function decryptFromSenderECDH(
    recipientPrivateKey: CryptoKey | string | Record<string, unknown>,
    packet: { ephPublicJwk: Record<string, unknown>; salt: string; iv: string; ciphertext: string }
) {
    const te = new TextEncoder();
    const { ephPublicJwk, salt, iv, ciphertext } = packet;

    let resolvedPrivateKey: CryptoKey;
    if (typeof recipientPrivateKey === 'string') {
        const parsed = (() => {
            try {
                return JSON.parse(recipientPrivateKey) as Record<string, unknown>;
            } catch {
                return null;
            }
        })();

        resolvedPrivateKey = parsed
            ? await crypto.subtle.importKey(
                  'jwk',
                  parsed,
                  { name: 'ECDH', namedCurve: 'P-256' },
                  false,
                  ['deriveBits']
              )
            : await crypto.subtle.importKey(
                  'pkcs8',
                  base64ToArrayBuffer(recipientPrivateKey),
                  { name: 'ECDH', namedCurve: 'P-256' },
                  false,
                  ['deriveBits']
              );
    } else if (recipientPrivateKey instanceof CryptoKey) {
        resolvedPrivateKey = recipientPrivateKey;
    } else {
        resolvedPrivateKey = await crypto.subtle.importKey(
            'jwk',
            recipientPrivateKey,
            { name: 'ECDH', namedCurve: 'P-256' },
            false,
            ['deriveBits']
        );
    }

    const ephPublicKey = await crypto.subtle.importKey(
        "jwk",
        ephPublicJwk,
        { name: "ECDH", namedCurve: "P-256" },
        false,
        []
    );

    const sharedBits = await crypto.subtle.deriveBits(
        { name: "ECDH", public: ephPublicKey },
        resolvedPrivateKey,
        256
    );

    const ikm = await crypto.subtle.importKey("raw", sharedBits, "HKDF", false, ["deriveKey"]);
    const info = te.encode("share-secret-v1");
    const saltBytes = new Uint8Array(base64ToArrayBuffer(salt));
    const aesKey = await crypto.subtle.deriveKey(
        { name: "HKDF", hash: "SHA-256", salt: saltBytes, info },
        ikm,
        { name: "AES-GCM", length: 256 },
        false,
        ["decrypt"]
    );

    const ivBytes = new Uint8Array(base64ToArrayBuffer(iv));
    const ciphertextBytes = base64ToArrayBuffer(ciphertext);
    const plaintext = await crypto.subtle.decrypt({ name: "AES-GCM", iv: ivBytes }, aesKey, ciphertextBytes);
    return new Uint8Array(plaintext);
}

<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\NoteKey;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userAPayload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'public_key' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAElvXYVFLZc/Eu6r2XtsW6HpW1HOjXiKeIg+B+DAu0glKZSe3M6Y81PmGPVQ/5HVcSD+c7/cvYulrvMo4WA/k2dA==',
            'private_key' => json_encode([
                'ciphertext' => 'ogklAxEPoYOQ1ZIQ7dmQw8jPfazf1YBQ09M5rUXVZn2xvWPz1JrFBZgjrwjULhaLo3CnWfXYa/uvwYqiW5wJUBV2jo3UmVLrhzare0bxtcpUrNJC07QIIr10Jhs/NCVZvcHyATkplqkVnAXAJ2GKtQzhlQFXaJVYAAGWuJbghJeZ+T5RlL7zUYTpwxtSAWezJRCmkDoKBAdiGZHPK/muGQkdM604xkmjC6eiN2vTzcTIr6Ed+VeLOVket7+JioZAxTAXhUVYs9c=',
                'iv' => 'Np9YHNG5zBqew5e+',
                'salt' => 'g27jIfQDTLptuVs5sMFVCQ==',
            ], JSON_UNESCAPED_SLASHES),
        ];

        $userBPayload = [
            'name' => 'Second User',
            'email' => 'friend@example.com',
            'password' => Hash::make('password'),
            'public_key' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAECywqWayGD9ixj5LFYD03QKLEtkRxF5QxLpi78KHflu6O/8IBBJFZS8G91Bzcj8QGzTSGBLCwl7xP8ugQ31wZEw==',
            'private_key' => json_encode([
                'ciphertext' => 'bDPsw75HxD9E7nn6shjt/TdD/rnqspe7UEMAdBlFEfhNkGTc74X05fPLlVGLvn7A53p6O3egVLO0d9vg4tUls9k2yGc9SyCLBfCdIR11/FF78hL1vckcYZqa/cMhrIaRVV8emsKSF8uLSc7Int3x/WPttSo0GQaIAFMkN1HJvD0fveu9MMgwT40AzNJx9DPI2qr2BgU8Zsi4h3lWiOf3yUnpv3/o2eAjbRpKuzguk6EoTu4o5Wf8eI4GHVw0pYwkZEQWz/KZotk=',
                'iv' => 'xzMd5JBQkdkf1Es+',
                'salt' => 'D9IHsb7ScOxwLAUGCIuq5w==',
            ], JSON_UNESCAPED_SLASHES),
        ];

        $userA = User::query()->updateOrCreate(
            ['email' => $userAPayload['email']],
            $userAPayload
        );

        $userB = User::query()->updateOrCreate(
            ['email' => $userBPayload['email']],
            $userBPayload
        );

        NoteKey::query()->whereIn('user_id', [$userA->id, $userB->id])->delete();
        Note::query()->whereIn('owner_id', [$userA->id, $userB->id])->delete();

        $notesForA = [
            [
                'title' => 'Welcome to PassNote',
                'plaintext' => 'Seeded note: hello from PassNote.',
                'content' => [
                    'ciphertext' => 'f8cNNV5YpL3vxkHd7mavwZ3N/0urdyO39ae6jMK3pqqQogRuTzpNG67ysc5tXnQOJg==',
                    'iv' => 'I7qosZLP5jS638HH',
                ],
                'owner_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => 'swPP9DFwHNILlkE9glLxSCYSyTH5b3pHILwkAxs74kU',
                        'y' => '6sehsWY4FVeXG1mghJ84DZcEGoPITMl9jYUUdt337VA',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'atkYS/GZBo/W9248AbH3Dg==',
                    'iv' => 'yCx5YHkZAifRBOJq',
                    'ciphertext' => 'WBeT6LFrk/8sibMKQJICOQRfS1tO/2C8S7tdryzdXZMR5Uc+wBu+5clk0d4ibOAM',
                ],
                'sharee_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => 'QgdL8icrmv13lXAHipI3FjaOUK1iRNMrkWuUjVv-lm4',
                        'y' => '8ZXDS3kbavFZVlHJmTExLX-8MkSSFkhwN1F8HbdSUKo',
                        'crv' => 'P-256',
                    ],
                    'salt' => '489lVwsTD+XHtOFspV/SVw==',
                    'iv' => 'Mq+gzpFzEmNSPQPG',
                    'ciphertext' => 'aLMcUeWyJGzrxZCyz+w59MiFSS34PWOR0mxn8Cmtnq6bsKlGFvVKbQAMmGvXgndX',
                ],
            ],
            [
                'title' => 'Shared secrets',
                'plaintext' => 'Seeded note: share this with a friend.',
                'content' => [
                    'ciphertext' => '+9tczalqUkrr071fPldc0zkwtylEc//YJmuHSO+tmupI7Pv89fZ48bOrU1LVHyxJxrIZEbHm',
                    'iv' => '/rgMWnazQ+BKPAxn',
                ],
                'owner_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => 'BJjX7jtsWuKGXAFiEtO-BSvm1BG5KiDP67LlbRaZqDU',
                        'y' => 'kcgB8EcFHWn2PPAC8-sPJtmMKhaiQ9sZj6I8YqabRvQ',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'JVrqUMq2xils10Ciyo3RRw==',
                    'iv' => 'S6yAHFXuN8r9Pfc5',
                    'ciphertext' => 'zg8jKZw/88kVnd2WJG5750mfN7rXkhYaEoy2pyFm6+hS1JBwpB8GKuMg7lhkIYwo',
                ],
                'sharee_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => '3VviusrGvpD5OYWxNfUdde9ttweVYaF2JBCLyZZQ5JA',
                        'y' => '7JTxWr0My6sVz0b0tMv3p9DBHySf3CCvEzH3VQYZ1FI',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'NXL2wxCWN1FuiKnDsJxfwg==',
                    'iv' => '89G/crjG09z6X06b',
                    'ciphertext' => 'e/I0onaID+ooWdoDMfwss15JfioYSoiosh6V9tezP2mPlQJXSFSCn08TTqcsazjM',
                ],
            ],
        ];

        $notesForB = [
            [
                'title' => 'Second user note',
                'plaintext' => 'Seeded note: second user here.',
                'content' => [
                    'ciphertext' => 'feA8weR7S+1i1S414ynzoW90n9tgVVQAor7In3g0CS7Ts9uS9/vnJwTtQ/h3tQ==',
                    'iv' => 'NmWlLZXHXP/Hv902',
                ],
                'owner_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => 'Z7eEPIBWTeMR6dEeaxaw9QK6HHCW2SS6i919RKax5zk',
                        'y' => 'xhB-SmCqExSsS3h9zYHE0r6YQ1KAKONqXJcRLTKqQQ8',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'dq6dqftYkgD5HVJ37be9YA==',
                    'iv' => 'Rv6hPuuJwypkFPrI',
                    'ciphertext' => 'ZEwQsXTUawdev9FJTrgNTZWHqxR2izYiGDoRBfNFpKYcVOFk+595H8a7WIGN0rI4',
                ],
                'sharee_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => '-TIcEZ_vzQaKy-GH4TAD15Vvj44yyA2AjWaLcZw4mAU',
                        'y' => 'JyPmz5c9j1bbkZFunqowGvWUYyZUQ3kR9YDUCZCLHO8',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'xVTClUZLNXvuANuX4aEeww==',
                    'iv' => 'VM0dgAW8qcN9TdNZ',
                    'ciphertext' => 'yjtEmWUx37HuTpcE9M+4R3RO1/r5/Ev1dCXWCXfS/MhlyZNq9S21AdC1Uhfh9Hnq',
                ],
            ],
            [
                'title' => 'Two-way share',
                'plaintext' => 'Seeded note: shared from user two.',
                'content' => [
                    'ciphertext' => 'QZ+iYW6xy+GoHnXXbzF/SYmU+J+RqDM3GIlb4Spu8xmkq45sPB36URXxJg7vvQZJx+c=',
                    'iv' => 'db4dACdsQzeRGwXY',
                ],
                'owner_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => 'HL1GYiuiaMNRLqeonW4ov0afUhKUZeTTRI-z-9ZuT_Y',
                        'y' => '-6A1nt40Mnv1zHQmIox2B5c38gHmad0JkD4ezLO8kNw',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'AOrKQjzidhCySrOTtBAhIg==',
                    'iv' => 'Ghorg81KU3x31Wca',
                    'ciphertext' => 'rM5E5BMHHXyGXIJzomdq7GuEPIguavnephMMfq1DLTE7BBVFwMSGSvz/t74g/ne3',
                ],
                'sharee_key' => [
                    'ephPublicJwk' => [
                        'key_ops' => [],
                        'ext' => true,
                        'kty' => 'EC',
                        'x' => 'tfQLgre-R-ZUWn02ThYpZEUFrCY4snSnumsOblavuuw',
                        'y' => 'iQ6iexvqWiJw_t6D6HPEpzHmY5EUUp05cXZIAtCZMUI',
                        'crv' => 'P-256',
                    ],
                    'salt' => 'w/OiLmLyddK0t0tS2ZPGXQ==',
                    'iv' => 'PCPLkWoy7wlZIVRg',
                    'ciphertext' => '2gdURA27OokCbi4ZwV1QEelJPAC2ggOyMNwgdmDb6gwwmYkTRwW4XSXYvwS4rVoY',
                ],
            ],
        ];

        foreach ($notesForA as $payload) {
            $note = Note::query()->create([
                'title' => $payload['title'],
                'content' => json_encode($payload['content'], JSON_UNESCAPED_SLASHES),
                'owner_id' => $userA->id,
            ]);

            NoteKey::query()->create([
                'note_id' => $note->id,
                'user_id' => $userA->id,
                'key' => json_encode($payload['owner_key'], JSON_UNESCAPED_SLASHES),
            ]);

            NoteKey::query()->create([
                'note_id' => $note->id,
                'user_id' => $userB->id,
                'key' => json_encode($payload['sharee_key'], JSON_UNESCAPED_SLASHES),
            ]);
        }

        foreach ($notesForB as $payload) {
            $note = Note::query()->create([
                'title' => $payload['title'],
                'content' => json_encode($payload['content'], JSON_UNESCAPED_SLASHES),
                'owner_id' => $userB->id,
            ]);

            NoteKey::query()->create([
                'note_id' => $note->id,
                'user_id' => $userB->id,
                'key' => json_encode($payload['owner_key'], JSON_UNESCAPED_SLASHES),
            ]);

            NoteKey::query()->create([
                'note_id' => $note->id,
                'user_id' => $userA->id,
                'key' => json_encode($payload['sharee_key'], JSON_UNESCAPED_SLASHES),
            ]);
        }
    }
}

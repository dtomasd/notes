<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserPublicKeyController extends Controller
{
    public function show(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email'],
        ]);

        $publicKey = User::query()
            ->where('email', $validated['email'])
            ->value('public_key');

        if (!$publicKey) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json(['public_key' => $publicKey]);
    }
}

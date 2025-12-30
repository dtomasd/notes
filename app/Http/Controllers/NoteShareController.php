<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteKey;
use App\Models\User;
use Illuminate\Http\Request;

class NoteShareController extends Controller
{
    public function index(Request $request, Note $note)
    {
        if ($note->owner_id !== $request->user()->id) {
            abort(403);
        }

        $sharees = NoteKey::query()
            ->where('note_id', $note->id)
            ->where('user_id', '!=', $note->owner_id)
            ->with('user:id,email,name')
            ->get(['id', 'user_id'])
            ->map(function (NoteKey $noteKey) {
                return [
                    'id' => $noteKey->id,
                    'email' => $noteKey->user?->email,
                    'name' => $noteKey->user?->name,
                ];
            });

        return response()->json(['sharees' => $sharees]);
    }

    public function store(Request $request, Note $note)
    {
        if ($note->owner_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email'],
            'encrypted_key' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        if ($user->id === $note->owner_id) {
            return back()->withErrors(['email' => 'Cannot share note with yourself.']);
        }

        $noteKey = NoteKey::updateOrCreate(
            [
                'note_id' => $note->id,
                'user_id' => $user->id,
            ],
            [
                'key' => $validated['encrypted_key'],
            ]
        );

        return redirect()->back();
    }

    public function destroy(Request $request, Note $note, NoteKey $noteKey)
    {
        if ($note->owner_id !== $request->user()->id) {
            abort(403);
        }

        if ($noteKey->note_id !== $note->id) {
            abort(404);
        }

        $noteKey->delete();

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Models\NoteKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ownNotes = Note::query()
            ->where('owner_id', $request->user()->id)
            ->latest('updated_at')
            ->get(['id', 'title', 'content', 'owner_id', 'created_at', 'updated_at']);

        $noteKeys = NoteKey::query()
            ->where('user_id', $request->user()->id)
            ->pluck('key', 'note_id');

        $ownNotes = $ownNotes->map(function (Note $note) use ($noteKeys) {
            $note->setAttribute('encrypted_key', $noteKeys->get($note->id));
            $note->setAttribute('is_owner', true);
            return $note;
        });

        $sharedNotes = Note::query()
            ->whereHas('noteKeys', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->where('owner_id', '!=', $request->user()->id)
            ->latest('updated_at')
            ->get(['id', 'title', 'content', 'owner_id', 'created_at', 'updated_at']);

        $sharedNotes = $sharedNotes->map(function (Note $note) use ($noteKeys) {
            $note->setAttribute('encrypted_key', $noteKeys->get($note->id));
            $note->setAttribute('is_owner', false);
            return $note;
        });

        return Inertia::render('Dashboard', [
            'notes' => $ownNotes,
            'shared_notes' => $sharedNotes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNoteRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $note = Note::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'owner_id' => $request->user()->id,
            ]);

            NoteKey::create([
                'key' => $validated['encrypted_key'],
                'note_id' => $note->id,
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()->route('dashboard');
    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Note $note)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        if ($note->owner_id !== $request->user()->id) {
            abort(403);
        }

        $note->update($request->safe()->only(['title', 'content']));

        return redirect()->route('dashboard');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {
        if ($note->owner_id !== request()->user()->id) {
            abort(403);
        }

        $note->delete();

        return redirect()->route('dashboard');
    }
}

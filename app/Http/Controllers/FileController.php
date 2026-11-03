<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\RegistrationGroup;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Slips and badge photos live on the private disk and are only served through here. */
class FileController extends Controller
{
    public function slip(RegistrationGroup $group)
    {
        Gate::authorize('manage-group', $group);
        abort_unless($group->slip_path && Storage::disk('local')->exists($group->slip_path), 404);

        return Storage::disk('local')->response($group->slip_path, null, ['Cache-Control' => 'private, max-age=600']);
    }

    public function badge(Request $request, Participant $participant)
    {
        $user = $request->user();
        abort_unless($user->isStaff() || $user->can('manage-group', $participant->group), 403);
        abort_unless($participant->badge_photo_path && Storage::disk('local')->exists($participant->badge_photo_path), 404);

        return Storage::disk('local')->response($participant->badge_photo_path, null, ['Cache-Control' => 'private, max-age=600']);
    }

    public function qr(Request $request, Participant $participant)
    {
        $user = $request->user();
        abort_unless($user->isStaff() || $user->can('manage-group', $participant->group), 403);

        return response(QrCode::forParticipant($participant, 400), 200, ['Content-Type' => 'image/svg+xml']);
    }
}

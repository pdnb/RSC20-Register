<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Support\QrCode;

class TicketController extends Controller
{
    public function __invoke(string $token)
    {
        $participant = Participant::query()->with(['group.university', 'type'])->where('qr_token', $token)->firstOrFail();

        return view('ticket', [
            'participant' => $participant,
            'qr' => QrCode::forParticipant($participant, 280),
        ]);
    }
}

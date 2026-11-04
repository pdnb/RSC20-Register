<?php

namespace App\Observers;

use App\Models\Participant;
use App\Services\FeeCalculator;
use App\Support\ParticipantCode;
use App\Support\ThaiId;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParticipantObserver
{
    public function saving(Participant $participant): void
    {
        FeeCalculator::snapshot($participant);

        if ($participant->isDirty('national_id')) {
            $participant->national_id = ThaiId::normalize($participant->national_id);
            $participant->national_id_hash = ThaiId::hash($participant->national_id);
        }

        if ($participant->isDirty('receipt_tax_id') && $participant->receipt_tax_id !== null) {
            $participant->receipt_tax_id = ThaiId::normalize($participant->receipt_tax_id);
        }

        $participant->qr_token ??= (string) Str::ulid();
    }

    public function created(Participant $participant): void
    {
        ParticipantCode::assign($participant);
        FeeCalculator::refreshTotal($participant->group);
    }

    public function updated(Participant $participant): void
    {
        if ($participant->wasChanged(['fee_amount', 'participant_type_id'])) {
            FeeCalculator::refreshTotal($participant->group);
        }
    }

    public function deleted(Participant $participant): void
    {
        if ($participant->badge_photo_path) {
            Storage::disk('local')->delete($participant->badge_photo_path);
        }

        FeeCalculator::refreshTotal($participant->group);
    }
}

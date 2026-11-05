<?php

namespace App\Livewire\Coordinator\Concerns;

use App\Models\RegistrationGroup;

trait ResolvesGroup
{
    public RegistrationGroup $group;

    /** A coordinator always works on their university's single group; an admin passes one in. */
    protected function resolveGroup(?RegistrationGroup $group = null): void
    {
        $user = auth()->user();

        if ($group?->exists) {
            abort_unless($user->can('manage-group', $group), 403);
            $this->group = $group;

            return;
        }

        abort_unless($user->isCoordinator() && $user->university_id, 403);

        $this->group = RegistrationGroup::query()->firstOrCreate(
            ['university_id' => $user->university_id],
            ['coordinator_id' => $user->id],
        );
    }

    public function canEdit(): bool
    {
        return auth()->user()->isAdmin() || $this->group->isEditableByCoordinator();
    }

    /** Editing participants already on the list is also allowed while an admin has unlocked the group. */
    public function canEditExistingParticipants(): bool
    {
        return $this->canEdit() || $this->group->isEditUnlocked();
    }
}

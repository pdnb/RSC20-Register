<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('เปลี่ยนรหัสผ่าน')]
class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save()
    {
        $user = auth()->user();

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], attributes: [
            'current_password' => 'รหัสผ่านปัจจุบัน',
            'password' => 'รหัสผ่านใหม่',
        ]);

        $user->forceFill(['password' => Hash::make($this->password), 'must_change_password' => false])->save();
        session()->flash('status', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');

        return $this->redirect(route('coordinator.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.change-password');
    }
}

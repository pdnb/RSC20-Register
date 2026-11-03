<?php

namespace App\Livewire\Auth;

use App\Http\Controllers\Auth\Auth0Controller;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('เข้าสู่ระบบผู้ดูแล')]
class AdminLogin extends Component
{
    public function render()
    {
        return view('livewire.auth.admin-login', ['configured' => Auth0Controller::isConfigured()]);
    }
}

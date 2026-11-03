{{-- Lets plain (non-Livewire) views reuse the guest layout. --}}
@include('layouts.guest', ['slot' => $slot, 'title' => $title ?? null])

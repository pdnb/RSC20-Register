{{-- Lets plain (non-Livewire) views reuse the admin layout. --}}
@include('layouts.admin', ['slot' => $slot, 'title' => $title ?? null])

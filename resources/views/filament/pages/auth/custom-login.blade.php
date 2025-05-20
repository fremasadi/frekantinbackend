<!-- resources/views/filament/pages/auth/custom-login.blade.php -->
<x-filament-panels::page.simple>
    <!-- Hapus heading default -->
    <x-slot name="heading">
        <!-- Heading dikosongkan sesuai desain -->
    </x-slot>

    <!-- Logo Frekantin -->
    {{-- <div class="flex justify-center mb-8">
        <img src="/assets/ic_logo.png" alt="Frekantin" class="h-10">
    </div> --}}

    <!-- Form login yang sederhana -->
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <!-- Tombol login dengan label Masuk -->
        <div class="mt-4">
            <x-filament::button
                type="submit"
                color="primary"
                class="w-full bg-primary-600 fi-btn-login"
            >
                Masuk
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <!-- Hapus footer tambahan -->
</x-filament-panels::page.simple>
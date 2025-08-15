<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $expireDate = '';
    public string $countdown = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        $this->expireDate = $user->expire_date ? $user->expire_date->format('Y-m-d H:i:s') : '';
        $this->updateCountdown();
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
    public function updateCountdown(): void
    {
        $user = Auth::user();

        if (!$user->expire_date) {
            $this->countdown = 'No expiration set';
            return;
        }

        $now = now();
        $diff = $now->diff($user->expire_date);

        $this->countdown = sprintf(
            '%d days, %02d:%02d:%02d',
            $diff->days,
            $diff->h,
            $diff->i,
            $diff->s
        );

        if ($now->greaterThan($user->expire_date)) {
            $this->countdown = 'Expired';
        }
    }
}; ?>
<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            @if(auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
            <div class="mt-2">
                <flux:text>{{ __('Your email address is unverified.') }}
                    <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                        {{ __('Click here to re-send the verification email.') }}
                    </flux:link>
                </flux:text>

                @if(session('status') === 'verification-link-sent')
                <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                    {{ __('A new verification link has been sent to your email address.') }}
                </flux:text>
                @endif
            </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>
                <x-action-message class="me-3" on="profile-updated">{{ __('Saved.') }}</x-action-message>
            </div>
        </form>

        <!-- <livewire:settings.delete-user-form /> -->

        {{-- Expiration Countdown --}}
        <div class="mt-6 p-4 border rounded bg-gray-50 dark:bg-gray-800">
            <h3 class="font-semibold mb-2">Account Expiration</h3>

            @if($expireDate)
            <p>Expire Date: <strong>{{ $expireDate }}</strong></p>
            <p>Time Remaining: <strong id="countdown">{{ $countdown }}</strong></p>
            @else
            <p>No expiration date set.</p>
            @endif
        </div>
    </x-settings.layout>
</section>

<script>
    let countdownElement = document.getElementById('countdown');
    let expireTime = new Date("{{ $expireDate }}").getTime();

    function updateCountdown() {
        let now = new Date().getTime();
        let distance = expireTime - now;

        if (distance < 0) {
            countdownElement.innerHTML = "Expired";
            clearInterval(timer);
            return;
        }

        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownElement.innerHTML = `${days} days, ${hours} Hour ${minutes} Min ${seconds} Sec`;
    }

    updateCountdown();
    let timer = setInterval(updateCountdown, 1000);
</script>
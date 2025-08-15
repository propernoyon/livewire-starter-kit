<?php

use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // OTP step
    public int $step = 1;
    public string $otp = '';
    public ?int $userId = null;

    // STEP 1: Register & send OTP
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','string','lowercase','email','max:255','unique:' . User::class],
            'password' => ['required','string','confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Generate 6-digit OTP
        $plainOtp = (string) random_int(100000, 999999);

        // Create user with OTP (store hashed for safety)
        $user = User::create(array_merge($validated, [
            'otp_code'        => Hash::make($plainOtp),
            'otp_expires_at'  => now()->addMinutes(10),
            'otp_last_sent_at'=> now(),
            'otp_attempts'    => 0,
            'is_verified'     => false,
        ]));

        $this->userId = $user->id;

        // Send OTP email via Notification
        $user->notify(new OtpNotification($plainOtp));

        // Move to OTP step
        $this->step = 2;
    }

    // STEP 2: Verify OTP
    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required','digits:6'],
        ], [
            'otp.digits' => 'OTP must be 6 digits.',
        ]);

        $user = User::find($this->userId);

        if (! $user) {
            $this->addError('otp', 'Session expired. Please register again.');
            $this->resetToStep1();
            return;
        }

        // Expiry check
        if ($user->otp_expires_at && now()->greaterThan($user->otp_expires_at)) {
            $this->addError('otp', 'OTP expired. Please resend a new OTP.');
            return;
        }

        // Attempts check (basic throttle)
        if ($user->otp_attempts >= 5) {
            $this->addError('otp', 'Too many attempts. Please resend a new OTP.');
            return;
        }

        // Verify
        if (! Hash::check($this->otp, $user->otp_code)) {
            $user->increment('otp_attempts');
            $this->addError('otp', 'Invalid OTP.');
            return;
        }

        // Success
        $user->forceFill([
            'is_verified'    => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
            'otp_last_sent_at'=> null,
            'otp_attempts'   => 0,
        ])->save();

        Auth::login($user);

        // Redirect after login
        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }

    // Resend OTP (optional)
    public function resendOtp(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            $this->addError('otp', 'Session expired. Please register again.');
            $this->resetToStep1();
            return;
        }

        // Cooldown: 60s
        if ($user->otp_last_sent_at && now()->diffInSeconds($user->otp_last_sent_at) < 60) {
            $wait = 60 - now()->diffInSeconds($user->otp_last_sent_at);
            $this->addError('otp', "Please wait {$wait} seconds before resending.");
            return;
        }

        $plainOtp = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp_code'        => Hash::make($plainOtp),
            'otp_expires_at'  => now()->addMinutes(10),
            'otp_last_sent_at'=> now(),
            'otp_attempts'    => 0,
        ])->save();

        $user->notify(new OtpNotification($plainOtp));

        session()->flash('status', 'A new OTP has been sent to your email.');
    }

    private function resetToStep1(): void
    {
        $this->step = 1;
        $this->userId = null;
        $this->otp = '';
        // keep name/email so user doesn't have to retype
    }
};
?>

<div class="flex flex-col gap-6">
    @if($step === 1)
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" wire:submit="register" class="flex flex-col gap-6">
            <!-- Name -->
            <flux:input
                wire:model="name"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email -->
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    @elseif($step === 2)
        <x-auth-header :title="__('Verify your email')" :description="__('We sent a 6-digit OTP to your email. Enter it below to complete registration.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form wire:submit.prevent="verifyOtp" class="flex flex-col gap-6">
            <flux:input
                wire:model="otp"
                :label="__('OTP')"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                required
                autofocus
                placeholder="123456"
            />
            @error('otp') <div class="text-sm text-red-500">{{ $message }}</div> @enderror

            <div class="flex flex-col gap-3">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Verify & Continue') }}
                </flux:button>
                <flux:button type="button" variant="ghost" class="w-full" wire:click="resendOtp">
                    {{ __('Resend OTP') }}
                </flux:button>
            </div>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Didn’t get the code? Check your spam folder.') }}
        </div>
    @endif
</div>

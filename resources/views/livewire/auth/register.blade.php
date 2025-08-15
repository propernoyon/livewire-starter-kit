<?php

use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // OTP step
    public int $step = 1;
    public string $otp = '';

    // STEP 1: Register & send OTP
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Generate 6-digit OTP
        $plainOtp = (string) random_int(100000, 999999);

        // Store pending user in session
        session()->put('pending_user', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'otp' => $plainOtp,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 0,
            'is_verified' => true,
            'status' => 'Active',
            'expire_date' => now()->addDays(10),
        ]);

        // Send OTP using Notification (dummy User instance)
        $dummyUser = new User(['email' => $validated['email']]);
        $dummyUser->name = $validated['name']; 
        $dummyUser->notify(new OtpNotification($plainOtp));

        // Move to OTP step
        $this->step = 2;

        session()->flash('status', 'A 6-digit OTP has been sent to your email.');
    }

    // STEP 2: Verify OTP
    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.digits' => 'OTP must be 6 digits.',
        ]);

        $pending = session('pending_user');

        if (!$pending) {
            $this->addError('otp', 'Session expired. Please register again.');
            $this->resetToStep1();
            return;
        }

        // Expiry check
        if (now()->greaterThan($pending['otp_expires_at'])) {
            $this->addError('otp', 'OTP expired. Please resend a new OTP.');
            return;
        }

        // Attempts check
        if ($pending['otp_attempts'] >= 5) {
            $this->addError('otp', 'Too many attempts. Please resend a new OTP.');
            return;
        }

        // Verify OTP
        if ($this->otp !== $pending['otp']) {
            $pending['otp_attempts']++;
            session()->put('pending_user', $pending);
            $this->addError('otp', 'Invalid OTP.');
            return;
        }

        // OTP verified: create user in database
        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password'],
            'is_verified' => true,
            'status' => 'Active',
            'expire_date' => now()->addDays(10),
        ]);

        Auth::login($user);

        // Clear pending session
        session()->forget('pending_user');

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }

    // Resend OTP
    public function resendOtp(): void
    {
        $pending = session('pending_user');

        if (!$pending) {
            $this->addError('otp', 'Session expired. Please register again.');
            $this->resetToStep1();
            return;
        }

        // Cooldown: 60s
        if (isset($pending['last_sent_at']) && now()->diffInSeconds($pending['last_sent_at']) < 60) {
            $wait = 60 - now()->diffInSeconds($pending['last_sent_at']);
            $this->addError('otp', "Please wait {$wait} seconds before resending.");
            return;
        }

        $plainOtp = (string) random_int(100000, 999999);
        $pending['otp'] = $plainOtp;
        $pending['otp_expires_at'] = now()->addMinutes(10);
        $pending['otp_attempts'] = 0;
        $pending['last_sent_at'] = now();
        session()->put('pending_user', $pending);

        // Send OTP using Notification (dummy User instance)
        $dummyUser = new User(['email' => $pending['email']]);
        $dummyUser->name = $pending['name'];
        $dummyUser->notify(new OtpNotification($plainOtp));

        session()->flash('status', 'A new OTP has been sent to your email.');
    }

    private function resetToStep1(): void
    {
        $this->step = 1;
        $this->otp = '';
    }
};
?>

<div class="flex flex-col gap-6">
    @if($step === 1)
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" :placeholder="__('Full name')" />
        <flux:input wire:model="email" :label="__('Email address')" type="email" required autocomplete="email" placeholder="email@example.com" />
        <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" :placeholder="__('Password')" viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" :placeholder="__('Confirm password')" viewable />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Create account') }}</flux:button>
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
        <flux:input wire:model="otp" :label="__('OTP')" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus placeholder="123456" />
        @error('otp') <div class="text-sm text-red-500">{{ $message }}</div> @enderror

        <div class="flex flex-col gap-3">
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Verify & Continue') }}</flux:button>
            <flux:button type="button" variant="ghost" class="w-full" wire:click="resendOtp">{{ __('Resend OTP') }}</flux:button>
        </div>
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Didn’t get the code? Check your spam folder.') }}
    </div>
    @endif
</div>

<?php


use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use App\Notifications\OtpNotification;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $expireDate = '';
    public string $countdown = '';

    // OTP properties
    public string $otpInput = '';
    public string $sentOtp = '';
    public bool $otpSent = false;
    public string $pendingEmail = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->expireDate = $user->expire_date ? $user->expire_date->format('Y-m-d H:i:s') : '';
        $this->updateCountdown();
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        // যদি শুধু name change হয়
        if ($user->email === $validated['email']) {
            $user->name = $validated['name'];
            $user->save();
            $this->dispatch('profile-updated', name: $user->name);
            return;
        }

        // Email change detected → OTP পাঠানো
        $plainOtp = rand(100000, 999999);
        $this->pendingEmail = $validated['email']; // New email address
        $this->sentOtp = $plainOtp;
        $this->otpSent = true;


        $dummyUser = new User(['email' => $validated['email']]);
        $dummyUser->name = $validated['name'];
        $dummyUser->notify(new OtpNotification($plainOtp, 'Email Update'));

        $this->dispatch('otp-sent');
    }


    // Step 1: Send OTP to new email
    public function sendOtp(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore(Auth::id())],
        ]);

        $plainOtp = rand(100000, 999999); // 6-digit OTP
        $this->sentOtp = $plainOtp;
        $this->otpSent = true;
        $this->pendingEmail = $validated['email'];

        $dummyUser = new User(['email' => $validated['email']]);
        $dummyUser->name = $validated['name'];
        $dummyUser->notify(new OtpNotification($plainOtp, 'Email Update'));

        $this->dispatch('otp-sent'); // frontend message
    }

    // Step 2: Verify OTP and update profile
    public function verifyOtp(): void
    {
        if ($this->otpInput == $this->sentOtp) {
            $user = Auth::user();
            $user->name = $this->name;
            $user->email = $this->pendingEmail;
            $user->email_verified_at = null;
            $user->save();

            $this->otpSent = false;
            $this->otpInput = '';
            $this->sentOtp = '';

            $this->dispatch('profile-updated', name: $user->name);
        } else {
            $this->addError('otpInput', 'OTP is incorrect.');
        }
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
};

?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">

        {{-- Name/email form --}}
        <form wire:submit.prevent="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
            <flux:input wire:model="email" :label="__('Email')" type="email" required />
            <div class="flex items-center justify-end">
                @if($otpSent)
                <flux:button variant="primary" value="Save">
                    {{ __('OTP Sent') }}
                </flux:button>
                @else
                <flux:button variant="primary" type="submit" value="Save">
                    {{ __('Save') }}
                </flux:button>
                @endif

            </div>
        </form>

        {{-- OTP input --}}
        @if($otpSent)
        <form wire:submit.prevent="verifyOtp" class="my-6 w-full space-y-6">
            <flux:input wire:model="otpInput" :label="__('Enter OTP')" type="text" required />
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit">{{ __('Verify OTP & Update') }}</flux:button>
            </div>
        </form>
        @endif

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
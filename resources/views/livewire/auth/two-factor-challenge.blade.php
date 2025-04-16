<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Events\Login;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.auth')] class extends Component
{
    
    public $recovery = false;
    public $google2fa;

    #[Validate('required|min:6')] 
    public $auth_code;
    public $recovery_code;

    public function mount()
    {
        // dd(session()->get('login.id'));
        $this->recovery = false;
    }

    public function switchToRecovery()
    {
        $this->recovery = !$this->recovery;
        if($this->recovery){
            $this->js("setTimeout(function(){ window.dispatchEvent(new CustomEvent('focus-auth-2fa-recovery-code', {})); }, 10);");
        } else {
            $this->js("setTimeout(function(){ window.dispatchEvent(new CustomEvent('focus-auth-2fa-auth-code', {})); }, 10);");
        }
        return;
    }

     #[On('submitCode')] 
    public function submitCode($code)
    {
        $this->auth_code = $code;
        $this->validate();

        $user = User::find(session()->get('login.id'));
        // dd(session()->get('login.id'));
        $secret = decrypt($user->two_factor_secret);
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $code);

        if($valid){
            $this->loginUser($user);
        } else {
            $this->addError('auth_code', 'Invalid authentication code. Please try again.');
        }

    }

    public function submit_recovery_code(){
        $user = User::find(session()->get('login.id'));
        $valid = in_array($this->recovery_code, json_decode(decrypt($user->two_factor_recovery_codes)));

        if ($valid) {
            $this->loginUser($user);
        } else {
            $this->addError('recovery_code', 'This is an invalid recovery code. Please try again.');
        }
    }

    public function loginUser($user){
        Auth::login($user);

        // clear out the session that is used to determine if the user can visit the 2fa challenge page.
        session()->forget('login.id');

        event(new Login(auth()->guard('web'), $user, true));
        
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}

?>

<div class="flex flex-col gap-6">
    <div x-data x-on:code-input-complete.window="console.log(event); $dispatch('submitCode', [event.detail.code])" class="relative w-full h-auto">
        @if(!$recovery)
            <x-auth-header :title="__('Authentication Code')" :description="__('Enter the authentication code provided by your authenticator application.')" />
        @else
            <x-auth-header :title="__('Recovery Code')" :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')" />
        @endif

        <div class="space-y-5 text-center">

            @if(!$recovery)
                <div class="relative mt-5">
                    <!-- Authentication Code -->
                    <x-input-otp wire:model="auth_code" id="auth-input-code" digits="6" eventCallback="code-input-complete" type="text" label="Code" />
                </div>
                @error('auth_code')
                    <p class="my-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <flux:button variant="primary" type="submit" class="w-full" wire:click="submitCode(document.getElementById('auth-input-code').value)">{{ __('Continue') }}</flux:button>
            @else
                <div class="relative">
                    <flux:input
                        wire:keydown.enter="submit_recovery_code"
                        wire:model="recovery_code"
                        id="auth-2fa-recovery-code"
                        :label="__('Recovery Code')"
                        type="text"
                        required
                        autofocus
                        autocomplete="recovery-code"
                    />
                </div>
                <flux:button variant="primary" type="submit" class="w-full" wire:click="submit_recovery_code">{{ __('Continue') }}</flux:button>
            @endif
        </div>

        <div class="mt-5 space-x-0.5 text-sm leading-5 text-center text-black dark:text-white">
            <span class="opacity-[47%]">or you can </span>
            <span class="font-medium underline opacity-60 cursor-pointer" wire:click="switchToRecovery" href="#_">
                @if(!$recovery)
                    <span>login using a recovery code</span>
                @else
                    <span>login using an authentication code</span>
                @endif
            </span>
        </div>
    </div>
</div>
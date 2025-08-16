<?php


use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use App\Models\ShopInformation;

new class extends Component {
    public $shop_name;
    public $address;
    public $mobile;
    public $email;

    public function mount()
    {
        $shop = ShopInformation::where('user_id', Auth::id())->first();

        if ($shop) {
            $this->shop_name = $shop->shop_name;
            $this->address   = $shop->address;
            $this->mobile    = $shop->mobile;
            $this->email     = $shop->email;
        }
    }

    public function updateShopInfo()
    {
        $this->validate([
            'shop_name' => 'required|string|max:255',
            'address'   => 'required|string|max:500',
            'mobile'    => 'required|string|max:20',
            'email'     => 'required|email|max:255',
        ]);

        ShopInformation::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'shop_name' => $this->shop_name,
                'address'   => $this->address,
                'mobile'    => $this->mobile,
                'email'     => $this->email,
            ]
        );

        $this->dispatch('shop-updated');
    }
}

?>



<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update Shop Information')" :subheading="__('Ensure your shop information is accurate and up-to-date')">
        <form method="POST" wire:submit="updateShopInfo" class="mt-6 space-y-6">
            <flux:input
                wire:model="shop_name"
                :label="__('Shop Name')"
                type="text"
                required
                autocomplete="shop-name" />
            <flux:input
                wire:model="address"
                :label="__('Address')"
                type="text"
                required
                autocomplete="address" />
            <flux:input
                wire:model="mobile"
                :label="__('Mobile')"
                type="text"
                required
                autocomplete="mobile" />
            <flux:input
                wire:model="email"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="shop-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<div class="container x3-studio-route mt-14"><x-x3.studio-panel :title="$x3['account']" variant="account" :description="$x3['account_text']" />
    <x-navigation.breadcrumb />
    <div class="px-2">

        <div class="grid md:grid-cols-2 gap-3">

            <x-form.input name="first_name" type="text" :label="__('general.input.first_name')"
                :placeholder="__('general.input.first_name_placeholder')" wire:model="first_name" required dirty />
            <x-form.input name="last_name" type="text" :label="__('general.input.last_name')"
                :placeholder="__('general.input.last_name_placeholder')" wire:model="last_name" required dirty />

            <x-form.input name="email" type="email" :label="__('general.input.email')"
                :placeholder="__('general.input.email_placeholder')" required wire:model="email" dirty />

            <x-form.properties :custom_properties="$custom_properties" :properties="$properties" dirty />
        </div>


        <x-button.primary wire:click="submit" class="w-full mt-4">
            {{ __('general.update') }}
        </x-button.primary>
    </div>
</div>

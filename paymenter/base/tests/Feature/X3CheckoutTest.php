<?php
namespace Tests\Feature;
use App\Models\Setting;
use App\Providers\SettingsProvider;
class X3CheckoutTest extends CheckoutTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['key'=>'theme'],['value'=>'x3-hosting','type'=>'string']);
        SettingsProvider::flushCache();
        \Qirolab\Theme\Theme::set('x3-hosting','default');
    }
}


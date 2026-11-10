<?php
namespace Tests\Feature;

use App\Enums\InvoiceTransactionStatus;
use App\Models\{Setting, Product, Plan, Invoice, InvoiceTransaction, Service, User, Currency, Cart, Coupon};
use App\Providers\SettingsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
use Livewire\Livewire;
use Tests\TestCase;
use X3\Rackline\Theme;

class X3UpgradeTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('themes/x3-hosting/lib/Theme.php');
        Setting::updateOrCreate(['key'=>'theme'],['value'=>'x3-hosting','type'=>'string']);
        Setting::updateOrCreate(['key'=>'allowed_languages'],['value'=>['en','ar'],'type'=>'array']);
        SettingsProvider::flushCache();
        \Qirolab\Theme\Theme::set('x3-hosting','default');
    }
    private function setting(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key'=>'theme_x3-hosting_'.$key],['value'=>$value,'type'=>is_bool($value)?'boolean':'string']);
        SettingsProvider::flushCache();
    }
    public function test_admin_settings_convert_through_native_filament(): void
    {
        $settings=\App\Classes\Theme::getSettings();
        $names=array_column($settings,'name');
        $this->assertSame(count($names),count(array_unique($names)));
        foreach (['footer_button_url','product_specs','featured_products','recommended_products','faq_5_a_ar','comparison_enabled'] as $key) {
            $this->assertContains('theme_x3-hosting_'.$key,$names);
        }
        foreach ($settings as $field) $this->assertNotNull(\App\Classes\FilamentInput::convert($field));
    }
    public function test_links_and_faq_are_configurable_escaped_and_reject_unsafe_urls(): void
    {
        $this->setting('footer_button_url','https://example.test/x3');
        $this->setting('footer_button_en','X3 <support>');
        $this->setting('discord_url','javascript:alert(1)');
        $this->setting('footer_links',json_encode([['label_en'=>'Terms','url'=>'/terms'],['label_en'=>'Bad','url'=>'//evil.test']]));
        $this->setting('faq_1_q_en','Custom <question>');
        $this->get('/')->assertOk()->assertSee('https://example.test/x3',false)->assertSee('X3 &lt;support&gt;',false)->assertSee('Custom &lt;question&gt;',false)->assertSee('href="/terms"',false)->assertDontSee('javascript:alert',false)->assertDontSee('//evil.test',false);
        foreach (['javascript:alert(1)','data:text/html,test','//evil.test','/\\evil.test','https://user:pass@example.test','https://example.test/ a'] as $url) $this->assertSame('',Theme::url($url));
        $this->assertSame('/support',Theme::url('/support'));
        $this->assertSame([3,2],Theme::ids('3,2,3,-1,0,no'));
    }
    public function test_featured_visibility_metadata_recommendation_and_art(): void
    {
        $f=$this->createProduct(); $p=$f->product;
        $hidden=Product::factory()->create(['hidden'=>true,'name'=>'HIDDEN-X3']);
        $this->setting('featured_products',$hidden->id.','.$p->id);
        $this->setting('recommended_products',(string)$p->id);
        $this->setting('category_art',json_encode([$p->category_id=>'game']));
        $this->setting('product_specs',json_encode([$p->id=>['cpu'=>'Ryzen <7950X>','ram'=>'8 GB','storage'=>['en'=>'80 GB NVMe','ar'=>'٨٠ غيغابايت NVMe'],'secret'=>'NEVER-EXPOSE']]));
        $this->get('/')->assertOk()->assertSee('x3-hardware-game',false)->assertSee('Ryzen &lt;7950X&gt;',false)->assertSee('Recommended')->assertDontSee('HIDDEN-X3')->assertDontSee('NEVER-EXPOSE');
        $this->assertSame(['cpu','ram','storage'],array_keys(Theme::specs($p)));
        Once::flush();
        $this->withSession(['locale'=>'ar'])->get('/')->assertOk()->assertSee('٨٠ غيغابايت NVMe')->assertSee('موصى بها');
    }
    public function test_invalid_or_untyped_metadata_degrades_without_breaking_pages(): void
    {
        $f=$this->createProduct();
        foreach (['not-json','null','false','{"1":true}','{"1":{"cpu":[1,2]}}'] as $value) {
            $this->setting('product_specs',$value);
            $this->get('/')->assertOk();
        }
        $this->setting('category_art','{"1":{"bad":1}}');
        $this->assertSame('compute',Theme::variant((object)['id'=>1]));
        $this->setting('footer_links','{"invalid":null}'); $this->get('/')->assertOk();
    }
    public function test_card_uses_one_actual_plan_with_setup_cycle_and_currency(): void
    {
        $f=$this->createProduct();
        $f->plan->prices()->update(['setup_fee'=>5]);
        $p=$f->product->fresh(); $card=Theme::card($p);
        $this->assertSame('$10.00',$card['price']); $this->assertSame('$5.00',$card['setup']);
        $this->assertStringContainsString('1',$card['cycle']);
        $this->withSession(['currency'=>'XYZ']);
        $card=Theme::card($p->fresh());
        $this->assertSame('Unavailable in this currency',$card['price']); $this->assertSame('',$card['checkout']);
        $free=$p->plans()->create(['name'=>'Free','type'=>'free','sort'=>2]);
        $card=Theme::card($p->fresh()); $this->assertSame('Free',$card['price']);
    }
    public function test_comparison_toggle_and_both_directions_render(): void
    {
        $f=$this->createProduct();
        foreach (['en'=>'ltr','ar'=>'rtl'] as $locale=>$dir) {
            Once::flush();
            $this->withSession(['locale'=>$locale])->get(route('category.show',$f->product->category))->assertOk()->assertSee('x3Compare(',false)->assertSee('<dialog',false)->assertSee('dir="'.$dir.'"',false);
        }
        $this->setting('comparison_enabled',false);
        $this->get(route('category.show',$f->product->category))->assertOk()->assertDontSee('<dialog',false)->assertDontSee('x3-compare-toggle',false);
    }
    public function test_arabic_billing_cycles_have_correct_singular_dual_and_plural(): void
    {
        app()->setLocale('ar');
        foreach ([1=>'كل شهر',2=>'كل شهرين',3=>'كل ٣ أشهر',12=>'كل ١٢ شهراً'] as $n=>$expected) {
            $plan=new Plan(['type'=>'recurring','billing_unit'=>'month','billing_period'=>$n]);
            $this->assertSame($expected,Theme::cycle($plan));
        }
    }
    public function test_checkout_summary_keeps_native_live_price_updates(): void
    {
        $f=$this->createProduct(); $f->plan->prices()->update(['setup_fee'=>5]);
        $annual=$f->product->plans()->create(['name'=>'Annual','type'=>'recurring','billing_unit'=>'year','billing_period'=>1,'sort'=>1]);
        $annual->prices()->create(['currency_code'=>'USD','price'=>100,'setup_fee'=>7]);
        Livewire::test('products.checkout',['category'=>$f->product->category,'product'=>$f->product->slug])
            ->assertSee('x3-purchase-progress',false)->assertSee('$15.00')->assertSee('One-time setup')
            ->set('plan_id',$annual->id)->assertSee('$107.00')->assertSee('$100.00')->assertSee('$7.00')->assertSee('Annual');
    }
    public function test_cart_first_period_coupon_does_not_reduce_displayed_renewal(): void
    {
        $f=$this->createProduct();
        $coupon=Coupon::create(['code'=>'X3TEST','type'=>'percentage','value'=>50,'applies_to'=>'all','recurring'=>false]);
        $cart=Cart::create(['currency_code'=>'USD','coupon_id'=>$coupon->id]);
        $cart->items()->create(['product_id'=>$f->product->id,'plan_id'=>$f->plan->id,'quantity'=>2,'config_options'=>[],'checkout_config'=>[]]);
        Once::flush();
        $this->withCookie('cart',$cart->ulid)->get(route('cart'))->assertOk()->assertSee('Recurring charge')->assertSee('$20.00')->assertSee('$10.00')->assertSee('x3-purchase-progress',false);
    }
    public function test_dashboard_scopes_actions_uses_remaining_and_does_not_mutate_due_dates(): void
    {
        $user=User::factory()->create(['email_verified_at'=>now()]); $other=User::factory()->create();
        $invoice=Invoice::withoutEvents(fn()=>Invoice::factory()->create(['user_id'=>$user->id,'status'=>'pending','number'=>'X3-PARTIAL','currency_code'=>'USD','due_at'=>today()->subDay()]));
        $invoice->items()->create(['price'=>100,'quantity'=>1,'description'=>'Hosting']);
        InvoiceTransaction::withoutEvents(fn()=>InvoiceTransaction::create(['invoice_id'=>$invoice->id,'amount'=>40,'status'=>InvoiceTransactionStatus::Succeeded]));
        Invoice::withoutEvents(fn()=>Invoice::factory()->create(['user_id'=>$other->id,'status'=>'pending','number'=>'PRIVATE-INVOICE']));
        $f=$this->createProduct();
        $service=Service::withoutEvents(fn()=>Service::create(['user_id'=>$user->id,'product_id'=>$f->product->id,'plan_id'=>$f->plan->id,'currency_code'=>'USD','price'=>10,'quantity'=>1,'status'=>'active','expires_at'=>today()->addDays(3)]));
        $before=$service->expires_at->toDateString(); $this->actingAs($user);
        $response=$this->withSession($this->loginUser($user))->get(route('dashboard'))->assertOk()->assertSee('X3-PARTIAL')->assertSee('$60.00')->assertDontSee('PRIVATE-INVOICE');
        $html=$response->getContent(); $panel=substr($html,strpos($html,'x3-next-actions'),strpos($html,'<!-- Active Services -->')-strpos($html,'x3-next-actions'));
        $this->assertStringContainsString(route('services.show',$service),$panel);
        $this->assertStringContainsString('Overdue',$panel);
        $this->assertSame($before,$service->fresh()->expires_at->toDateString());
    }
    public function test_renewal_panel_excludes_future_one_time_and_other_users_services(): void
    {
        $u=User::factory()->create(['email_verified_at'=>now()]); $other=User::factory()->create(); $f=$this->createProduct();
        $one=$f->product->plans()->create(['name'=>'Lifetime','type'=>'one-time']);
        foreach ([[$u,$f->plan,3],[$u,$f->plan,60],[$u,$one,2],[$other,$f->plan,1]] as [$owner,$plan,$days]) {
            $s=Service::withoutEvents(fn()=>Service::create(['user_id'=>$owner->id,'product_id'=>$f->product->id,'plan_id'=>$plan->id,'currency_code'=>'USD','price'=>10,'quantity'=>1,'status'=>'active','expires_at'=>today()->addDays($days)]));
            $services[]=$s;
        }
        $this->actingAs($u);
        $response=$this->withSession($this->loginUser($u))->get(route('dashboard'))->assertOk();
        $html=$response->getContent(); $panel=substr($html,strpos($html,'x3-next-actions'),strpos($html,'<!-- Active Services -->')-strpos($html,'x3-next-actions'));
        $this->assertStringContainsString(route('services.show',$services[0]),$panel);
        foreach (array_slice($services,1) as $service) $this->assertStringNotContainsString('href="'.route('services.show',$service).'"',$panel);
    }
}

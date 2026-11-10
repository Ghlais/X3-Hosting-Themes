<?php
namespace Tests\Feature;
use App\Models\Setting;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Providers\SettingsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class X3ThemeTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['key' => 'theme'], ['value' => 'x3-hosting', 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'allowed_languages'], ['value' => ['en','ar'], 'type' => 'array']);
        SettingsProvider::flushCache();
        \Qirolab\Theme\Theme::set('x3-hosting', 'default');
    }
    public function test_home_and_empty_state_in_both_directions(): void
    {
        foreach (['en'=>'ltr','ar'=>'rtl'] as $locale=>$dir) {
            \Illuminate\Support\Once::flush();
            $this->withSession(['locale'=>$locale])->get('/')
                ->assertOk()->assertSee('dir="'.$dir.'"', false)
                ->assertSee('X3 Hosting')->assertSee('x3-empty', false)
                ->assertDontSee('Welcome to Paymenter');
        }
    }
    public function test_catalog_uses_actual_product_and_pricing(): void
    {
        $fixture = $this->createProduct();
        foreach (['en','ar'] as $locale) {
            \Illuminate\Support\Once::flush();
            $this->withSession(['locale'=>$locale])->get(route('category.show',$fixture->product->category->slug))
                ->assertOk()->assertSee('Test Product')->assertSee('$10.00');
            $this->get(route('products.show',[$fixture->product->category->slug,$fixture->product->slug]))
                ->assertOk()->assertSee('Test Product');
        }
    }
    public function test_guest_cart_and_auth_forms_render(): void
    {
        foreach (['en','ar'] as $locale) {
            foreach (['cart','login','register','password.request'] as $route) {
                \Illuminate\Support\Once::flush();
                $this->withSession(['locale'=>$locale])->get(route($route))->assertOk();
            }
        }
    }
    public function test_client_pages_render_in_both_languages(): void
    {
        $user = User::factory()->create(['email_verified_at'=>now()]);
        $this->actingAs($user);
        foreach (['en','ar'] as $locale) {
            foreach (['dashboard','services','invoices','tickets','tickets.create','account','account.security','account.payment-methods','account.notifications'] as $route) {
                \Illuminate\Support\Once::flush();
                $this->withSession($this->loginUser($user,['locale'=>$locale]))->get(route($route))->assertOk();
            }
        }
    }
    public function test_invoice_and_ticket_details_still_enforce_ownership(): void
    {
        $owner=User::factory()->create(['email_verified_at'=>now()]);
        $other=User::factory()->create(['email_verified_at'=>now()]);
        $invoice=Invoice::withoutEvents(fn()=>Invoice::factory()->create(['user_id'=>$owner->id,'status'=>'pending']));
        $ticket=Ticket::withoutEvents(fn()=>Ticket::create(['user_id'=>$owner->id,'subject'=>'X3 fixture','status'=>'open','priority'=>'low']));
        $this->actingAs($owner);
        foreach (['en','ar'] as $locale) {
            \Illuminate\Support\Once::flush();
            $this->withSession($this->loginUser($owner,['locale'=>$locale]))->get(route('invoices.show',$invoice))->assertOk();
            $this->get(route('tickets.show',$ticket))->assertOk()->assertSee('X3 fixture');
        }
        $this->actingAs($other);
        $this->withSession($this->loginUser($other))->get(route('invoices.show',$invoice))->assertNotFound();
        $this->get(route('tickets.show',$ticket))->assertNotFound();
    }
    public function test_out_of_stock_product_has_no_checkout_link(): void
    {
        $fixture=$this->createProduct(['stock'=>0]);
        $url=route('products.checkout',[$fixture->product->category->slug,$fixture->product->slug]);
        $this->get(route('products.show',[$fixture->product->category->slug,$fixture->product->slug]))
            ->assertOk()->assertDontSee($url,false);
    }
    public function test_theme_settings_are_escaped_in_html(): void
    {
        Setting::updateOrCreate(['key'=>'theme_x3-hosting_hero_title_en'],['value'=>'<script>alert("x3")</script>','type'=>'string']);
        SettingsProvider::flushCache();
        $this->get('/')->assertOk()->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert("x3")</script>',false);
    }
}

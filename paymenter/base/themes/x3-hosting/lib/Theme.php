<?php
namespace X3\Rackline;

/** Presentation helpers only. No settings writes, provisioning or payment actions. */
final class Theme
{
    public static function locale(): string { return str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en'; }
    public static function strings(): array { return require __DIR__.'/../lang/'.self::locale().'/ui.php'; }
    public static function url(mixed $value, string $fallback = ''): string
    {
        if (!is_string($value)) return $fallback;
        $value = trim($value);
        if ($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) return $fallback;
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) return $value;
        if (filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['https','http'], true) && !parse_url($value, PHP_URL_USER) && !parse_url($value, PHP_URL_PASS)) return $value;
        return $fallback;
    }
    public static function ids(mixed $value): array
    {
        if (!is_string($value)) return [];
        return array_slice(array_values(array_unique(array_filter(array_map(static fn ($id) => ctype_digit(trim($id)) ? (int)trim($id) : 0, explode(',', $value))))), 0, 24);
    }
    public static function map(string $key): array
    {
        $raw = theme($key, '{}');
        if (!is_string($raw) || strlen($raw) > 65536) return [];
        $map = json_decode($raw, true, 12);
        return is_array($map) ? $map : [];
    }
    public static function specs($product): array
    {
        $entry = self::map('product_specs')[(string)$product->id] ?? [];
        if (!is_array($entry)) return [];
        $result = [];
        foreach (['cpu','ram','storage','network','location'] as $key) {
            $value = $entry[$key] ?? null;
            if (is_array($value)) $value = $value[self::locale()] ?? $value['en'] ?? null;
            if (is_string($value) && trim($value) !== '') $result[$key] = mb_substr(trim($value), 0, 180);
        }
        return $result;
    }
    public static function variant($category): string
    {
        $value = self::map('category_art')[(string)$category->id] ?? 'compute';
        return in_array($value, ['compute','game','apps'], true) ? $value : 'compute';
    }
    public static function cycle($plan): string
    {
        $t = self::strings();
        if (!$plan) return $t['unavailable'];
        if ($plan->type === 'free') return $t['free'];
        if ($plan->type !== 'recurring') return $t['one_time'];
        if (self::locale() === 'ar') {
            $forms = ['day'=>['يوم','يومين','أيام','يوماً'], 'week'=>['أسبوع','أسبوعين','أسابيع','أسبوعاً'], 'month'=>['شهر','شهرين','أشهر','شهراً'], 'year'=>['سنة','سنتين','سنوات','سنة']];
            $n = (int)$plan->billing_period;
            if (isset($forms[$plan->billing_unit])) {
                [$one,$two,$few,$many] = $forms[$plan->billing_unit];
                if ($n === 1) return 'كل '.$one;
                if ($n === 2) return 'كل '.$two;
                return 'كل '.strtr((string)$n, ['0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤','5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩']).' '.($n >= 3 && $n <= 10 ? $few : $many);
            }
        }
        return $t['every'].' '.$plan->billing_period.' '.trans_choice('services.billing_cycles.'.$plan->billing_unit, $plan->billing_period);
    }
    public static function plan($product)
    {
        $plans = $product->availablePlans();
        return $plans->firstWhere('type', 'free') ?? $plans->first();
    }
    public static function card($product): array
    {
        $t = self::strings(); $plan = self::plan($product); $price = $plan?->price();
        $available = $price && $price->available;
        return [
            'id' => (string)$product->id, 'name' => $product->name,
            'price' => $available ? ($price->is_free ? $t['free'] : $price->formatted->price) : $t['unavailable'],
            'setup' => $available && $price->has_setup_fee ? $price->formatted->setup_fee : $t['none'],
            'cycle' => self::cycle($plan), 'specs' => self::specs($product),
            'stock' => $product->stock === 0 ? $t['out_of_stock'] : $t['available'],
            'recommended' => in_array($product->id, self::ids(theme('recommended_products', '')), true),
            'url' => route('products.show', [$product->category, $product]),
            'checkout' => $available && $product->stock !== 0 ? route('products.checkout', [$product->category, $product, 'plan'=>$plan->id]) : '',
        ];
    }
    public static function featured()
    {
        $ids = array_slice(self::ids(theme('featured_products', '')), 0, 6);
        $query = \App\Models\Product::query()->where('hidden', false)->with(['category','plans.prices.currency']);
        if ($ids) $query->whereIn('id', $ids);
        $products = $query->orderBy('sort')->orderBy('id')->limit(6)->get()->filter(fn ($p) => $p->category !== null);
        return $ids ? $products->sortBy(fn ($p) => array_search($p->id, $ids, true))->values() : $products->take(3);
    }
    public static function faq(): array
    {
        $t = self::strings(); $result=[];
        for ($i=1; $i<=5; $i++) {
            $q = theme('faq_'.$i.'_q_'.self::locale(), $t['faq_'.$i.'_q'] ?? '');
            $a = theme('faq_'.$i.'_a_'.self::locale(), $t['faq_'.$i.'_a'] ?? '');
            if (is_string($q) && is_string($a) && trim($q) !== '' && trim($a) !== '') $result[]=['q'=>$q,'a'=>$a];
        }
        return $result;
    }
    public static function date($date): string { return $date ? $date->copy()->locale(self::locale())->translatedFormat('d M Y') : '—'; }
    public static function dateTime($date): string { return $date ? self::date($date).' · '.$date->format('H:i') : '—'; }
}

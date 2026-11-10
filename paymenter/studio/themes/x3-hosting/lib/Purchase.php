<?php
namespace X3\Rackline;
require_once __DIR__.'/Theme.php';

/** Presentation for native products. Never reads provisioning settings or changes prices. */
final class Purchase
{
    public static function strings(): array { return require __DIR__.'/../lang/'.Theme::locale().'/purchase.php'; }
    public static function text(?string $html): string
    {
        $html=preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is','',$html ?? '');
        $html=preg_replace('~<br\s*/?>|</(?:p|div|li|h[1-6]|section|tr)>~i',"\n",$html);
        return html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8');
    }
    public static function specs($product): array
    {
        $result=Theme::specs($product);
        $labels=[
            'cpu'=>'CPU(?:\s+(?:Limit|Allocation|Cores?))?|Processor|vCPU|المعالج|حد المعالج',
            'ram'=>'RAM|Memory|الرام|الذاكرة',
            'storage'=>'(?:SSD|NVMe)(?:\s+Storage)?|Storage|Disk(?:\s+Space)?|التخزين|المساحة',
            'network'=>'Network|Bandwidth|Traffic|الشبكة|نقل البيانات',
            'location'=>'Location|Region|الموقع|المنطقة',
        ];
        foreach(preg_split('/\R/u',self::text($product->description)) as $line) {
            $line=trim(preg_replace('/^[\s>*#\-+]+/u','',$line));
            $line=str_replace(['**','__','`'],'',$line);
            foreach($labels as $key=>$pattern) if(!isset($result[$key]) && preg_match('/^(?:'.$pattern.')\s*[:：]\s*(.+)$/iu',$line,$match)) $result[$key]=mb_substr(trim($match[1]),0,180);
        }
        $ordered=[];foreach(array_keys($labels) as $key)if(isset($result[$key]))$ordered[$key]=$result[$key];
        return $ordered;
    }
    public static function intro($product): string
    {
        foreach(preg_split('/\R/u',self::text($product->description)) as $line) {
            $line=trim($line);
            if($line==='' || preg_match('/^(?:[#>*\-+]|(?:RAM|CPU|SSD|NVMe|Price|Server Specifications|Included Features|المواصفات|المميزات)\b)/iu',$line))continue;
            if(str_contains(mb_strtolower($line),mb_strtolower($product->name)))continue;
            return mb_strimwidth($line,0,210,'…','UTF-8');
        }
        return '';
    }
    public static function description(?string $html): string
    {
        if(!$html)return '';
        if(!preg_match('~<(?:p|div|ul|ol|table|h[1-6])\b~i',$html)) {
            $html=\Illuminate\Support\Str::markdown($html,['html_input'=>'allow','allow_unsafe_links'=>false]);
        } else {
            // RichEditor may wrap pasted Markdown in individual paragraphs.
            $html=preg_replace_callback('~<p\b[^>]*>(.*?)</p>~is',static function($match) {
                $text=trim(self::text($match[1]));
                if($text==='' && !preg_match('~<(img|video|audio)\b~i',$match[1]))return '';
                if(preg_match('/^(?:#{1,6}\s|[-*+]\s|\d+\.\s)/u',$text))return \Illuminate\Support\Str::markdown($match[1],['html_input'=>'allow','allow_unsafe_links'=>false]);
                return $match[0];
            },$html);
            $html=preg_replace('~</ul>\s*<ul>~i','',$html);
        }
        return \Illuminate\Support\Str::sanitizeHtml($html);
    }
    public static function plans($product): array
    {
        $t=self::strings();$result=[];
        foreach($product->availablePlans() as $plan) {
            $price=$plan->price();if(!$price->available)continue;
            $result[]=['id'=>(string)$plan->id,'name'=>$plan->name,'cycle'=>Theme::cycle($plan),
                'price'=>$price->is_free?Theme::strings()['free']:$price->formatted->price,
                'setup'=>$price->has_setup_fee?$price->formatted->setup_fee:$t['no_setup'],
                'initial'=>$price->is_free?Theme::strings()['free']:$price->formatted->total,
                'checkout'=>$product->stock!==0?route('products.checkout',[$product->category,$product,'plan'=>$plan->id]):'',
                'recurring'=>$plan->type==='recurring'];
        }
        return $result;
    }
    public static function selected($product,array $plans): ?array
    {
        $requested=request()->query('plan');
        $id=is_string($requested) && ctype_digit($requested)?$requested:(string)(Theme::plan($product)?->id ?? '');
        return collect($plans)->firstWhere('id',$id) ?? ($plans[0] ?? null);
    }
    public static function support(): string
    {
        return Theme::url(theme('support_url',''),config('settings.tickets_disabled',false)?'':route('tickets.create'));
    }
}

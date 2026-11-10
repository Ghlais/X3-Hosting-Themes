<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$options=getopt('',['root:']);$root=realpath($options['root']??'');
if(!$root||!is_file($root.'/vendor/autoload.php')){fwrite(STDERR,"Usage: php tools/check.php --root=/var/www/paymenter\n");exit(1);}
$errors=[];$warnings=[];$views=0;
try {
    $expected=json_decode(file_get_contents(__DIR__.'/../PAYLOAD-SHA256.json'),true,512,JSON_THROW_ON_ERROR);
    foreach($expected as $path=>$hash) if(!is_file($root.'/'.$path)||!hash_equals($hash,hash_file('sha256',$root.'/'.$path)))$errors[]='Missing/changed: '.$path;
    require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $active=config('settings.theme'); if($active!=='x3-hosting')$warnings[]='Active theme is '.($active??'unset').'; select x3-hosting in administration.';
    \Qirolab\Theme\Theme::set('x3-hosting','default');
    $resolved=realpath(view()->getFinder()->find('home'));
    if($resolved!==realpath($root.'/themes/x3-hosting/views/home.blade.php'))$errors[]='Home resolved outside the installed X3 theme; check nested upload folders.';
    foreach(array_keys($expected) as $path) if(str_ends_with($path,'.php') && is_file($root.'/'.$path)) {
        $code=file_get_contents($root.'/'.$path);
        if(str_ends_with($path,'.blade.php')){$code=app('blade.compiler')->compileString($code);$views++;}
        try{token_get_all($code,TOKEN_PARSE);}catch(Throwable $e){$errors[]=$path.': '.$e->getMessage();}
    }
    $manifest=json_decode(file_get_contents($root.'/public/x3-hosting/manifest.json'),true,512,JSON_THROW_ON_ERROR);
    foreach(['themes/x3-hosting/js/app.js','themes/x3-hosting/css/app.css','themes/x3-hosting/js/easymde-entry.js'] as $input)if(!isset($manifest[$input]))$errors[]='Vite entry missing: '.$input;
    foreach($manifest as $entry)foreach(array_merge([$entry['file']??''],$entry['css']??[],$entry['assets']??[]) as $asset)if($asset && !is_file($root.'/public/x3-hosting/'.$asset))$errors[]='Built asset missing: '.$asset;
    $en=require $root.'/themes/x3-hosting/lang/en/ui.php';$ar=require $root.'/themes/x3-hosting/lang/ar/ui.php';
    if(array_diff_key($en,$ar)||array_diff_key($ar,$en))$errors[]='Arabic/English translation keys differ';
    echo json_encode(['theme'=>'X3 Hosting 1.1.0','paymenter'=>config('app.version'),'php'=>PHP_VERSION,'active_theme'=>$active,'home'=>$resolved,'compiled_views'=>$views,'errors'=>$errors,'warnings'=>$warnings],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'X3 check: '.$e->getMessage().PHP_EOL);exit(1);}
exit($errors?1:0);

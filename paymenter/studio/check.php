<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$o=getopt('',['root:']);$root=realpath($o['root']??'');$errors=[];$warnings=[];$views=0;$php=0;
try {
 if(!$root||!is_file($root.'/vendor/autoload.php'))throw new RuntimeException('Usage: php check.php --root=/var/www/paymenter');
 $manifest=json_decode(file_get_contents(__DIR__.'/FILES-SHA256.json'),true,512,JSON_THROW_ON_ERROR);
 foreach($manifest as $rel=>$hash){if(!is_file($root.'/'.$rel)||!hash_equals($hash,hash_file('sha256',$root.'/'.$rel)))$errors[]='Missing/changed: '.$rel;}
 require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
 $active=config('settings.theme');if($active!=='x3-hosting')$warnings[]='Select x3-hosting in administration. Active theme: '.($active??'unset');
 \Qirolab\Theme\Theme::set('x3-hosting','default');
 $layout=file_get_contents($root.'/themes/x3-hosting/views/layouts/app.blade.php');if(substr_count($layout,'<x-x3.depth-assets />')!==1)$errors[]='Layout must contain exactly one depth-assets include';
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/themes/x3-hosting',FilesystemIterator::SKIP_DOTS)) as $file){
  if(!$file->isFile()||!str_ends_with($file->getFilename(),'.php'))continue;
  try{$code=file_get_contents($file->getPathname());if(str_ends_with($file->getFilename(),'.blade.php')){$code=app('blade.compiler')->compileString($code);$views++;}else $php++;token_get_all($code,TOKEN_PARSE);}catch(Throwable $e){$errors[]=$file->getFilename().': '.$e->getMessage();}
 }
 if(!\Illuminate\Support\Str::hasMacro('sanitizeHtml'))$errors[]='Native Filament HTML sanitizer is unavailable';
 foreach(['home','products.index','products.show','products.checkout','cart','dashboard','services.index','services.show','invoices.index','invoices.show','tickets.index','tickets.create','tickets.show','client.account.index','auth.login','auth.register'] as $name){$resolved=realpath(view()->getFinder()->find($name));$expected=realpath($root.'/themes/x3-hosting/views/'.str_replace('.','/',$name).'.blade.php');if($resolved!==$expected)$errors[]='View resolves outside X3: '.$name;}
 foreach(['purchase','depth'] as $group){$en=require $root.'/themes/x3-hosting/lang/en/'.$group.'.php';$ar=require $root.'/themes/x3-hosting/lang/ar/'.$group.'.php';if(array_keys($en)!==array_keys($ar))$errors[]=$group.' translation keys differ';}
 echo json_encode(['interface'=>'X3 Studio 3D 1.4.0 + Purchase 1.2.0','paymenter'=>config('app.version'),'php'=>PHP_VERSION,'active_theme'=>$active,'verified_files'=>count($manifest),'compiled_views'=>$views,'php_files'=>$php,'errors'=>$errors,'warnings'=>$warnings],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'X3 Studio check: '.$e->getMessage().PHP_EOL);exit(1);}
exit($errors||$warnings?1:0);

<?php
/** X3 Studio 1.4.0. CLI only; additive layout include; no core or database writes. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
umask(0022);
const X3_FILES=[
 'public/x3-hosting/depth-v1.3.0.css',
 'public/x3-hosting/depth-v1.3.0.js',
 'public/x3-hosting/purchase-v1.2.0.css',
 'public/x3-hosting/studio-v1.4.0.css',
 'themes/x3-hosting/lang/ar/depth.php',
 'themes/x3-hosting/lang/ar/purchase.php',
 'themes/x3-hosting/lang/en/depth.php',
 'themes/x3-hosting/lang/en/purchase.php',
 'themes/x3-hosting/lib/Purchase.php',
 'themes/x3-hosting/views/auth/login.blade.php',
 'themes/x3-hosting/views/auth/register.blade.php',
 'themes/x3-hosting/views/cart.blade.php',
 'themes/x3-hosting/views/client/account/credits.blade.php',
 'themes/x3-hosting/views/client/account/index.blade.php',
 'themes/x3-hosting/views/client/account/notifications.blade.php',
 'themes/x3-hosting/views/client/account/payment-methods.blade.php',
 'themes/x3-hosting/views/client/account/security.blade.php',
 'themes/x3-hosting/views/components/x3/depth-assets.blade.php',
 'themes/x3-hosting/views/components/x3/hardware.blade.php',
 'themes/x3-hosting/views/components/x3/purchase-assets.blade.php',
 'themes/x3-hosting/views/components/x3/rack.blade.php',
 'themes/x3-hosting/views/components/x3/studio-panel.blade.php',
 'themes/x3-hosting/views/components/x3/studio-scene.blade.php',
 'themes/x3-hosting/views/dashboard.blade.php',
 'themes/x3-hosting/views/home.blade.php',
 'themes/x3-hosting/views/invoices/index.blade.php',
 'themes/x3-hosting/views/invoices/show.blade.php',
 'themes/x3-hosting/views/products/checkout.blade.php',
 'themes/x3-hosting/views/products/index.blade.php',
 'themes/x3-hosting/views/products/show.blade.php',
 'themes/x3-hosting/views/services/index.blade.php',
 'themes/x3-hosting/views/services/show.blade.php',
 'themes/x3-hosting/views/services/upgrade.blade.php',
 'themes/x3-hosting/views/tickets/create.blade.php',
 'themes/x3-hosting/views/tickets/index.blade.php',
 'themes/x3-hosting/views/tickets/show.blade.php',
];
const X3_LAYOUT='themes/x3-hosting/views/layouts/app.blade.php';
const X3_INCLUDE='<x-x3.depth-assets />';
const X3_ALL=[...X3_FILES,X3_LAYOUT];
function x3Fail(string $message): never {throw new RuntimeException($message);}
function x3Copy(string $from,string $to): void {
 if(!is_dir(dirname($to))&&!mkdir(dirname($to),0755,true))x3Fail('Cannot create '.dirname($to));
 if(!copy($from,$to))x3Fail('Cannot copy '.$from);
}
function x3Atomic(string $from,string $to): void {
 if(!is_dir(dirname($to))&&!mkdir(dirname($to),0755,true))x3Fail('Cannot create '.dirname($to));
 $tmp=tempnam(dirname($to),'.x3-purchase-');
 if(!$tmp || !copy($from,$tmp))x3Fail('Cannot stage '.$to);
 chmod($tmp,0644);
 for($i=0;$i<6;$i++){if(@rename($tmp,$to))return;clearstatcache(true,$to);usleep(50000*(2**$i));}
 if(is_file($tmp))unlink($tmp);x3Fail('Cannot replace '.$to);
}
function x3Views(string $root): void {
 $p=proc_open([PHP_BINARY,$root.'/artisan','view:clear'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
 if(!is_resource($p))x3Fail('Cannot clear compiled views');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
 if(proc_close($p)!==0)x3Fail('view:clear failed: '.$out.' '.$err);
 echo trim($out).PHP_EOL;
}
function x3Snapshot(string $root,string $folder): array {
 $state=[];
 foreach(X3_ALL as $rel){$exists=is_file($root.'/'.$rel);$state[$rel]=['existed'=>$exists,'hash'=>$exists?hash_file('sha256',$root.'/'.$rel):null];if($exists)x3Copy($root.'/'.$rel,$folder.'/'.$rel);}
 return $state;
}
function x3Restore(string $root,string $folder,array $state): void {
 foreach(X3_ALL as $rel){if($state[$rel]['existed'])x3Atomic($folder.'/'.$rel,$root.'/'.$rel);elseif(is_file($root.'/'.$rel)&&!unlink($root.'/'.$rel))x3Fail('Cannot restore absent file '.$rel);}
}
try {
 if(version_compare(PHP_VERSION,'8.3','<'))x3Fail('PHP 8.3+ required');
 $o=getopt('',['root:','apply','rollback:']);$root=isset($o['root'])?realpath($o['root']):false;
 if(!$root||!is_file($root.'/artisan')||!is_file($root.'/config/app.php'))x3Fail('Usage: php install.php --root=/var/www/paymenter [--apply]');
 preg_match("/['\"]version['\"]\s*=>\s*['\"]([^'\"]+)/",file_get_contents($root.'/config/app.php'),$v);
 if(($v[1]??'')!=='1.5.9')x3Fail('Validated target is Paymenter 1.5.9. Detected '.($v[1]??'unknown'));
 foreach(['themes/x3-hosting/lib/Theme.php','themes/x3-hosting/theme.php',X3_LAYOUT,'public/x3-hosting/manifest.json'] as $base)if(!is_file($root.'/'.$base))x3Fail('Install X3 Rackline 1.1.0 first. Missing '.$base);
 foreach(X3_ALL as $rel){$parts=explode('/',$rel);$path=$root;foreach($parts as $part){$path.='/'.$part;if(is_link($path))x3Fail('Symlink target rejected: '.$path);}if(file_exists($path)&&!is_file($path))x3Fail('Expected a file: '.$rel);}
 foreach(['themes','public'] as $dir)if(!is_writable($root.'/'.$dir))x3Fail('Not writable: '.$dir);
 $package=str_replace('\\','/',__DIR__);$normalized=str_replace('\\','/',$root);
 if(str_starts_with($package.'/',$normalized.'/public/')||str_starts_with($package.'/',$normalized.'/themes/'))x3Fail('Extract outside public/ and themes/, for example /tmp/X3-Studio-3D-v1.4.0');
 $backups=$root.'/storage/app/x3-studio-backups';
 if(isset($o['rollback'])) {
  $folder=realpath($o['rollback']);$base=realpath($backups);
  if(!$folder||!$base||!str_starts_with($folder,$base.DIRECTORY_SEPARATOR))x3Fail('Choose a backup under '.$backups);
  $journal=json_decode(file_get_contents($folder.'/state.json'),true,512,JSON_THROW_ON_ERROR);
  if(($journal['root']??'')!==$root||($journal['restored']??false))x3Fail('Wrong backup or already restored');
  $state=$journal['files'];if(array_diff(X3_ALL,array_keys($state))||count($state)!==count(X3_ALL))x3Fail('Invalid backup file list');
  foreach($state as $rel=>$item)if($item['existed']&&(!is_file($folder.'/previous/'.$rel)||!hash_equals($item['hash'],hash_file('sha256',$folder.'/previous/'.$rel))))x3Fail('Backup damaged: '.$rel);
  if(!isset($o['apply'])){echo "Rollback preflight OK. Add --apply to restore.\n";exit;}
  $saved=$folder.'/replaced-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(3));$current=x3Snapshot($root,$saved);
  try{x3Restore($root,$folder.'/previous',$state);x3Views($root);}catch(Throwable $e){x3Restore($root,$saved,$current);throw $e;}
  $journal['restored']=true;file_put_contents($folder.'/state.json',json_encode($journal,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo "Studio update rolled back. Replaced files saved in $saved\n";exit;
 }
 $manifest=json_decode(file_get_contents(__DIR__.'/FILES-SHA256.json'),true,512,JSON_THROW_ON_ERROR);
 if(count($manifest)!==count(X3_FILES)||array_diff(X3_FILES,array_keys($manifest)))x3Fail('Invalid update payload');
 $same=true;
 foreach($manifest as $rel=>$hash){if(!is_file(__DIR__.'/'.$rel)||is_link(__DIR__.'/'.$rel)||!hash_equals($hash,hash_file('sha256',__DIR__.'/'.$rel)))x3Fail('Package changed or incomplete: '.$rel);if(!is_file($root.'/'.$rel)||!hash_equals($hash,hash_file('sha256',$root.'/'.$rel)))$same=false;}
 $layout=file_get_contents($root.'/'.X3_LAYOUT);$count=substr_count($layout,X3_INCLUDE);
 if($count>1)x3Fail('Duplicate depth include in layout. Resolve before installing.');
 if($count===0){if(substr_count($layout,'</head>')!==1)x3Fail('Custom layout has no unique closing head tag; cannot safely inject assets.');$updated=str_replace('</head>','    '.X3_INCLUDE.PHP_EOL.'</head>',$layout);$same=false;}else $updated=$layout;
 echo "X3 Studio 1.4.0: 36 payload files verified; 1 additive layout include. Footer, branding and settings preserved.\n";
 if(!isset($o['apply'])){echo "Preflight OK. Add --apply to install.\n";exit;}
 if($same){echo "Studio update already installed; no files changed.\n";exit;}
 $folder=$backups.'/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));if(!mkdir($folder,0700,true))x3Fail('Cannot create private backup');
 $state=x3Snapshot($root,$folder.'/previous');$journal=['root'=>$root,'version'=>'1.4.0','files'=>$state,'restored'=>false];file_put_contents($folder.'/state.json',json_encode($journal,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 $stagedLayout=$folder.'/layout.next';if(file_put_contents($stagedLayout,$updated)===false)x3Fail('Cannot stage layout');
 try{foreach(X3_FILES as $rel)x3Atomic(__DIR__.'/'.$rel,$root.'/'.$rel);x3Atomic($stagedLayout,$root.'/'.X3_LAYOUT);x3Views($root);}catch(Throwable $e){x3Restore($root,$folder.'/previous',$state);throw $e;}
 echo "Installed X3 Studio 1.4.0, including purchase interface 1.2.0. Keep active theme: x3-hosting.\nBackup: $folder\n";
 echo 'Rollback: php '.escapeshellarg(__FILE__).' --root='.escapeshellarg($root).' --rollback='.escapeshellarg($folder)." --apply\n";
}catch(Throwable $e){fwrite(STDERR,'X3 Studio: '.$e->getMessage().PHP_EOL);exit(1);}

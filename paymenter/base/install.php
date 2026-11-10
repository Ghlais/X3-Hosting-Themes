<?php
/** X3 Hosting theme installer. CLI only; never place this package under public/. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
umask(0022);
function fail(string $message): never { throw new RuntimeException($message); }
function copyTree(string $source, string $target): void {
    if (is_link($source)) fail('Symlink payload rejected: '.$source);
    if (is_dir($source)) {
        if (!is_dir($target) && !mkdir($target,0755,true)) fail('Cannot create '.$target);
        foreach (new DirectoryIterator($source) as $entry) if (!$entry->isDot()) copyTree($entry->getPathname(),$target.'/'.$entry->getFilename());
    } elseif (!is_file($source) || !copy($source,$target)) fail('Copy failed: '.$source);
    else chmod($target,0644);
}
function moveTree(string $source,string $target): void {
    // Windows scanners can briefly hold a directory immediately after a copy/rename.
    for($attempt=0;$attempt<6;$attempt++) {
        clearstatcache(true,$source);clearstatcache(true,$target);
        if(file_exists($target)) fail('Move target already exists: '.$target);
        if(@rename($source,$target)) return;
        if($attempt<5) usleep(50000 * (2 ** $attempt));
    }
    fail('Cannot move '.$source.' to '.$target);
}
function clearViews(string $root): void {
    $process=proc_open([PHP_BINARY,$root.'/artisan','view:clear'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
    if (!is_resource($process)) fail('Cannot run view:clear');
    fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]);fclose($pipes[2]);
    if (proc_close($process)!==0) fail('view:clear failed. '.$out.' '.$err);
    echo trim($out).PHP_EOL;
}
function verifyPayload(string $package): array {
    $manifest=json_decode(file_get_contents($package.'/PAYLOAD-SHA256.json'),true,512,JSON_THROW_ON_ERROR);
    foreach ($manifest as $relative=>$expected) {
        if (!preg_match('~^(themes|public)/x3-hosting/[A-Za-z0-9_./-]+$~D',$relative) || str_contains($relative,'..')) fail('Invalid payload path');
        $path=$package.'/'.$relative;
        if (!is_file($path) || is_link($path) || !hash_equals($expected,hash_file('sha256',$path))) fail('Missing/changed package file: '.$relative);
    }
    foreach (['themes/x3-hosting','public/x3-hosting'] as $directory) {
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($package.'/'.$directory,FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isLink()) fail('Symlink payload rejected');
            if ($file->isFile()) {
                $relative=str_replace('\\','/',substr($file->getPathname(),strlen($package)+1));
                if (!isset($manifest[$relative])) fail('Unexpected payload file: '.$relative);
            }
        }
    }
    return $manifest;
}
try {
    if (version_compare(PHP_VERSION,'8.3','<')) fail('PHP 8.3 or later is required.');
    $options=getopt('',['root:','apply','rollback:','check']);
    $root=realpath($options['root'] ?? '');
    if (!$root || !is_file($root.'/artisan') || !is_file($root.'/config/app.php') || !is_dir($root.'/themes/default')) fail('Specify --root=/absolute/path/to/paymenter');
    foreach (['themes','public'] as $parent) if (is_link($root.'/'.$parent) || !is_writable($root.'/'.$parent)) fail('Target parent must be a writable real directory: '.$parent);
    preg_match("/['\"]version['\"]\\s*=>\\s*['\"]([^'\"]+)/",file_get_contents($root.'/config/app.php'),$match);
    if (($match[1] ?? '') !== '1.5.9') fail('This release is validated for Paymenter 1.5.9. Detected: '.($match[1] ?? 'unknown'));
    $package=__DIR__;
    if (str_starts_with(str_replace('\\','/',$package).'/',str_replace('\\','/',$root).'/public/')) fail('Extract the package outside public/.');
    $targets=['theme'=>$root.'/themes/x3-hosting','assets'=>$root.'/public/x3-hosting'];
    foreach($targets as $target) {
        if (is_link($target) || (file_exists($target) && !is_dir($target))) fail('Expected real directory: '.$target);
        if (str_starts_with(str_replace('\\','/',$package).'/',str_replace('\\','/',$target).'/')) fail('Extract the package outside the theme target, for example /tmp/x3-release.');
    }
    $backupBase=$root.'/storage/app/x3-theme-backups';
    if (isset($options['rollback'])) {
        $backup=realpath($options['rollback']); $base=realpath($backupBase);
        if (!$backup || !$base || !str_starts_with($backup,$base.DIRECTORY_SEPARATOR)) fail('Rollback must use a backup created under '.$backupBase);
        $journal=json_decode(file_get_contents($backup.'/state.json'),true,512,JSON_THROW_ON_ERROR);
        if (($journal['root'] ?? '')!==$root || ($journal['restored'] ?? false)) fail('Backup belongs to another install or was already restored.');
        foreach($targets as $key=>$target) {
            if (($journal['existed'][$key] ?? false) && !is_dir($backup.'/'.$key)) fail('Incomplete backup: '.$key);
            if (file_exists($backup.'/before-rollback-'.$key)) fail('Rollback already started. Inspect this backup before retrying.');
        }
        if (!isset($options['apply'])) { echo "Rollback preflight OK. Add --apply to restore this backup.\n"; exit(0); }
        $moved=[]; $restored=[];
        try {
            foreach($targets as $key=>$target) {
                if (is_dir($target)) { moveTree($target,$backup.'/before-rollback-'.$key); $moved[]=$key; }
                if ($journal['existed'][$key]) { moveTree($backup.'/'.$key,$target); $restored[]=$key; }
            }
            clearViews($root);
        } catch(Throwable $error) {
            foreach(array_reverse($restored) as $key) moveTree($targets[$key],$backup.'/'.$key);
            foreach(array_reverse($moved) as $key) moveTree($backup.'/before-rollback-'.$key,$targets[$key]);
            throw $error;
        }
        $journal['restored']=true;file_put_contents($backup.'/state.json',json_encode($journal,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        echo "Rollback complete. Replaced version preserved in: $backup\n"; exit(0);
    }
    $manifest=verifyPayload($package);
    echo 'Package checksum OK: '.count($manifest)." files; Paymenter 1.5.9; PHP ".PHP_VERSION."\n";
    if (!isset($options['apply'])) { echo "Preflight OK. Add --apply to install. Only themes/x3-hosting and public/x3-hosting are replaced.\n"; exit(0); }
    $id=gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
    $backup=$backupBase.'/'.$id;
    if (!mkdir($backup,0700,true)) fail('Cannot create private backup folder');
    $journal=['root'=>$root,'version'=>'1.1.0','existed'=>array_map('is_dir',$targets),'restored'=>false];
    file_put_contents($backup.'/state.json',json_encode($journal,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    $stages=['theme'=>$root.'/themes/.x3-stage-'.$id,'assets'=>$root.'/public/.x3-stage-'.$id];
    copyTree($package.'/themes/x3-hosting',$stages['theme']);
    copyTree($package.'/public/x3-hosting',$stages['assets']);
    $moved=[]; $installed=[];
    try {
        foreach($targets as $key=>$target) {
            if ($journal['existed'][$key]) { moveTree($target,$backup.'/'.$key); $moved[]=$key; }
            moveTree($stages[$key],$target); $installed[]=$key;
        }
        clearViews($root);
    } catch(Throwable $error) {
        foreach(array_reverse($installed) as $key) moveTree($targets[$key],$backup.'/failed-new-'.$key);
        foreach(array_reverse($moved) as $key) moveTree($backup.'/'.$key,$targets[$key]);
        throw $error;
    }
    echo "Installed X3 Hosting 1.1.0. Backup: $backup\n";
    echo "Select x3-hosting in Paymenter administration if it is not already active. Existing settings are retained.\n";
    echo 'Rollback: php '.escapeshellarg(__FILE__).' --root='.escapeshellarg($root).' --rollback='.escapeshellarg($backup)." --apply\n";
} catch(Throwable $error) { fwrite(STDERR,'X3: '.$error->getMessage().PHP_EOL); exit(1); }

<?php

if (getenv('VERCEL') && file_exists(__DIR__.'/../database/vercel.sqlite')) {
	$runtimeDatabase = '/tmp/spin_wheels.sqlite';

	if (! file_exists($runtimeDatabase)) {
		copy(__DIR__.'/../database/vercel.sqlite', $runtimeDatabase);
	}

	putenv('DB_CONNECTION=sqlite');
	putenv('DB_DATABASE='.$runtimeDatabase);
	putenv('SESSION_DRIVER=cookie');
	putenv('CACHE_STORE=array');
}

require_once __DIR__.'/../public/index.php';
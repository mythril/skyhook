<?php

require_once 'vendor/autoload.php';
require_once 'includes/autoload.php';

//TODO shut off display errors, except when on a dev machine
ini_set('display_errors', 'on');
error_reporting(E_ALL);

Localization::init();

set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
	if (!(error_reporting() & $errno)) {
		return;
	}
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

Container::registerSingleton('Environment\Session', function () {
	ini_set('session.name', 'skyhook');
	// 10 minute maximum session time
	ini_set('session.gc_maxlifetime', '600');
	// Session gc'd every time a session is created (shouldn't be often).
	ini_set('session.gc_probability', '100');
	ini_set('session.gc_divisor', '100');
	// Prevents session fixation
	ini_set('session.use_strict_mode', '1');
	// Causes cookie to expire when browser is closed
	ini_set('session.cookie_lifetime', '0');
	// Prevents client-side XSS attacks
	ini_set('session.cookie_httponly', '1');
	session_start();
	$regenerator = function ($delete) {
		$_SESSION = [];
		session_regenerate_id($delete);
		return session_id();
	};
	return new Environment\Session(
		$_SESSION,
		session_id(),
		'skyhook',
		$regenerator
	);
});


// Register Environment Singletons
foreach (array(
	'Get' => &$_GET,
	'Post' => &$_POST,
	'Server' => &$_SERVER,
	'PostFiles' => &$_FILES,
	'Cookie' => &$_COOKIE,
) as $class => $glbl) {
	$c = "Environment\\" . $class;
	Container::registerSingleton($c, function () use ($c, $glbl) {
		return new $c($glbl);
	});
}

$cookies = Container::dispense("Environment\\Cookie");

if (isset($cookies['lang']) && Localization::localePresent($cookies['lang'])) {
	Localization::setLocale($cookies['lang']);
}

Container::registerSingleton('Environment\RequestHeaders', function () {
	$headers = getallheaders();
	return new Environment\RequestHeaders($headers);
});

foreach (array(
	'Delete',
	'Put',
) as $wrapper) {
	Container::registerSingleton($wrapper, function () use ($wrapper) {
		$server = Container::dispense(Environment\Server);
		$nsed = "Environment\\" . $wrapper;
		$wrapped = $nsed::buildHelper($server);
		return new $nsed($wrapped);
	});
}

Container::registerSingleton('DB', function () {
	return new DB(new DateTimeZone(trim(file_get_contents('/etc/timezone'))));
});

date_default_timezone_set(trim(file_get_contents('/etc/timezone')));

$router = Container::dispense("Router");
$server = Container::dispense('Environment\Server');

$result = $router->resolve(
	$server->pageUrl(),
	[
		['/on$', function () {
			echo JSON::encode(['on' => !file_exists('command')]);
			return true;
		}],
		['/settings.js', Router::lazyLoad('Controllers\SettingsData')],
		['/test-price$', Router::lazyLoad('Controllers\Ajax\TestPrice')],
		['/admin', Router::lazyLoad('Controllers\Admin')],
		
		//Checks the config before any other routes are resolved.
		['', Container::dispense('Controllers\ConfigChecker')],
		['/check-balance', Router::lazyLoad('Controllers\CheckBalance')],
		
		['/finalize/:ticket$', Router::lazyLoad('Controllers\Ajax\FinalizePurchase')],
		['/ajax', Router::lazyLoad('Controllers\Ajax')],
		['/validate/:address$', Router::lazyLoad('Controllers\Ajax\ValidateBitcoinAddress')],
		['/add-email-to-ticket/:ticket', Router::lazyLoad('Controllers\Ajax\AddEmailToTicket')],
		['/nettest', Router::lazyLoad('Controllers\NetworkTester')],
		['/receipt/:ticket$', Router::lazyLoad('Controllers\Receipt')],
		['/error/:ticket$', Router::lazyLoad('Controllers\Error')],
		['/purchase/:address/:ticket$', Router::lazyLoad('Controllers\FinishPurchase')],
		['/price$', Router::lazyLoad('Controllers\Price')],
		['/billscan-balance/:ticket$', Router::lazyLoad('Controllers\BillScannerBalance')],
		['/email-receipt', Router::lazyLoad('Controllers\EmailReceipt')],
		['/start-purchase/:address$', Router::lazyLoad('Controllers\Ajax\StartPurchase')],
		
		//Checks the known connectivity before any other routes are resolved.
		['', Container::dispense('Controllers\ConnectivityChecker')],
		
		['/start$', Router::lazyLoad('Controllers\Start')],
		['/account$', Router::lazyLoad('Controllers\Account')],
		['/purchase/:address$', Router::lazyLoad('Controllers\StartPurchase')],
		['/bust$', Router::lazyLoad('Controllers\CacheBust')],
		['/?$', Router::lazyLoad('Controllers\Start')],
		['', function () {
			header('HTTP/1.1 404 Not Found.');
			echo '404 Not Found.';
			return true;
		}]
	]
);





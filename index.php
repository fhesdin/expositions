<?php
declare(strict_types=1);

/**
 * expositions.top — Front controller (routage ?r=/path, convention de la stack).
 */

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core\\' => __DIR__ . '/core/',
        'App\\'  => __DIR__ . '/modules/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $baseDir . $relative . '.php';
            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
});

require_once __DIR__ . '/core/helpers.php';

if (!function_exists('e'))        { function e(mixed $v): string         { return \Core\e($v); } }
if (!function_exists('url'))      { function url(string $p = ''): string  { return \Core\url($p); } }
if (!function_exists('route'))    { function route(string $p = ''): string { return \Core\route($p); } }
if (!function_exists('redirect')) { function redirect(string $p, int $c = 302): void { \Core\redirect($p, $c); } }
if (!function_exists('slugify'))  { function slugify(string $t): string   { return \Core\slugify($t); } }
if (!function_exists('date_fr'))  { function date_fr(?string $d, string $f = 'd/m/Y H:i'): string { return \Core\date_fr($d, $f); } }
if (!function_exists('excerpt'))  { function excerpt(?string $t, int $l = 120): string { return \Core\excerpt($t, $l); } }

class_alias(\Core\View::class, 'View');
class_alias(\Core\Csrf::class, 'Csrf');
class_alias(\Core\Auth::class, 'Auth');
class_alias(\Core\Session::class, 'Session');

\Core\Config::load();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

\Core\Session::start();

\Core\View::share('currentUser', \Core\Auth::user());
\Core\View::share('auth', \Core\Auth::class);

$router = new \Core\Router();
$routes = require __DIR__ . '/config/routes.php';
$routes($router);

$router->dispatch(\Core\Request::method(), \Core\Request::path());
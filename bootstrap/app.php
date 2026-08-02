<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

// date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
date_default_timezone_set('Asia/Kolkata');
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/
//$app->register(Illuminate\Queue\QueueServiceProvider::class);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(Illuminate\Contracts\Encryption\Encrypter::class, function ($app) {
    $key = base64_decode(substr(env('APP_KEY'), 7)); // remove 'base64:' prefix
    $cipher = env('APP_CIPHER', 'AES-256-CBC');

    return new Illuminate\Encryption\Encrypter($key, $cipher);
});

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('queue');


$app->configure('logging');
// $app->configureMonologUsing(function ($monolog) {
//     $monolog->pushHandler(
//         new \Monolog\Handler\RotatingFileHandler(storage_path('logs/lumen.log'))
//     );
//     $monolog->setTimezone(new \DateTimeZone('Asia/Kolkata'));
// });

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     App\Http\Middleware\ExampleMiddleware::class
// ]);
$app->middleware([
    //App\Http\Middleware\ExampleMiddleware::class
    App\Http\Middleware\CorsMiddleware::class
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'throttle' => App\Http\Middleware\ThrottleRequests::class,
    'check.active' => App\Http\Middleware\CheckActiveUser::class,
    //'throttle' => \LumenRateLimiting\ThrottleRequests::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);

// $app->register(App\Providers\EventServiceProvider::class);



/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/
$app->withFacades(true, [
    Illuminate\Support\Facades\Mail::class => 'Mail',
]);

// Register the Mail service provider
$app->register(Illuminate\Mail\MailServiceProvider::class);

// Load mail configuration
$app->configure('mail');
$app->router->group([], function ($router) {
    require __DIR__.'/../routes/web.php';
});
$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'throttle' => App\Http\Middleware\ThrottleRequests::class,
    'decrypt' => App\Http\Middleware\DecryptAndValidatePayload::class,
     'decryptAfterLogin' => App\Http\Middleware\DecryptAndValidatePayloadAfterlogin::class,
]);
$app->routeMiddleware([
    'whitelist.ip' => App\Http\Middleware\CheckWhitelistedIp::class,
]);
$app->routeMiddleware([
    'check.blacklist' => App\Http\Middleware\CheckBlackListed::class,
]);
$app->routeMiddleware([
    'check.whitelist' => App\Http\Middleware\CheckWhiteListedAcc::class,
]);
$app->routeMiddleware([
    'check.service.time' => App\Http\Middleware\CheckServiceTime::class,
]);

return $app;

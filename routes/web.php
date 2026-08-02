<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$modulesPath = __DIR__ . '/../app/Modules';
$modules = scandir($modulesPath);

foreach ($modules as $module) {
    if ($module === '.' || $module === '..') continue;

    $routesFile = $modulesPath . '/' . $module . '/Routes/' . strtolower($module) . '.php';
    if (file_exists($routesFile)) {
        require $routesFile;
    }
}

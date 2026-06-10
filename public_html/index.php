<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use NeneField\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

// Do not advertise the PHP version (defense in depth; `expose_php` may be On).
header_remove('X-Powered-By');

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

$psr17 = $container->get(Psr17Factory::class);
assert($psr17 instanceof Psr17Factory);

$request = (new ServerRequestCreator($psr17, $psr17, $psr17, $psr17))->fromGlobals();

$application = $container->get(RequestHandlerInterface::class);
assert($application instanceof RequestHandlerInterface);

$response = $application->handle($request);

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);
$emitter->emit($response);

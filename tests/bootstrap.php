<?php

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

require dirname(__DIR__) . '/vendor/autoload.php';

// Ensure test environment
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

// Boot the Symfony kernel
$kernel = new \App\Kernel('test', true);
$kernel->boot();

// Get the entity manager
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Drop and recreate the schema before the test suite runs
$application = new Application($kernel);
$application->setAutoExit(false);

// Drop schema (ignore errors if it doesn't exist)
$dropInput = new ArrayInput([
    'command' => 'doctrine:schema:drop',
    '--force' => true,
    '--full-database' => true,
    '--env' => 'test',
]);
$application->run($dropInput);

// Create schema
$createInput = new ArrayInput([
    'command' => 'doctrine:schema:create',
    '--env' => 'test',
]);
$application->run($createInput);

// Optionally, close the kernel after schema setup
$kernel->shutdown();
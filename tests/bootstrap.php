<?php

declare(strict_types=1);

$mono = dirname(__DIR__, 3).'/vendor/autoload.php';
$loader = require is_file($mono) ? $mono : dirname(__DIR__).'/vendor/autoload.php';
$loader->addPsr4('Survos\\ConnectionsBundle\\', dirname(__DIR__).'/src');
$loader->addPsr4('Survos\\ConnectionsBundle\\Tests\\', __DIR__);
$loader->addPsr4('Survos\\ConnectionsContract\\', dirname(__DIR__, 3).'/lib/connections-contract/src');

#!/usr/bin/env php
<?php
require __DIR__. '/../vendor/autoload.php';

set_time_limit(0);
ini_set("memory_limit", "-1");

use Symfony\Component\Console\Application;

$application = new Application();

$getImagesCommand = new \Invoke\GetAlbumCovers();
$application->add($getImagesCommand);
$application->setDefaultCommand($getImagesCommand->getName());
$application->run();

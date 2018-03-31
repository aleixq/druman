#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Druman\AliasFactory;
$application = new Application();

$aliases = AliasFactory::generateAliases(getenv("HOME").'/.druman-aliases.yml');

// ... register commands
$application->add(new Druman\Command\ListProjectsCommand($aliases)); //  App\Command\ListProjectsCommand
$application->add(new Druman\Command\RunnerProjectsCommand($aliases)); //  App\Command\ListProjectsCommand
$application->add(new Druman\Command\ManagerRunnerProjectsCommand($aliases)); //  App\Command\ListProjectsCommand
$application->run();

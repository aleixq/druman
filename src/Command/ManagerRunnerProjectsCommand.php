<?php
// src/Command/ManagerRunnerProjectsCommand.php
namespace Druman\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


class ManagerRunnerProjectsCommand extends RunnerProjectsCommand
{
  protected function configure()
  {
    // ...
    $this
      // the name of the command (the part after "bin/console")
      ->setName('projects:update')

      // the short description shown while running "php bin/console list"
      ->setDescription('Run manager update command on each project.')

      // the full command description shown when running the command with
      // the "--help" option
      ->setHelp('This command allows you to run the manager update command on each projects that can be managed...')
      ->setDefinition(
        new InputDefinition([
          new InputOption('group', 'g', InputOption::VALUE_OPTIONAL, 'Run only on these projects which are members of specified group'),
          new InputOption('alias', 'a', InputOption::VALUE_OPTIONAL, 'Run only on this specific alias'),
	  new InputOption('all', 'all', InputOption::VALUE_NONE, 'Run in all alias, excluding those using drush8-alias manager'),
	  new InputOption('local', 'l', InputOption::VALUE_NONE, 'List only local projects'),
	  new InputOption('remote', 'r', InputOption::VALUE_NONE, 'List only remote projects'),
      ]));
  }

  protected function initialize(InputInterface $input, OutputInterface $output){
    $this->order = "null";
  }

  protected function executeInAlias(InputInterface $input, OutputInterface $output, $alias)
  {
    $result = 0;
    if ($alias['manager'] == 'drupal-composer' ){
      //  Process for Drupals with drupal-composer.
      // Set to maintenance mode:
      $this->order = "vendor/bin/drush sset system.maintenance_mode 1";
      $result += parent::executeInAlias($input, $output, $alias);
      //first update core
      $this->order = "composer update drupal/core webflo/drupal-core-require-dev symfony/* --with-dependencies";
      $result += parent::executeInAlias($input, $output, $alias);
      //then the modules
      $this->order = "composer update";
      $result += parent::executeInAlias($input, $output, $alias);
      //update db:
      $this->order = "vendor/bin/drush updatedb";
      $result += parent::executeInAlias($input, $output, $alias);
      //rebuild cache
      $this->order = "vendor/bin/drupal cr";
      $result += parent::executeInAlias($input, $output, $alias);
      // Set off maintenance mode:
      $this->order = "vendor/bin/drush sset system.maintenance_mode 0";
      $result += parent::executeInAlias($input, $output, $alias);
    }
    if ($alias['manager'] == 'drush8' or $alias['manager'] == 'drush8-alias'){
      // Process for Drupals 7 and drush <=8;
      $with_drush = $alias['manager'] == 'drush8' ? "drush ":"";

      $drupal_version = $this->getDrupalVersion($output, $alias['path']);
      $drupal_major_version = explode(".", $drupal_version, 2)[0];
      $output->writeln(sprintf('<info>Drupal version: %s</info>', $drupal_major_version));
      
      // Set to maintenance mode:
      $set_maintenance_mode = ($drupal_major_version == 8 )? "sset system.maintenance_mode":"vset maintenance_mode" ;
      $this->order = $with_drush . $set_maintenance_mode . " 1";
      $result += parent::executeInAlias($input, $output, $alias);
      //first update all
      $this->order = $with_drush . "up";
      $result += parent::executeInAlias($input, $output, $alias);
      //update db:
      $this->order = $with_drush . "updatedb";
      $result += parent::executeInAlias($input, $output, $alias);
      //rebuild cache
      $cache_clear = ($drupal_major_version == 8 )? "cr" : "cc";
      $this->order = $with_drush . $cache_clear . " all";
      $result += parent::executeInAlias($input, $output, $alias);
      // Set off maintenance mode:
      $this->order = $with_drush . $set_maintenance_mode . " 0";
      $result += parent::executeInAlias($input, $output, $alias);
    }
    return $result;
  }

  protected function getDrupalVersion(OutputInterface $output, $path){
    $process = new Process(sprintf('cd %s && drush status', $path));
    $process->start();
    $version = '';

    foreach ($process as $type => $data) {
      if ($process::OUT === $type) {
        if (preg_match('|Drupal version +: +(.*)|', $data, $matches)) {
          $version = trim($matches[1]);
        }
      } else {
        echo "[ERR] ".$data."-----data\n";
      }
    }
    return $version;

  }
}

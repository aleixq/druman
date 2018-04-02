<?php
// src/Command/ListProjectsCommand.php
namespace Druman\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console\Helper\Table;
use Druman\ProjectsFiltersTrait;

class ListProjectsCommand extends Command
{
  use ProjectsFiltersTrait;

  private $aliases;

  public function __construct($aliases)
  {
    $this->aliases = $aliases;
    parent::__construct(null);
  }
  protected function configure()
  {
    // ...
    $this
      // the name of the command (the part after "bin/console")
      ->setName('projects:list')

      // the short description shown while running "php bin/console list"
      ->setDescription('Lists projects that can be managed.')

      // the full command description shown when running the command with
      // the "--help" option
      ->setHelp('This command allows you to list the projects that can be managed...')
      ->setDefinition(
        new InputDefinition([
          new InputOption('group', 'g', InputOption::VALUE_OPTIONAL, 'List only projects of specified group'),
          new InputOption('local', 'l', InputOption::VALUE_NONE, 'List only local projects'),
          new InputOption('remote', 'r', InputOption::VALUE_NONE, 'List only remote projects'),
          new InputOption('full', 'f', InputOption::VALUE_NONE, 'Show all fields from list: alias, path, management type and group.'),
      ]));
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $full = $input->getOption('full');
    $group = $input->getOption('group');
    $local_only = $input->getOption('local');
    $remote_only = $input->getOption('remote');

    $table = new Table($output);
    $table->setHeaders(($full)?['Alias','Path','Web Path', 'Groups','Manager']:[]);
    $aliases = $this->filterByOrigin($local_only, $remote_only, $this->aliases);
    $aliases = $this->filterByGroup($group, $aliases);
    foreach($aliases as $key=>$alias){
      $web_path = $alias['manager'] == 'drupal-composer' ? $alias['path']. '/web' :  $alias['path'];
      $table->addRow(($full)? [$alias['alias'], $alias['path'], $web_path, $alias['groups'], $alias['manager']]:[$alias['alias'], $web_path]);
    }
    $table->setStyle('compact');
    $table->render();

    // return value is important when using CI
    // to fail the build when the command fails
    // 0 = success, other values = fail
    return 0;      

  }
}

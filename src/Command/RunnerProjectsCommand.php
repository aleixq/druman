<?php
// src/Command/RunnerProjectsCommand.php
namespace Druman\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\ProgressBar;
use Druman\ProjectsFiltersTrait;


class RunnerProjectsCommand extends Command
{
  use ProjectsFiltersTrait;

  private $aliases;
  protected $failingProcess;
  protected $order;

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
      ->setName('projects:run')

      // the short description shown while running "php bin/console list"
      ->setDescription('Run a command on each project.')

      // the full command description shown when running the command with
      // the "--help" option
      ->setHelp('This command allows you to run a command on each projects that can be managed...')
      ->setDefinition(
        new InputDefinition([
          new InputOption('group', 'g', InputOption::VALUE_OPTIONAL, 'Run only on these projects which are members of specified group'),
	  new InputOption('alias', 'a', InputOption::VALUE_OPTIONAL, 'Run only on this specific alias'),
	  new InputOption('all', 'all', InputOption::VALUE_NONE, 'Run in all alias, excluding those using drush8-alias manager, if specified no filters will be used'),
	  new InputOption('local', 'l', InputOption::VALUE_NONE, 'List only local projects'),
	  new InputOption('remote', 'r', InputOption::VALUE_NONE, 'List only remote projects'),
      ]))
      ->addArgument('order', InputArgument::OPTIONAL, 'Command to run.');
  }

  protected function initialize(InputInterface $input, OutputInterface $output){
    $this->order = $input->getArgument('order');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $group = $input->getOption('group');
    $alias_opt = $input->getOption('alias');
    $in_all = $input->getOption('all');
    $local_only = $input->getOption('local');
    $remote_only = $input->getOption('remote');

    $result = 0;

    if (!$in_all && !$alias_opt && !$group && !$remote_only && !$local_only){
      $output->writeln(sprintf('<error>%s</error>', "Please, set --group, --alias, --local, --remote or --all to apply to some project(s)."));
      $help = new HelpCommand();
      $help->setCommand($this);
      return $help->run($input, $output);
    }

    $aliases = $this->filterByOrigin($local_only, $remote_only, $this->aliases);
    $aliases = $this->filterByGroup($group, $aliases);
    $aliases = $this->filterByAlias($alias_opt, $aliases);

    if ($in_all){
      $aliases = $this->aliases;
    }

    if ($alias_opt && sizeof($aliases) == 0){
      $output->writeln(sprintf('<error>Could not found alias "%s"</error>', $alias_opt));
      return -1;
    }

    $progressBar = new ProgressBar($output, sizeof($aliases));
    $progressBar->start();
    $output->writeln("");
    $i = 0;

    foreach($aliases as $key=>$alias){
      if ($in_all){
        // do not run drush8-alias commands when in all mode, to prevent commands not aplicable.
        if ($alias['manager'] == "drush8-alias"){
          $output->writeln(sprintf('<info>Omitting the drush8-alias managed project "%s" to prevent mixing drush and bash commands.</info>', $alias['alias']));
          continue;
        }
      }

      if ($this->executeInAlias($input, $output, $alias) == -1){
	// To preserve $result 0 only touch if bad executeInAlias result
        $result = -1;
      }
      $progressBar->advance();
      $output->writeln("");

      $i++;
    }
    $progressBar->finish();
    $output->writeln("");


    // return value is important when using CI
    // to fail the build when the command fails
    // 0 = success, other values = fail
    return $result;
  }
 /**
  * Runs the order in alias.
  *
  * @param [] $alias
  *   An array with path, alias, manager, group keys
  * @return int
  *   Returns -1 if command fails, nothing elsewhere.
  */
  protected function executeInAlias(InputInterface $input, OutputInterface $output, $alias){
    // get the username to prevent calling fix-perms always...
    $alias_user = posix_getpwuid(fileowner($alias['path']))['name'];

    $drush_alias = $alias['manager'] == 'drush8-alias' ? "@" . $alias['alias']:FALSE;
    if ($drush_alias){
      $proc = new Process(sprintf("drush %s %s", $drush_alias, $this->order));
    }
    else {
      $proc = new Process(sprintf("cd '%s' && su %s -c \"%s\" -s /bin/bash", $alias['path'], $alias_user, $this->order));
    }
    $output->writeln(sprintf('<info>processing command "%s" in alias "%s"</info>', $this->order, $alias['alias']));
    $output->writeln(sprintf("cd '%s' && su %s -c \"%s\" -s /bin/bash", $alias['path'], $alias_user, $this->order));
    try{
      $proc->setTty(true);
      $proc->mustRun(function ($type, $buffer) {
        echo $buffer;
      });
      $output->writeln(sprintf('<info>command %s succesfully ran</info>', $this->order));
    } catch (ProcessFailedException $e) {
      echo $e->getMessage();
      $this->failingProcess = $proc;
      $output->writeln(sprintf('<error>%s</error>', $this->failingProcess->getErrorOutput()));
      return -1;
    }

  }
}

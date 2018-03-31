<?php
// src/Command/RunnerProjectsCommand.php
namespace Druman\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\ProgressBar;


class RunnerProjectsCommand extends Command
{
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
      ]))
      ->addArgument('order', InputArgument::OPTIONAL, 'Command to run.');
  }

  protected function initialize(InputInterface $input, OutputInterface $output){
    $this->order = $input->getArgument('order');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $group = $input->getOption('group');
    $result = 0;

    $progressBar = new ProgressBar($output, sizeof($this->aliases));
    $progressBar->start();
    $i = 0;

    foreach($this->aliases as $key=>$alias){
      if ($group){
	$groups = explode(',', $alias['groups']);
	if (!in_array($group, $groups)){
	  continue;
	}	
      }

      if ($this->executeInAlias($input, $output, $alias) == -1){
	// To preserve $result 0 only touch if bad executeInAlias result
        $result = -1;
      }
      $progressBar->advance();
      $i++;
    }
    $progressBar->finish();

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
    
    $proc = new Process(sprintf("cd '%s' && su %s -c \"%s\" -s /bin/bash", $alias['path'], $alias_user, $this->order));
    $output->writeln(sprintf('<info>processing command "%s" in alias "%s"</info>', $this->order, $alias['alias']));
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

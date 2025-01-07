<?php
namespace Concrete\Core\Command\Task\Output;

use Symfony\Component\Console\Output\OutputInterface as SymfonyConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
defined('C5_EXECUTE') or die("Access Denied.");

class ConsoleOutput implements OutputInterface
{

    /**
     * @var SymfonyConsoleOutputInterface
     */
    protected $symfonyOutput;

    public function __construct(?SymfonyConsoleOutputInterface $symfonyOutput = null)
    {
        if ($symfonyOutput === null) {
            $symfonyOutput = new SymfonyConsoleOutput();
        }
        $this->symfonyOutput = $symfonyOutput;
    }

    public function write($message)
    {
        $this->symfonyOutput->writeln($message);
    }

}

<?php

namespace Smalot\Minotor\Command;

use Smalot\Minotor\Server\SocketServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerStatus
 * @package Smalot\Minotor\Command
 */
class ServerStatus extends Command
{
    const PID_FILE = 'minotor-server.pid';

    /**
     *
     */
    protected function configure()
    {
        $this
          ->setName('server:status')
          ->setDescription('Check status server.')
          ->addOption('pid', 'p', InputOption::VALUE_OPTIONAL, 'PID file path');
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getPidFile(InputInterface $input)
    {
        if ($pidFile = $input->hasOption('pid')) {
            return $input->getOption('pid');
        } else {
            return APP_ROOT.'/'.self::PID_FILE;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $this->getPidFile($input);

        if (!file_exists($pidFile)) {
            $output->writeln('<comment>Server not running</comment>');
        } else {
            $output->writeln('<info>Server running</info>');
        }
    }
}

<?php

namespace Smalot\Minotor\Command;

use Smalot\Minotor\Server\SocketServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerStart
 * @package Smalot\Minotor\Command
 */
class ServerStart extends Command
{
    const PID_FILE = 'minotor-server.pid';

    /**
     *
     */
    protected function configure()
    {
        $this
          ->setName('server:start')
          ->setDescription('Run server.')
          ->setHelp('Start server...')
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
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $this->getPidFile($input);

        openlog('monitoring', LOG_PID | LOG_ODELAY, LOG_USER);
//        openlog('monitoring', LOG_PID | LOG_PERROR | LOG_ODELAY, LOG_USER);

        if (!file_exists($pidFile)) {
            file_put_contents($pidFile, posix_getpid());

//            $output->writeln('<info>Listening on 5000</info>');

            declare(ticks = 1);

            $daemon = new SocketServer('0.0.0.0:5000');
            $daemon->setTimeout(5);
            $daemon->run();
        } else {
//            $output->writeln('<info>Already running</info>');
        }
    }
}

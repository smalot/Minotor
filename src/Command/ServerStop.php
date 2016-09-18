<?php

namespace Smalot\Minotor\Command;

use Smalot\Minotor\Server\SocketServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerStop
 * @package Smalot\Minotor\Command
 */
class ServerStop extends Command
{
    const PID_FILE = 'minotor-server.pid';

    /**
     *
     */
    protected function configure()
    {
        $this
          ->setName('server:stop')
          ->setDescription('Stop server.')
          ->setHelp('Stop server...')
          ->addOption('pid', 'p', InputOption::VALUE_OPTIONAL, 'PID file path')
          ->addOption('force', 'f', null, 'Force');
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

        if (file_exists($pidFile)) {
            $succeed = true;
            $pid = file_get_contents($pidFile);
            $status = posix_getsid($pid);

            if ($status) {
                $succeed = posix_kill($pid, SIGTERM);
                pcntl_waitpid($pid, $status);

                if (!$succeed && $input->hasOption('force')) {
                    $succeed = posix_kill($pid, SIGKILL);
                }
            }

            if ($succeed) {
                unlink($pidFile);
//                $output->writeln('<info>Server killed</info>');
            } else {
//                $output->writeln('<error>Unable to kill server</error>');
            }

        } else {
//            $output->writeln('<comment>Not running</comment>');
        }
    }
}

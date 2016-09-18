<?php

namespace Smalot\Minotor\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ClientSend
 * @package Smalot\Minotor\Command
 */
class ClientSend extends Command
{
    protected function configure()
    {
        $this
          ->setName('client:send')
          ->setDescription('Send locally stored metrics to the server.')
          ->setHelp('Send all metrics...');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = new \Socket\Raw\Factory();
        $socket = $factory->createClient('127.0.0.1:5000');

        $output->writeln('Client connected to '.$socket->getPeerName());
        $response = $socket->read(8192);
        $output->writeln(trim($response, "\n\r"));

        $logs = array(
          'apache.connection.open' => 10,
          'apache.connection.close' => 100,
          'apache.connection.wait' => 80,
        );

        foreach ($logs as $key => $value) {
            $socket->send('put '.microtime(true).' '.$key.' '.$value, MSG_EOF);
            $response = $socket->read(8192);
            $output->writeln(trim($response, "\n\r"));
        }

        $socket->send('exit', MSG_EOF);
        $response = $socket->read(8192);
        $output->writeln(trim($response, "\n\r"));

        $socket->close();
    }
}

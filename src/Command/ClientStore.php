<?php

namespace Smalot\Minotor\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ClientStore
 * @package Smalot\Minotor\Command
 */
class ClientStore extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('client:store')
          // the short description shown while running "php bin/console list"
          ->setDescription('Store locally metric.')

            // the full command description shown when running the command with
            // the "--help" option
//          ->setHelp("Send all metrics...")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln(
          [
            'User Creator',
            '============',
            '',
          ]
        );

        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->write('create a user.');
    }
}

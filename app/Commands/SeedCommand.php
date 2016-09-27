<?php
namespace App\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Test.')
            ->setHelp("This command allows you to create users...")
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);

        // retrieve the getArgument( value using getArgument()
        $output->writeln('Username: ' . $input->getArgument('username'));

        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->write('create a user.');
    }

    
}


<?php
namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Modules\Main\Models\testModel;
use App\Modules\Main\Helpers\MagentoApi;

class SeedCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Test.')
            ->setHelp("This command allows you to create users...")
            // ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        dd(env());
        $m = new MagentoApi;
        $r = $m->login();
        $r = $m->get_catalogProductAttributeRepositoryV1("size");
        // $r = $m->getToken();
        dd($r);
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        dd(PHP_EOL);
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

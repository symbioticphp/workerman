<?php


namespace Symbiotic\Workerman\Commands;


use Psr\Container\ContainerInterface;
use Symbiotic\Workerman\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Worker extends Command
{
    private ContainerInterface $app;

    public function __construct(App $container)
    {
        $this->app = $container;
        parent::__construct();
        /**
         *  start
         * start -d
         * status
         * status -d
         * connections
         * stop
         * stop -g
         * restart
         * reload
         * reload -g
         */
        $this
            ->addArgument('run_command', InputArgument::REQUIRED, 'Workerman command.')
            ->addOption('host', 's', InputOption::VALUE_OPTIONAL, 'Http server IP.')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Http server port')
            ->addOption('processes', 'c', InputOption::VALUE_OPTIONAL, 'Http server port', 1)
            ->addOption(
                'daemonize',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Demonize the worker?',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        match ($input->getArgument('run_command')) {
            'start' => $this->app->start($input, $output),
            default => $this->app->workermanCommand($output),
        };
        return 0;
    }
}
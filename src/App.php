<?php

namespace Symbiotic\Workerman;


use Symbiotic\Apps\Application;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\SymbioticException;
use Symbiotic\Routing\UrlGeneratorInterface;
use Symbiotic\Settings\SettingsInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class App extends Application
{

    public function workermanCommand(OutputInterface $output)
    {
        $output->writeln('Run Workerman command!');
        ob_start();
        Worker::runAll();
        $output->writeln(ob_get_clean());
    }

    public function start(InputInterface $input, OutputInterface $output)
    {
        $inputPort = $input->getOption('port');
        $inputHost = $input->getOption('host');
        $countWorkers = $input->hasOption('processes')
            ? (int)$input->hasOption('processes') : 1;

        if ($inputPort && $inputHost) {
            $host = $inputHost;
            $port = $inputPort;
            $alias = $host . ':' . $port;
        } else {
            $host = $this[SettingsInterface::class]->get('http_server_host');
            $port = $this[SettingsInterface::class]->get('http_server_port');
            $alias = $this[SettingsInterface::class]->get('http_server_alias');
        }

        if (empty($alias)) {
            if (intval($port) < 1) {
                throw new SymbioticException('Port [' . $port . '] is not valid!');
            }
            $alias = $host . ':' . $port;
        }

        $worker = $this->createWorker('http://' . $alias);
        $worker->count = $countWorkers;
        $worker->start();

        $core = $worker->getCurrentContainer();
        $output->writeln('See in address: ' . $core->get(UrlGeneratorInterface::class)->to('/'));
    }

    public function createWorker($socket_name = '', array $context_option = array())
    {
        $worker = new Worker($socket_name, $context_option);
        $worker->setContainer($this->get(CoreInterface::class));
        return $worker;
    }


}
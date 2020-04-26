<?php

namespace Command;

use Service\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;

class ConfigCommand extends Command
{
    protected static $defaultName = 'config';

    private $title = "Set config";
    /** @var Config $config */
    private  $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('configure bannerlord-cli')
            ->setHelp('This command allows you to set up the config');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->title);

        $config = $this->config->get();

        $srcConfirmed = false;
        while($srcConfirmed == false) {
            $path = $io->ask('source path:', $config['folders']['src']);
            if($io->confirm("confirm path: {$path}")) {
                $this->config->setSrc($path);
                $srcConfirmed = true;
            }
        }
        $outConfirmed = false;
        while($outConfirmed == false) {
            $path = $io->ask('Bannerlord path:', $config['folders']['out']);
            if($io->confirm("confirm path: {$path}")) {
                $this->config->setOut($path);
                $outConfirmed = true;
            }
        }

        if(!$this->config->save()) {
            $io->writeln('Could not save to file!');
            return 1;
        }
        $io->writeln('Wrote changes to file.');
        return 0;
    }
}
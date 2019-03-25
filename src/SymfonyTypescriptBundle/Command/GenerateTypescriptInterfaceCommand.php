<?php

namespace SymfonyTypescriptBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SymfonyTypescriptBundle\Generator;
use SymfonyTypescriptBundle\Parser;

class GenerateTypescriptInterfaceCommand extends ContainerAwareCommand
{
	private $parser;
	private $generator;

	public function __construct($name = null, Parser $parser, Generator $generator)
	{
		parent::__construct($name);

		$this->parser = $parser;
		$this->generator = $generator;
	}

	protected function configure()
    {
        $this
            ->setName('generate:typescript:interface')
            ->setDescription('Generate typescript interface for DataModel')
            ->addArgument('dataModel', InputArgument::REQUIRED, 'DataModel entity')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$dataModel = $input->getArgument('dataModel');

		$entity = $this->parser->parse($dataModel);
		$this->generator->generate($entity);

		$output->writeln('<fg=green>' . $entity->getTypescriptName() . ' was successfully generated' . '</>');
	}

}

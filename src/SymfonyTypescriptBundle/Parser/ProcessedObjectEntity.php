<?php

namespace SymfonyTypescriptBundle\Parser;

class ProcessedObjectEntity extends AbstractEntity {
	private $entity;

	public function __construct(string $name, AbstractEntity $entity)
	{
		parent::__construct($name);

		$this->entity = $entity;
	}

	public function getEntity(): AbstractEntity
	{
		return $this->entity;
	}
}

<?php

namespace SymfonyTypescriptBundle\Parser;

class ObjectProperty extends AbstractProperty {
	protected $objectInstance;

	public function __construct(string $name, bool $nullable, AbstractEntity $objectInstance)
	{
		parent::__construct($name, $nullable);

		$this->objectInstance = $objectInstance;
	}

	public function getObjectInstance(): AbstractEntity
	{
		return $this->objectInstance;
	}

}

<?php

namespace SymfonyTypescriptBundle\Parser;

class AbstractEntity {
	private $name;

	private $properties = [];

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getProperties(): array
	{
		return $this->properties;
	}

	public function setProperties(array $properties): self
	{
		$this->properties = $properties;

		return $this;
	}

	public function addProperty(AbstractProperty $property): self
	{
		$this->properties[] = $property;

		return $this;
	}

	public function getTypescriptName(): string
	{
		return $this->name . 'Interface';
	}

	public function getTypescriptFileName(): string
	{
		return lcfirst($this->getTypescriptName());
	}

	public function getTypescriptFileNameWithExtension(): string
	{
		return lcfirst($this->getTypescriptName()) . '.ts';
	}
}

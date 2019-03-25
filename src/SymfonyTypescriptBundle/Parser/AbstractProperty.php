<?php

namespace SymfonyTypescriptBundle\Parser;

class AbstractProperty {

	private $name;

	private $nullable;

	public function __construct(string $name, bool $nullable)
	{
		$this->name = $name;
		$this->nullable = $nullable;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isNullable(): bool
	{
		return $this->nullable;
	}
}

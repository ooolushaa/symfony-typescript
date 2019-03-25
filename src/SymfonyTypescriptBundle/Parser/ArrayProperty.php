<?php

namespace SymfonyTypescriptBundle\Parser;

class ArrayProperty extends AbstractProperty {
	public const DEFAULT_VALUE = 'any';

	protected $arrayOf;

	/**
	 * @param string $name
	 * @param bool $nullable
	 * @param string|AbstractEntity $arrayOf
	 */
	public function __construct(string $name, bool $nullable, $arrayOf)
	{
		parent::__construct($name, $nullable);

		$this->arrayOf = $arrayOf;
	}

	public function getArrayOf()
	{
		return $this->arrayOf;
	}

}

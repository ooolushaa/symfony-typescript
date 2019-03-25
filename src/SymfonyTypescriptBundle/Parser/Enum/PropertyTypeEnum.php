<?php

namespace SymfonyTypescriptBundle\Parser\Enum;

class PropertyTypeEnum {
	public const STRING = 'string';
	public const INT = 'int';
	public const INTEGER = 'integer';
	public const FLOAT = 'float';
	public const BOOL = 'bool';
	public const BOOLEAN = 'boolean';
	public const ARRAY = 'array';
	public const OBJECT = 'object';

	public static function getInternalTypes(): array
	{
		return [
			self::STRING,
			self::INTEGER,
			self::INT,
			self::FLOAT,
			self::BOOL,
			self::BOOLEAN,
			self::ARRAY
		];
	}
}

<?php

namespace SymfonyTypescriptBundle\Parser\Factory;

use SymfonyTypescriptBundle\Exception\CanNotProcessPropertyTypeException;
use SymfonyTypescriptBundle\Parser\AbstractEntity;
use SymfonyTypescriptBundle\Parser\AbstractProperty;
use SymfonyTypescriptBundle\Parser\ArrayProperty;
use SymfonyTypescriptBundle\Parser\BoolProperty;
use SymfonyTypescriptBundle\Parser\FloatProperty;
use SymfonyTypescriptBundle\Parser\IntProperty;
use SymfonyTypescriptBundle\Parser\Enum\PropertyTypeEnum;
use SymfonyTypescriptBundle\Parser\ObjectProperty;
use SymfonyTypescriptBundle\Parser\StringProperty;

class PropertyFactory {

	/**
	 * @param string $name
	 * @param string $type
	 * @param bool $nullable
	 * @param string|AbstractEntity $arrayOf
	 * @param AbstractEntity|null $objectInstance
	 * @return AbstractProperty
	 * @throws CanNotProcessPropertyTypeException
	 */
	public function create(string $name, string $type, bool $nullable, $arrayOf, ?AbstractEntity $objectInstance): AbstractProperty
	{
		switch ($type) {
			case PropertyTypeEnum::STRING:
				return new StringProperty($name, $nullable);
			case PropertyTypeEnum::INTEGER:
			case PropertyTypeEnum::INT:
				return new IntProperty($name, $nullable);
			case PropertyTypeEnum::FLOAT:
				return new FloatProperty($name, $nullable);
			case PropertyTypeEnum::BOOLEAN:
			case PropertyTypeEnum::BOOL:
				return new BoolProperty($name, $nullable);
			case PropertyTypeEnum::ARRAY:
				return new ArrayProperty($name, $nullable, $arrayOf);
			case PropertyTypeEnum::OBJECT:
				return new ObjectProperty($name, $nullable, $objectInstance);
			default:
				throw new CanNotProcessPropertyTypeException();
		}
	}

}

<?php

namespace SymfonyTypescriptBundle\Parser\Factory;

use SymfonyTypescriptBundle\Exception\CanNotProcessEntityTypeException;
use SymfonyTypescriptBundle\Parser\AbstractEntity;
use SymfonyTypescriptBundle\Parser\ObjectEntity;
use SymfonyTypescriptBundle\Parser\DataModelEntity;
use SymfonyTypescriptBundle\Parser\Enum\EntityTypeEnum;
use SymfonyTypescriptBundle\Parser\EnumEntity;

class EntityFactory {

	public function create(string $name, string $type): AbstractEntity
	{
		switch ($type) {
			case EntityTypeEnum::DATA_MODEL:
				return new DataModelEntity($name);
			case EntityTypeEnum::ENUM:
				return new EnumEntity($name);
			case EntityTypeEnum::OBJECT:
				return new ObjectEntity($name);
			default:
				throw new CanNotProcessEntityTypeException();
		}
	}

}

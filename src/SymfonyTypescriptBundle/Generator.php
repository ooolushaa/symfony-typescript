<?php

namespace SymfonyTypescriptBundle;

use SymfonyTypescriptBundle\Parser\AbstractEntity;
use SymfonyTypescriptBundle\Parser\AbstractProperty;
use SymfonyTypescriptBundle\Parser\ArrayProperty;
use SymfonyTypescriptBundle\Parser\BoolProperty;
use SymfonyTypescriptBundle\Parser\DataModelEntity;
use SymfonyTypescriptBundle\Parser\FloatProperty;
use SymfonyTypescriptBundle\Parser\IntProperty;
use SymfonyTypescriptBundle\Parser\ObjectEntity;
use SymfonyTypescriptBundle\Parser\ObjectProperty;
use SymfonyTypescriptBundle\Parser\ProcessedObjectEntity;
use SymfonyTypescriptBundle\Parser\StringProperty;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Generator {

	private const DATA_MODELS_FOLDER = '/src/SymfonyTypescriptBundle/App/interface/dataModel';
	private const OBJECT_FOLDER = '/src/SymfonyTypescriptBundle/App/interface';
	private const ENUM_FOLDER = '/src/SymfonyTypescriptBundle/App/interface/enum';

	private $projectDir;

	public function __construct(string $projectDir)
	{
		$this->projectDir = $projectDir;
	}

	public function generate(AbstractEntity $entity)
	{
		$lines = [];

		if ($this->findFile($entity->getTypescriptFileName())) {
			throw new \Exception();
		}

		if ($entity instanceof DataModelEntity) {
			foreach ($entity->getProperties() as $entityProperty) {
				if ($entityProperty instanceof ObjectProperty) {
					$instance = $entityProperty->getObjectInstance();

					if (!$instance instanceof ProcessedObjectEntity) {
						$this->generate($instance);
					}

					$lines[] = $this->dataModelImport($instance);
				} elseif ($entityProperty instanceof ArrayProperty) {
					$arrayOf = $entityProperty->getArrayOf();

					if ($arrayOf instanceof AbstractEntity) {
						$this->generate($arrayOf);
					}

					$lines[] = $this->dataModelImport($arrayOf);
				}
			}

			if (count($lines) > 0) {
				$lines[] = '';
			}

			$lines[] = 'export default interface ' . $entity->getTypescriptName() . ' {';

			foreach ($entity->getProperties() as $entityProperty) {
				$lines[] = $this->getPropertyInterfaceLine($entityProperty);
			}

			$lines[] = '}';

			$this->saveFile(
				self::DATA_MODELS_FOLDER,
				$entity->getTypescriptFileNameWithExtension(),
				implode(PHP_EOL, $lines)
			);
			return;
		}

		if ($entity instanceof ObjectEntity) {
			foreach ($entity->getProperties() as $entityProperty) {
				if ($entityProperty instanceof ObjectProperty) {
					$instance = $entityProperty->getObjectInstance();

					if (!$instance instanceof ProcessedObjectEntity) {
						$this->generate($instance);
					}

					$lines[] = $this->objectImport($instance);
				} elseif ($entityProperty instanceof ArrayProperty) {
					$arrayOf = $entityProperty->getArrayOf();

					if ($arrayOf instanceof AbstractEntity) {
						$this->generate($arrayOf);
					}

					$lines[] = $this->objectImport($arrayOf);
				}
			}

			if (count($lines) > 0) {
				$lines[] = '';
			}

			$lines[] = 'export default interface ' . $entity->getTypescriptName() . ' {';

			foreach ($entity->getProperties() as $entityProperty) {
				$lines[] = $this->getPropertyInterfaceLine($entityProperty);
			}

			$lines[] = '}';

			$this->saveFile(
				self::OBJECT_FOLDER,
				$entity->getTypescriptFileNameWithExtension(),
				implode(PHP_EOL, $lines)
			);
			return;
		}
	}

	protected function findFile(string $file)
	{
		$finder = new Finder();

		$dataModelDir = realpath($this->projectDir . '/src/SymfonyTypescriptBundle/App/interface');

		$finder->files()->in([$dataModelDir])->name($file . '.ts');

		$iterator = $finder->getIterator();
		$iterator->rewind();
		$firstFile = $iterator->current();

		return $firstFile;
	}

	protected function saveFile(string $folder, string $name, string $text): void
	{
		$fileSystem = new Filesystem();

		$fileSystem->dumpFile(realpath($this->projectDir . $folder) . DIRECTORY_SEPARATOR  . $name, $text);
	}

	protected function getNullableString(AbstractProperty $property)
	{
		return $property->isNullable() ? '?' : '';
	}

	protected function getPropertyInterfaceLine(AbstractProperty $property): string
	{
		if ($property instanceof ObjectProperty) {
			return '    ' . $property->getName() . $this->getNullableString($property) . ': ' . $property->getObjectInstance()->getTypescriptName() . ';';
		}
		if ($property instanceof StringProperty) {
			return '    ' . $property->getName() . $this->getNullableString($property) . ': string;';
		}
		if ($property instanceof IntProperty) {
			return '    ' . $property->getName() . $this->getNullableString($property) . ': number;';
		}
		if ($property instanceof FloatProperty) {
			return '    ' . $property->getName() . $this->getNullableString($property) . ': number;';
		}
		if ($property instanceof BoolProperty) {
			return '    ' . $property->getName() . $this->getNullableString($property) . ': boolean;';
		}
		if ($property instanceof ArrayProperty) {
			if ($property->getArrayOf() instanceof AbstractEntity) {
				return '    ' . $property->getName() . $this->getNullableString($property) . ': ' . $property->getArrayOf()->getTypescriptName() . '[];';
			}

			return '    ' . $property->getName() . $this->getNullableString($property) . ': ' . $property->getArrayOf() . '[];';
		}
	}

	protected function dataModelImport(AbstractEntity $instance): string
	{
		if ($instance instanceof DataModelEntity) {
			return 'import ' . $instance->getTypescriptName() . ' from "./' . $instance->getTypescriptFileName() . '";';
		} elseif ($instance instanceof ObjectEntity) {
			return 'import ' . $instance->getTypescriptName() . ' from "../' . $instance->getTypescriptFileName() . '";';
		}  elseif ($instance instanceof ProcessedObjectEntity) {
			if ($instance->getEntity() instanceof DataModelEntity) {
				return 'import ' . $instance->getEntity()->getTypescriptName() . ' from "./' . $instance->getEntity()->getTypescriptFileName() . '";';
			} elseif ($instance instanceof ObjectEntity) {
				return 'import ' . $instance->getEntity()->getTypescriptName() . ' from "../' . $instance->getEntity()->getTypescriptFileName() . '";';
			}
		}
	}

	protected function objectImport(AbstractEntity $instance): string
	{
		if ($instance instanceof DataModelEntity) {
			return 'import ' . $instance->getTypescriptName() . ' from "./dataModel/' . $instance->getTypescriptFileName() . '";';
		} elseif ($instance instanceof ObjectEntity) {
			return 'import ' . $instance->getTypescriptName() . ' from "./' . $instance->getTypescriptFileName() . '";';
		}  elseif ($instance instanceof ProcessedObjectEntity) {
			if ($instance->getEntity() instanceof DataModelEntity) {
				return 'import ' . $instance->getEntity()->getTypescriptName() . ' from "./dataModel/' . $instance->getEntity()->getTypescriptFileName() . '";';
			} elseif ($instance->getEntity() instanceof ObjectEntity) {
				return 'import ' . $instance->getEntity()->getTypescriptName() . ' from "./' . $instance->getEntity()->getTypescriptFileName() . '";';
			}
		}
	}
}
<?php

namespace SymfonyTypescriptBundle;

use SymfonyTypescriptBundle\Exception\CanNotFindClassFileException;
use SymfonyTypescriptBundle\Parser\AbstractEntity;
use SymfonyTypescriptBundle\Parser\ArrayProperty;
use SymfonyTypescriptBundle\Parser\Enum\EntityTypeEnum;
use SymfonyTypescriptBundle\Parser\Enum\PropertyTypeEnum;
use SymfonyTypescriptBundle\Parser\Factory\EntityFactory;
use SymfonyTypescriptBundle\Parser\Factory\PropertyFactory;
use SymfonyTypescriptBundle\Parser\ProcessedObjectEntity;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

class Parser {

	private const DATA_MODEL_INTERFACE = "DataModelInterface";
	private const ABSTRACT_ENUM = "AbstractEnum";

	private $projectDir;

	private $entityFactory;
	private $propertyFactory;

	public function __construct(string $projectDir, EntityFactory $entityFactory, PropertyFactory $propertyFactory)
	{
		$this->projectDir = $projectDir;
		$this->entityFactory = $entityFactory;
		$this->propertyFactory = $propertyFactory;
	}

	public function parse(string $className, array $flatten = []): AbstractEntity
	{
		if (array_key_exists($className, $flatten)) {
			return new ProcessedObjectEntity($className, $flatten[$className]);
		}

		$firstFile = $this->findFile($className);

		$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);

		$ast = $parser->parse($firstFile->getContents());

		$nodeFinder = new NodeFinder();

		/** @var Class_ $class */
		$class = $nodeFinder->findFirstInstanceOf($ast, Class_::class);

		$entityType = $this->getEntityType($class);
		$entity = $this->entityFactory->create($className, $entityType);

		$flatten[$entity->getName()] = $entity;

		foreach ($class->stmts as $stmt) {
			if ($stmt instanceof Property) {

				$name = $this->getPropertyName($stmt->props);
				$type = $stmt->getDocComment() ? $this->getPropertyType($stmt->getDocComment()->getText()) : null;
				$nullable = $stmt->getDocComment() ? $this->propertyHasNullable($stmt->getDocComment()->getText()) : null;
				$arrayOf = null;
				$objectInstanceOf = null;

				if ($type) {
					if ($arrayOf = $this->parseArrayType($type)) {
						$type = PropertyTypeEnum::ARRAY;

						if (!$this->isInternalType($arrayOf)) {
							$foundFile = $this->findFile($arrayOf);

							if (!$foundFile) {
								throw new CanNotFindClassFileException();
							}

							$arrayOf = $this->parse($arrayOf, $flatten);
						}
					}
				}

				if (!is_string($type) && !is_bool($nullable)) {
					$getter = $this->getPropertyGetterByName($name, $class->stmts);

					if ($getter->returnType instanceof Identifier) {
						$type = $getter->returnType->name;
						$nullable = false;
					} else if ($getter->returnType instanceof NullableType) {
						$nullable = true;
						if ($getter->returnType->type instanceof Identifier) {
							$type = $getter->returnType->type->name;
						}
					}
				}

				if (!$this->isInternalType($type)) {
					$foundFile = $this->findFile($type);

					if (!$foundFile) {
						throw new CanNotFindClassFileException();
					}

					$objectInstanceOf = $this->parse($type, $flatten);
					$type = PropertyTypeEnum::OBJECT;
				}

				$entity->addProperty(
					$this->propertyFactory->create(
						$name,
						$type,
						$nullable,
						$arrayOf ?: ArrayProperty::DEFAULT_VALUE,
						$objectInstanceOf
					)
				);
			}
		}

		return $entity;
	}

	protected function isInternalType(string $type): bool
	{
		return in_array($type, PropertyTypeEnum::getInternalTypes());
	}


	protected function getEntityType(Class_ $class_): string
	{
		foreach ($class_->implements as $classImplement) {
			if ($classImplement instanceof Name) {
				foreach ($classImplement->parts as $part) {
					if ($part === self::DATA_MODEL_INTERFACE) {
						return EntityTypeEnum::DATA_MODEL;
					}
				}
			}
		}

		if ($class_->extends instanceof Name) {
			foreach ($class_->extends->parts as $part) {
				if ($part === self::ABSTRACT_ENUM) {
					return EntityTypeEnum::ENUM;
				}
			}
		}

		return EntityTypeEnum::OBJECT;
	}

	protected function getPropertyName(array $propertyProperties)
	{
		/** @var PropertyProperty  $propertyProperty */
		foreach ($propertyProperties as $propertyProperty) {
			if ($propertyProperty->name instanceof VarLikeIdentifier) {
				return $propertyProperty->name->name;
			}
		}

		return null;
	}

	protected function getPropertyType(string $docBlockText): string
	{
		$startsAt = strpos($docBlockText, "@var ") + strlen("@var ");
		$endsAt = strpos($docBlockText, "\n", $startsAt);
		$result = substr($docBlockText, $startsAt, $endsAt - $startsAt);

		return $this->cleanTypeString($result);
	}

	protected function cleanTypeString(string $type): string
	{
		if (strpos($type, "|null") !== false) {
			return str_replace("|null", "", $type);
		}

		return $type;
	}

	protected function propertyHasNullable(string $docBlockText): bool
	{
		return strpos($docBlockText, "|null") !== false || strpos($docBlockText, "null|") !== false;
	}

	protected function parseArrayType(string $type): ?string
	{
		if (strpos($type, "[]") !== false) {
			return str_replace("[]", "", $type);
		}

		return null;
	}

	protected function getPropertyGetterByName(string $propertyName, array $stmts): ?ClassMethod
	{
		foreach ($stmts as $stmt) {
			if ($stmt instanceof ClassMethod) {
				foreach ($stmt->stmts as $stmtStmts) {
					if ($stmtStmts instanceof Return_) {
						if ($stmtStmts->expr instanceof PropertyFetch) {
							if ($stmtStmts->expr->var instanceof Variable) {
								if ($stmtStmts->expr->var->name === "this") {
									if ($stmtStmts->expr->name instanceof Identifier) {
										if ($stmtStmts->expr->name->name === $propertyName) {
											return $stmt;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return null;
	}

	protected function findFile(string $file)
	{
		$finder = new Finder();

		$dataModelDir = $this->projectDir . '/src/SymfonyTypeScriptBundle';

		$finder->files()->in([$dataModelDir])->name($file . '.php');

		$iterator = $finder->getIterator();
		$iterator->rewind();
		$firstFile = $iterator->current();

		return $firstFile;
	}
}

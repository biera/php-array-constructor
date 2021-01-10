<?php declare(strict_types=1);

namespace Biera;

use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\ParameterReflection;
use function iter\any;

/**
 * @internal
 */
class ParameterMetadata
{
    const FQCN = '/^(\\\?([a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]*)(\\\[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]*)*)\[\]$/';

    const PRIMITIVES = [
        'int', 'string', 'float', 'bool', 'array', 'iterable', 'callable', 'null'
    ];

    private \ReflectionParameter $parameterReflection;
    private ?array $types;

    public function __construct(ParameterReflection $parameterReflection, ParamTag $paramTag = null)
    {
        $name = $parameterReflection->getName();
        $getTypeMetadata = function (string $type) {
            $parsed = $this->extractType($type);

            // tuple: [type (string), isPrimitive (bool), isList (bool)]
            return [$parsed, in_array($parsed, self::PRIMITIVES), $type != $parsed];
        };

        $types = array_map(
            $getTypeMetadata,
            $parameterReflection->hasType()
                ? $this->extractTypes(
                $parameterReflection->getType()
            )
                : []
        );

        $dockBlockTypes = array_map(
            $getTypeMetadata,
            !is_null($paramTag)
                ? $paramTag->getTypes()
                : []
        );

        if ($this->isType('array', false, $types)) {
            if (!$this->isType('array', false, $dockBlockTypes) && $this->isList($dockBlockTypes)) {
                $types = array_filter(
                    $types, fn($typeMetadata) => $typeMetadata[0] != 'array'
                );
            }
        }

        $this->types = array_merge($dockBlockTypes, $types);
        $this->parameterReflection = $parameterReflection;
    }

    public function isPrimitive(array $types = null): bool
    {
        return any(
            fn($typeMetadata) => $typeMetadata[1], !is_null($types) ? $types : $this->types
        );
    }

    public function isComplex(array $types = null): bool
    {
        return any(
            fn($typeMetadata) => !$typeMetadata[1], !is_null($types) ? $types : $this->types
        );
    }

    public function isList(array $types = null): bool
    {
        return any(
            fn($typeMetadata) => $typeMetadata[2], !is_null($types) ? $types : $this->types
        );
    }

    public function isType(string $type, bool $isList, array $types = null): bool
    {
        return any(
            fn($typeMetadata) => $type == $typeMetadata[0] && $isList == $typeMetadata[2], !is_null($types) ? $types : $this->types
        );
    }

    public function isNullable(): bool
    {
        return $this->parameterReflection->allowsNull();
    }

    public function getName(): string
    {
        return $this->parameterReflection->getName();
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param mixed $fallback
     * @return mixed
     */
    public function getDefault($fallback)
    {
        try {
            $default = $this->parameterReflection->getDefaultValue();
        } catch (\ReflectionException $e) {
            $default =  $fallback;
        }

        return $default;
    }

    private function extractTypes(\ReflectionType $metadata): array
    {
        return array_map(
            fn(\ReflectionNamedType $type) => (string) $type,
            $metadata instanceof \ReflectionUnionType ? $metadata->getTypes() : [$metadata]
        );
    }

    private function extractType(string $possibleListType): ?string
    {
        preg_match(self::FQCN, $possibleListType, $matches);

        return $matches[1] ?? $possibleListType;
    }
}

<?php declare(strict_types=1);

namespace Biera;

use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\ParameterReflection;

/**
 * @internal
 */
class ParameterMetadata
{
    private $parameterReflection;
    /** @var bool|null */
    private $primitive;
    /** @var bool|null */
    private $nonPrimitiveList;
    /** @var string|null */
    private $type;

    const FQCN = '/^(\\\?([a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]*)(\\\[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]*)*)\[\]$/';

    public function __construct(ParameterReflection $parameterReflection, ParamTag $paramTag = null)
    {
        $this->parameterReflection = $parameterReflection;

        if ($parameterReflection->hasType()) {
            $type = $parameterReflection->getType();
            $this->type = (string) $type;

            // check if the type is list of objects ("ClassName[]")
            // by inspecting docblock @param tag (if exists)
            if ('array' == (string) $type && !is_null($paramTag)) {
                $types = array_filter(
                    array_map(
                        function (string $type) {
                            return $this->parseCompoundType($type);
                        },
                        $paramTag->getTypes()
                    )
                );

                switch (count($types)) {
                    case 1:
                        $this->type = $types[0];
                        $this->primitive = false;
                        $this->nonPrimitiveList = true;
                        break;

                    case 0:
                        $this->primitive = true;
                        $this->nonPrimitiveList = false;
                        break;

                    default:
                        throw new \LogicException();
                }

            } elseif ($type->isBuiltin()) {
                $this->primitive = true;
                $this->nonPrimitiveList = false;
            } else {
                $this->primitive = false;
                $this->nonPrimitiveList = false;
            }
        } else if (!is_null($paramTag)) {
            $types = array_filter(
                $paramTag->getTypes(),
                function (string $type) {
                    return !in_array($type, ['int', 'float', 'bool', 'string', 'null', 'array']);
                }
            );

            $typesCount = \count($types);

            if ($typesCount > 1) {
                throw new \LogicException(
                    sprintf('More than one non-primitive type provided: %s.', \join(', ', $types))
                );
            }

            if ($typesCount == 1) {
                $this->primitive = false;
                $type = $this->parseCompoundType($types[0]);
                $this->nonPrimitiveList = !is_null($type);
                $this->type = $this->nonPrimitiveList ? $type : $types[0];
            }
        }
    }

    public function isPrimitive(): bool
    {
        return is_null($this->primitive) ? true : $this->primitive;
    }

    public function isNonPrimitiveList(): bool
    {
        return is_null($this->nonPrimitiveList) ? false : $this->nonPrimitiveList;
    }

    public function isNullable(): bool
    {
        return $this->parameterReflection->allowsNull();
    }

    public function getName(): string
    {
        return $this->parameterReflection->getName();
    }

    public function getType(): ?string
    {
        return $this->type;
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

    /**
     * Parse list types of shape "ClassName[]" and extracts
     * the class part ("ClassName") or null in case of pattern mismatch
     */
    private function parseCompoundType(string $type): ?string
    {
        preg_match(self::FQCN, $type, $matches);

        return $matches[1] ?? null;
    }
}

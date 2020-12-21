<?php declare(strict_types=1);

namespace Biera;

use Laminas\Code\Reflection\ClassReflection;

trait ArrayConstructor
{
    /** @var ?array */
    private static $constructorParametersMetadata;

    public static function createFromArray(array $params)
    {
        return new self(...static::getConstructorParameters($params));
    }

    public static function createFromPrimitives(array $params)
    {
        $methodName = __FUNCTION__;

        $nonPrimitiveParameters = array_filter(
            self::getConstructorParametersMetadata(),
            function (ParameterMetadata $parameterMetadata) use ($params) {
                return !$parameterMetadata->isPrimitive();
            }
        );

        $nonPrimitiveParameterToValueMap = array_map(
            function (ParameterMetadata $parameterMetadata) use ($methodName, $params) {
                $parameterName = $parameterMetadata->getName();
                $parameterValue = $params[$parameterName] ?? null;

                if ($parameterMetadata->isNullable() && is_null($parameterValue)) {
                    return null;
                }

                try {
                    $class = new ClassReflection($parameterMetadata->getType());

                    if (!in_array(ArrayConstructor::class, $class->getTraitNames())) {
                        throw new \LogicException(
                            sprintf(
                                'Class %s must use %s trait',
                                $class->getName(),
                                ArrayConstructor::class
                            )
                        );
                    }
                } catch (\ReflectionException $e) {
                    throw new \LogicException(
                        "Class {$parameterMetadata->getType()} does not exists", 0, $e
                    );
                }

                $paramPrimitiveValue = $params[$parameterMetadata->getName()];
                $constructor = [$class->getName(), $methodName];

                if ($parameterMetadata->isNonPrimitiveList()) {
                    if (!is_array($paramPrimitiveValue)) {
                        throw new \LogicException();
                    }

                    $value = array_map($constructor, $paramPrimitiveValue);
                } else {
                    $value = call_user_func($constructor, $paramPrimitiveValue);
                }

                return $value;
            },
            $nonPrimitiveParameters
        );

        return static::createFromArray(
            array_merge($params, $nonPrimitiveParameterToValueMap)
        );
    }

    private static function getConstructorParameters(array $params): array
    {
        $defaults = array_map(
            function (ParameterMetadata $parameterMetadata) {
                return $parameterMetadata->getDefault(null);
            },
            self::getConstructorParametersMetadata()
        );

        return array_values(
            array_merge($defaults, $params)
        );
    }

    private static function getConstructorParametersMetadata(): array
    {
        if (is_null(self::$constructorParametersMetadata)) {
            self::$constructorParametersMetadata = getConstructorParametersMetadata(__CLASS__);

        }

        return self::$constructorParametersMetadata;
    }
}



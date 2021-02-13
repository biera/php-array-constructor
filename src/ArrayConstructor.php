<?php declare(strict_types=1);

namespace Biera;

use Laminas\Code\Reflection\ClassReflection;

trait ArrayConstructor
{
    /** @var ?array */
    private static $constructorParametersMetadata;

    public static function createFromArray(array $params)
    {
        return self::doCreateFromArray($params);
    }

    protected static function doCreateFromArray(array $params)
    {
        return new self(...static::getConstructorParameters($params));
    }

    public static function createFromPrimitives(array $params, array $metadata = [])
    {
        return self::doCreateFromPrimitives($params, $metadata);
    }

    protected static function doCreateFromPrimitives(array $params, array $metadata)
    {
        $methodName = 'createFromPrimitives';

        $complexOrPrimitiveParameters = \array_filter(
            self::getConstructorParametersMetadata(),
            function (ParameterMetadata $parameterMetadata) use ($params) {
                return $parameterMetadata->isComplex();
            }
        );

        $nonPrimitiveParameterToValueMap = \array_map(
            function (ParameterMetadata $parameterMetadata) use ($methodName, $params, $metadata) {
                $instantiationErrors = [];
                $parameterName = $parameterMetadata->getName();
                $parameterValue = $params[$parameterName] ?? null;

                if ($parameterMetadata->isNullable() && \is_null($parameterValue)) {
                    return null;
                }

                foreach ($parameterMetadata->getTypes() as [$type, $isPrimitive, $isList]) {
                    try {
                        if ($isPrimitive) {
                            if (\call_user_func("is_$type", $parameterValue) && (!$isList || \is_iterable($type))) {
                                return $parameterValue;
                            }
                        } else {
                            $classReflection = new ClassReflection($type);

                            // the value is an instance of declared type
                            if (\is_object($parameterValue) && $classReflection->isInstance($parameterValue)) {
                                return $parameterValue;
                            }

                            $constructor = [$classReflection->getName(), $methodName];

                            if ($isList) {
                                if (\is_iterable($parameterValue)) {
                                    return \array_map(
                                        function ($parameterValue) use ($constructor, $classReflection, $metadata) {

                                            if (\is_object($parameterValue) && $classReflection->isInstance($parameterValue)) {
                                                return $parameterValue;
                                            }

                                            return \call_user_func($constructor, $parameterValue, $metadata);
                                        },
                                        $parameterValue
                                    );
                                }
                            } else {
                                return \call_user_func($constructor, $parameterValue, $metadata);
                            }
                        }
                    } catch (InstantiationException $instantiationError) {
                        throw new InstantiationException($parameterName, [], $instantiationError);

                    } catch (\Throwable $e) {
                        $instantiationErrors[] = $e;
                    }
                }

                throw new InstantiationException($parameterName, $instantiationErrors);
            },
            $complexOrPrimitiveParameters
        );

        return static::createFromArray(
            \array_merge($params, $nonPrimitiveParameterToValueMap)
        );
    }

    private static function getConstructorParameters(array $params): array
    {
        $defaults = \array_map(
            function (ParameterMetadata $parameterMetadata) {
                return $parameterMetadata->getDefault(null);
            },
            self::getConstructorParametersMetadata()
        );

        return \array_values(
            \array_merge($defaults, $params)
        );
    }

    private static function getConstructorParametersMetadata(): array
    {
        if (\is_null(self::$constructorParametersMetadata)) {
            self::$constructorParametersMetadata = getConstructorParametersMetadata(__CLASS__);
        }

        return self::$constructorParametersMetadata;
    }
}

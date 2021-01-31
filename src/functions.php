<?php declare(strict_types=1);

namespace Biera;

use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\ParameterReflection;

function getConstructorParametersMetadata(string $className): array
{
    $class = new ClassReflection($className);

    if ($class->hasMethod('__construct')) {
        $constructor = $class->getMethod('__construct');
        $dockBlock = $constructor->getDocBlock();
        $paramTags = \array_reduce(
            $dockBlock ? $dockBlock->getTags('param') : [],
            function ($paramTags, ParamTag $paramTag) {
                $paramName = $paramTag->getVariableName();

                if (!\is_null($paramName)) {
                    // name is always prefixed with "$"
                    $paramTags[\substr($paramName, 1)] = $paramTag;
                }

                return $paramTags;
            },
            []
        );

        // get constructor parameters metadata combining
        // type declarations with docblock @param tags
        return \array_reduce(
            $constructor->getParameters(),
            function ($parameterMetadataMap, ParameterReflection $parameterReflection) use ($paramTags) {
                $parameterName = $parameterReflection->getName();
                $parameterMetadataMap[$parameterName] = new ParameterMetadata(
                    $parameterReflection, $paramTags[$parameterName] ?? null
                );

                return $parameterMetadataMap;
            },
            []
        );
    }

    return [];
}

<?php declare(strict_types=1);

namespace ParameterMetadataTest;

use PHPUnit\Framework\TestCase;
use Biera\ParameterMetadata;
use function Biera\getConstructorParametersMetadata;

class ParameterMetadataTest extends TestCase
{
    /** @test */
    public function itGetsConstructorParametersMetadata(): void
    {
        $constructorParametersMetadata = getConstructorParametersMetadata(A::class);

        $aParameter = $constructorParametersMetadata['a'];
        $this->assertInstanceOf(ParameterMetadata::class, $aParameter);
        $this->assertEquals('a', $aParameter->getName());
        // 'a' parameter is missing its type declaration and there is
        // no fallback to @param doc-block tag even when it
        // exists (except for arrays)
        $this->assertEquals(null, $aParameter->getType());
        $this->assertEquals(null, $aParameter->getDefault(null));
        $this->assertTrue($aParameter->isPrimitive());
        $this->assertFalse($aParameter->isNonPrimitiveList());
        $this->assertTrue($aParameter->isNullable());

        $bParameter = $constructorParametersMetadata['b'];
        $this->assertInstanceOf(ParameterMetadata::class, $bParameter);
        $this->assertEquals('b', $bParameter->getName());
        $this->assertEquals('int', $bParameter->getType());
        $this->assertEquals(null, $bParameter->getDefault(null));
        $this->assertTrue($bParameter->isPrimitive());
        $this->assertFalse($bParameter->isNonPrimitiveList());
        $this->assertFalse($bParameter->isNullable());

        $dParameter = $constructorParametersMetadata['c'];
        $this->assertInstanceOf(ParameterMetadata::class, $dParameter);
        $this->assertEquals('c', $dParameter->getName());
        $this->assertEquals('array', $dParameter->getType());
        $this->assertEquals(null, $dParameter->getDefault(null));
        $this->assertTrue($dParameter->isPrimitive());
        $this->assertFalse($dParameter->isNonPrimitiveList());
        $this->assertFalse($dParameter->isNullable());

        $dParameter = $constructorParametersMetadata['d'];
        $this->assertInstanceOf(ParameterMetadata::class, $dParameter);
        $this->assertEquals('d', $dParameter->getName());
        $this->assertEquals(\ArrayObject::class, $dParameter->getType());
        $this->assertEquals(null, $dParameter->getDefault(null));
        $this->assertFalse($dParameter->isPrimitive());
        $this->assertFalse($dParameter->isNonPrimitiveList());
        $this->assertFalse($dParameter->isNullable());

        $eParameter = $constructorParametersMetadata['e'];
        $this->assertInstanceOf(ParameterMetadata::class, $eParameter);
        $this->assertEquals('e', $eParameter->getName());
        $this->assertEquals(\stdClass::class, $eParameter->getType());
        $this->assertEquals([], $eParameter->getDefault(null));
        $this->assertFalse($eParameter->isPrimitive());
        $this->assertTrue($eParameter->isNonPrimitiveList());
        $this->assertFalse($eParameter->isNullable());
    }

    /** @test */
    public function itGetsConstructorParametersMetadataInCorrectOrder(): void
    {
        $constructorParametersMetadata = getConstructorParametersMetadata(A::class);

        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], array_keys($constructorParametersMetadata));
    }

    /**
     * it matches "list type":
     *  a concatenation of valid PHP class name (fully qualified) with "[]"
     *
     *  e.g:
     *      - \Vendor\Class[]
     *      - Vendor\Class[]
     *      - Class[]
     *
     * @test
     * @dataProvider validListTypeDataProvider
     */
    public function itMatchesValidListTypes(string $listType): void
    {
        $this->assertMatchesRegularExpression(ParameterMetadata::FQCN, $listType);
    }

    /**
     * @test
     * @dataProvider invalidListTypeDataProvider
     */
    public function itMisMatchesInvalidListTypes(string $invalidListType): void
    {
        $this->assertDoesNotMatchRegularExpression(ParameterMetadata::FQCN, $invalidListType);
    }

    public function validListTypeDataProvider(): array
    {
        return [
            ['\Fully\Qualified\ClassName[]'],
            ['Fully\Qualified\ClassName[]'],
            ['\ClassName[]'],
            ['ClassName[]']
        ];
    }

    public function invalidListTypeDataProvider(): array
    {
        return [
            ['\Fully\Qualified\ClassName'],
            ['Fully\Qualified\ClassName\[]'],
            ['[]'],
            ['1ClassName[]'],
            ['1\ClassName[]'],
            ['\Namespace\1ClassName[]']
        ];
    }
}

class A {
    /**
     * @param int $a
     * @param stdClass[] $e
     */
    public function __construct($a, int $b, array $c, \ArrayObject $d, array $e = [])
    {
    }
}

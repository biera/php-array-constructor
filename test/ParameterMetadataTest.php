<?php declare(strict_types=1);

namespace ParameterMetadataTest;

use PHPUnit\Framework\TestCase;
use Biera\ParameterMetadata;
use function Biera\getConstructorParametersMetadata;

class ParameterMetadataTest extends TestCase
{
    /**
     * @test
     * @covers getConstructorParametersMetadata
     */
    public function itGetsConstructorParametersMetadata(): void
    {
        // no __construct method
        $this->assertEmpty(getConstructorParametersMetadata(A::class));

        // param-less __construct method
        $this->assertEmpty(getConstructorParametersMetadata(B::class));

        $constructorParametersMetadata = getConstructorParametersMetadata(C::class);

        /** @var ParameterMetadata $aParameter */
        $aParameter = $constructorParametersMetadata['a'];
        $this->assertInstanceOf(ParameterMetadata::class, $aParameter);
        $this->assertEquals('a', $aParameter->getName());
        $this->assertEquals([['int', true, false]], $aParameter->getTypes());
        $this->assertEquals(null, $aParameter->getDefault(null));
        $this->assertTrue($aParameter->isPrimitive());
        $this->assertFalse($aParameter->isComplex());
        $this->assertFalse($aParameter->isList());
        $this->assertTrue($aParameter->isNullable());

        /** @var ParameterMetadata $bParameter */
        $bParameter = $constructorParametersMetadata['b'];
        $this->assertInstanceOf(ParameterMetadata::class, $bParameter);
        $this->assertEquals('b', $bParameter->getName());
        $this->assertEquals([['int', true, false]], $bParameter->getTypes());
        $this->assertEquals(null, $bParameter->getDefault(null));
        $this->assertTrue($bParameter->isPrimitive());
        $this->assertFalse($bParameter->isComplex());
        $this->assertFalse($bParameter->isList());
        $this->assertFalse($bParameter->isNullable());

        /** @var ParameterMetadata $cParameter */
        $cParameter = $constructorParametersMetadata['c'];
        $this->assertInstanceOf(ParameterMetadata::class, $cParameter);
        $this->assertEquals('c', $cParameter->getName());
        $this->assertEquals([['array', true, false]], $cParameter->getTypes());
        $this->assertEquals(null, $cParameter->getDefault(null));
        $this->assertTrue($cParameter->isPrimitive());
        $this->assertFalse($cParameter->isComplex());
        $this->assertFalse($cParameter->isList());
        $this->assertFalse($cParameter->isNullable());

        /** @var ParameterMetadata $dParameter */
        $dParameter = $constructorParametersMetadata['d'];
        $this->assertInstanceOf(ParameterMetadata::class, $dParameter);
        $this->assertEquals('d', $dParameter->getName());
        $this->assertEquals([[\ArrayObject::class, false, false]], $dParameter->getTypes());
        $this->assertEquals(null, $dParameter->getDefault(null));
        $this->assertFalse($dParameter->isPrimitive());
        $this->assertTrue($dParameter->isComplex());
        $this->assertFalse($dParameter->isList());
        $this->assertFalse($dParameter->isNullable());

        /** @var ParameterMetadata $eParameter */
        $eParameter = $constructorParametersMetadata['e'];
        $this->assertInstanceOf(ParameterMetadata::class, $eParameter);
        $this->assertEquals('e', $eParameter->getName());
        $this->assertEquals([[\stdClass::class, false, true]], $eParameter->getTypes());
        $this->assertEquals([], $eParameter->getDefault(null));
        $this->assertFalse($eParameter->isPrimitive());
        $this->assertTrue($eParameter->isComplex());
        $this->assertTrue($eParameter->isList());
        $this->assertFalse($eParameter->isNullable());

        /** @var ParameterMetadata $fParameter */
        $fParameter = $constructorParametersMetadata['f'];
        $this->assertInstanceOf(ParameterMetadata::class, $fParameter);
        $this->assertEquals('f', $fParameter->getName());
        $this->assertEquals([[\stdClass::class, false, true], ['string', true, true]], $fParameter->getTypes());
        $this->assertEquals([], $fParameter->getDefault(null));
        $this->assertTrue($fParameter->isPrimitive());
        $this->assertTrue($fParameter->isComplex());
        $this->assertTrue($fParameter->isList());
        $this->assertFalse($fParameter->isNullable());
    }

    /**
     * @test
     * @covers getConstructorParametersMetadata
     */
    public function itGetsConstructorParametersMetadataInCorrectOrder(): void
    {
        $constructorParametersMetadata = getConstructorParametersMetadata(C::class);

        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], array_keys($constructorParametersMetadata));
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
    public function itMismatchesInvalidListTypes(string $invalidListType): void
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

class A
{
}

class B
{
    public function __construct()
    {
    }
}

class C {
    /**
     * @param int $a
     * @param stdClass[] $e
     * @param stdClass[]|string[] $f
     */
    public function __construct($a, int $b, array $c, \ArrayObject $d, array $e = [], array $f = [])
    {
    }
}

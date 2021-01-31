<?php declare(strict_types=1);

namespace ArrayConstructorTest;

use Biera\InstantiationException;
use PHPUnit\Framework\TestCase;
use Biera\ArrayConstructor;

class ArrayConstructorTest extends TestCase
{
    /** @test */
    public function itConstructsFromArray(): void
    {
        $primitive = 'I am so primitive';
        $complex = new B([]);
        $params = [
            'primitive' => $primitive,
            'complex' => $complex
        ];

        /** @var A $a */
        $a = A::createFromArray($params);
        $this->assertInstanceOf(A::class, $a);
        $this->assertEquals($primitive, $a->getPrimitive());
        $this->assertSame($complex, $a->getComplex());
    }

    /** @test */
    public function itConstructsFromPrimitives(): void
    {
        $params = [
            'primitive' => 'I am so primitive',
            'complex' => [
                'complexList' => [
                    [], ['optionalPrimitive' => 10]
                ]
            ]
        ];

        $a = A::createFromPrimitives($params);
        $this->assertInstanceOf(A::class, $a);
        $this->assertEquals($params['primitive'], $a->getPrimitive());

        /** @var B $b */
        $b = $a->getComplex();
        $this->assertInstanceOf(B::class, $b);

        $complexList = $b->getComplexList();
        $this->assertCount(2, $complexList);
        $this->assertInstanceOf(C::class, $complexList[0]);
        $this->assertInstanceOf(C::class, $complexList[1]);
        $this->assertEquals(10, $complexList[1]->getOptionalPrimitive());
    }

    /** @test */
    public function itConstructsFromComplexAndPrimitives(): void
    {
        $d = new D(1);
        $params = [
            'primitive' => 'I am so primitive',
            'complex' => [
                'complexList' => [
                    // optionalComplex is already
                    // an instance of D class
                    ['optionalComplex' => $d]
                ]
            ]
        ];

        /** @var A $a */
        $a = A::createFromPrimitives($params);
        $this->assertSame($d, $a->getComplex()->getComplexList()[0]->getOptionalComplex());
    }

    /** @test */
    public function itRaisesAnErrorWhenInvalidParamsProvided(): void
    {
        $primitiveParams = [
            'primitive' => 'I am so primitive',
            'complex' => [
                'complexList' => [
                    // optionalComplex is missing 'primitive' key
                    // to instantiate object of D type
                    [], ['optionalPrimitive' => 10, 'optionalComplex' => []]
                ]
            ]
        ];

        try {
            A::createFromPrimitives($primitiveParams);

            $this->fail('\Biera\InstantiationException was expected to be thrown');
        } catch (InstantiationException $instantiationError) {
            $instantiationErrors = $instantiationError->getErrors();

            $this->assertEquals('complex -> complexList -> optionalComplex -> optionalComplex', $instantiationError->getPath());
            $this->assertCount(1, $instantiationErrors);
            $this->assertInstanceOf(\TypeError::class, $instantiationErrors[0]);
        } catch (\Throwable $anythingElse) {
            $this->fail(
                sprintf('\Biera\InstantiationException was expected to be thrown. %s was thrown instead', get_class($anythingElse))
            );
        }
    }
}

class A
{
    use ArrayConstructor;

    private $primitive;
    private $complex;

    public function __construct(string $primitive, B $complex)
    {
        $this->primitive = $primitive;
        $this->complex = $complex;
    }

    public function getPrimitive(): string
    {
        return $this->primitive;
    }

    public function getComplex(): B
    {
        return $this->complex;
    }
}

class B
{
    use ArrayConstructor;

    private $complexList;

    /** @param \ArrayConstructorTest\C[] $complexList */
    public function __construct(array $complexList)
    {
        $this->complexList = $complexList;
    }

    public function getComplexList()
    {
        return $this->complexList;
    }
}

class C
{
    use ArrayConstructor;

    private $optionalPrimitive;
    private ?D $optionalComplex;

    public function __construct($optionalPrimitive = null, D $optionalComplex = null)
    {
        $this->optionalPrimitive = $optionalPrimitive;
        $this->optionalComplex = $optionalComplex;
    }

    public function getOptionalPrimitive()
    {
        return $this->optionalPrimitive;
    }

    public function getOptionalComplex(): ?D
    {
        return $this->optionalComplex;
    }
}

class D
{
    use ArrayConstructor;

    public function __construct(int $primitive)
    {
    }
}


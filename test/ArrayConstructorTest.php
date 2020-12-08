<?php declare(strict_types=1);

namespace ArrayConstructorTest;

use PHPUnit\Framework\TestCase;
use Biera\ArrayConstructor;

class ArrayConstructorTest extends TestCase
{
    /** @test */
    public function itConstructsFromArray()
    {
        $primitive = 'I am so primitive';
        $nonPrimitive = new B([]);
        $aParams = [
            'primitive' => $primitive,
            'nonPrimitive' => $nonPrimitive
        ];

        $a = A::createFromArray($aParams);
        $this->assertInstanceOf(A::class, $a);
        $this->assertEquals($primitive, $a->getPrimitive());
        $this->assertSame($nonPrimitive, $a->getNonPrimitive());
    }

    /** @test */
    public function itConstructsFromPrimitives()
    {
        $aPrimitiveParams = [
            'primitive' => 'I am so primitive',
            'nonPrimitive' => [
                'nonPrimitiveList' => [
                    [], ['optional' => 10]
                ]
            ]
        ];

        $a = A::createFromPrimitives($aPrimitiveParams);
        $this->assertInstanceOf(A::class, $a);
        $this->assertEquals($aPrimitiveParams['primitive'], $a->getPrimitive());

        $b = $a->getNonPrimitive();
        $this->assertInstanceOf(B::class, $b);

        $nonPrimitiveList = $b->getNonPrimitiveList();
        $this->assertCount(2, $nonPrimitiveList);
        $this->assertInstanceOf(C::class, $nonPrimitiveList[0]);
        $this->assertInstanceOf(C::class, $nonPrimitiveList[1]);
        $this->assertEquals(10, $nonPrimitiveList[1]->getOptional());
    }
}

class A
{
    use ArrayConstructor;

    private $primitive;
    private $nonPrimitive;

    public function __construct(string $primitive, B $nonPrimitive)
    {
        $this->primitive = $primitive;
        $this->nonPrimitive = $nonPrimitive;
    }

    public function getPrimitive(): string
    {
        return $this->primitive;
    }

    public function getNonPrimitive(): B
    {
        return $this->nonPrimitive;
    }
}

class B
{
    use ArrayConstructor;

    private $nonPrimitiveList;

    /** @param \ArrayConstructorTest\C[] $nonPrimitiveList */
    public function __construct(array $nonPrimitiveList)
    {
        $this->nonPrimitiveList = $nonPrimitiveList;
    }

    public function getNonPrimitiveList()
    {
        return $this->nonPrimitiveList;
    }
}

class C
{
    use ArrayConstructor;

    private $optional;

    public function __construct($optional = null)
    {
        $this->optional = $optional;
    }

    public function getOptional()
    {
        return $this->optional;
    }
}




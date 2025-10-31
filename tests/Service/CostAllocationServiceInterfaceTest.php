<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Service\CostAllocationServiceInterface;

/**
 * @internal
 */
#[CoversClass(CostAllocationServiceInterface::class)]
class CostAllocationServiceInterfaceTest extends TestCase
{
    /**
     * 安全地从 ReflectionType 实例中提取类型名称。
     */
    private function getTypeName(?\ReflectionType $type): ?string
    {
        if (null === $type) {
            return null;
        }

        // Handle union types (PHP 8.0+)
        if ($type instanceof \ReflectionUnionType) {
            /** @var array<string> $names */
            $names = array_map(fn (\ReflectionType $t) => $this->getTypeName($t), $type->getTypes());

            return implode('|', array_filter($names, fn (?string $name): bool => null !== $name && '' !== $name));
        }

        // Handle intersection types (PHP 8.1+)
        if ($type instanceof \ReflectionIntersectionType) {
            /** @var array<string> $names */
            $names = array_map(fn (\ReflectionType $t) => $this->getTypeName($t), $type->getTypes());

            return implode('&', array_filter($names, fn (?string $name): bool => null !== $name && '' !== $name));
        }

        // Handle named types (the common case)
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        // Fallback for unknown types
        return (string) $type;
    }

    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(CostAllocationServiceInterface::class));
    }

    public function testInterfaceHasCreateAllocationRuleMethod(): void
    {
        $reflection = new \ReflectionClass(CostAllocationServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('createAllocationRule'));

        $method = $reflection->getMethod('createAllocationRule');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('Tourze\StockCostBundle\Entity\CostAllocation', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(4, $parameters);

        $this->assertEquals('name', $parameters[0]->getName());
        $this->assertEquals('string', $this->getTypeName($parameters[0]->getType()));

        $this->assertEquals('type', $parameters[1]->getName());
        $this->assertEquals('string', $this->getTypeName($parameters[1]->getType()));

        $this->assertEquals('criteria', $parameters[2]->getName());
        $this->assertEquals('array', $this->getTypeName($parameters[2]->getType()));

        $this->assertEquals('targets', $parameters[3]->getName());
        $this->assertEquals('array', $this->getTypeName($parameters[3]->getType()));
    }

    public function testInterfaceHasAllocateCostMethod(): void
    {
        $reflection = new \ReflectionClass(CostAllocationServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('allocateCost'));

        $method = $reflection->getMethod('allocateCost');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('array', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('totalCost', $parameters[0]->getName());
        $this->assertEquals('float', $this->getTypeName($parameters[0]->getType()));

        $this->assertEquals('rule', $parameters[1]->getName());
        $this->assertEquals('Tourze\StockCostBundle\Entity\CostAllocation', $this->getTypeName($parameters[1]->getType()));

        $this->assertEquals('effectiveDate', $parameters[2]->getName());
        $this->assertEquals('DateTimeImmutable', $this->getTypeName($parameters[2]->getType()));
    }

    public function testInterfaceHasGetAllocatedCostMethod(): void
    {
        $reflection = new \ReflectionClass(CostAllocationServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('getAllocatedCost'));

        $method = $reflection->getMethod('getAllocatedCost');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('float', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('skuId', $parameters[0]->getName());
        $this->assertEquals('string', $this->getTypeName($parameters[0]->getType()));

        $this->assertEquals('date', $parameters[1]->getName());
        $this->assertEquals('DateTimeImmutable', $this->getTypeName($parameters[1]->getType()));
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;

/**
 * @internal
 */
#[CoversClass(CostStrategyCalculatorInterface::class)]
class CostStrategyCalculatorInterfaceTest extends TestCase
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

            return implode('|', array_filter($names, fn (?string $value): bool => null !== $value && '' !== $value));
        }

        // Handle intersection types (PHP 8.1+)
        if ($type instanceof \ReflectionIntersectionType) {
            /** @var array<string> $names */
            $names = array_map(fn (\ReflectionType $t) => $this->getTypeName($t), $type->getTypes());

            return implode('&', array_filter($names, fn (?string $value): bool => null !== $value && '' !== $value));
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
        $this->assertTrue(interface_exists(CostStrategyCalculatorInterface::class));
    }

    public function testInterfaceHasCalculateMethod(): void
    {
        $reflection = new \ReflectionClass(CostStrategyCalculatorInterface::class);

        $this->assertTrue($reflection->hasMethod('calculate'));

        $method = $reflection->getMethod('calculate');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(CostCalculationResult::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('sku', $parameters[0]->getName());
        $this->assertEquals('string', $this->getTypeName($parameters[0]->getType()));

        $this->assertEquals('quantity', $parameters[1]->getName());
        $this->assertEquals('int', $this->getTypeName($parameters[1]->getType()));
    }

    public function testInterfaceHasSupportsMethod(): void
    {
        $reflection = new \ReflectionClass(CostStrategyCalculatorInterface::class);

        $this->assertTrue($reflection->hasMethod('supports'));

        $method = $reflection->getMethod('supports');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('bool', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('strategy', $parameters[0]->getName());
        $this->assertEquals(CostStrategy::class, $this->getTypeName($parameters[0]->getType()));
    }

    public function testInterfaceHasGetSupportedStrategyMethod(): void
    {
        $reflection = new \ReflectionClass(CostStrategyCalculatorInterface::class);

        $this->assertTrue($reflection->hasMethod('getSupportedStrategy'));

        $method = $reflection->getMethod('getSupportedStrategy');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(CostStrategy::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    public function testInterfaceHasRecalculateMethod(): void
    {
        $reflection = new \ReflectionClass(CostStrategyCalculatorInterface::class);

        $this->assertTrue($reflection->hasMethod('recalculate'));

        $method = $reflection->getMethod('recalculate');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('array', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('skus', $parameters[0]->getName());
        $this->assertEquals('array', $this->getTypeName($parameters[0]->getType()));
    }

    public function testInterfaceHasCanCalculateMethod(): void
    {
        $reflection = new \ReflectionClass(CostStrategyCalculatorInterface::class);

        $this->assertTrue($reflection->hasMethod('canCalculate'));

        $method = $reflection->getMethod('canCalculate');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('bool', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('sku', $parameters[0]->getName());
        $this->assertEquals('string', $this->getTypeName($parameters[0]->getType()));

        $this->assertEquals('quantity', $parameters[1]->getName());
        $this->assertEquals('int', $this->getTypeName($parameters[1]->getType()));
    }
}

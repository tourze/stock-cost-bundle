<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Service\CostPeriodServiceInterface;

/**
 * @internal
 */
#[CoversClass(CostPeriodServiceInterface::class)]
class CostPeriodServiceInterfaceTest extends TestCase
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
        $this->assertTrue(interface_exists(CostPeriodServiceInterface::class));
    }

    public function testInterfaceHasCreatePeriodMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('createPeriod'));

        $method = $reflection->getMethod('createPeriod');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('periodStart', $parameters[0]->getName());
        $this->assertEquals('DateTimeImmutable', $this->getTypeName($parameters[0]->getType()));

        $this->assertEquals('periodEnd', $parameters[1]->getName());
        $this->assertEquals('DateTimeImmutable', $this->getTypeName($parameters[1]->getType()));

        $this->assertEquals('defaultStrategy', $parameters[2]->getName());
        $this->assertEquals(CostStrategy::class, $this->getTypeName($parameters[2]->getType()));
        $this->assertTrue($parameters[2]->isOptional());
    }

    public function testInterfaceHasClosePeriodMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('closePeriod'));

        $method = $reflection->getMethod('closePeriod');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('period', $parameters[0]->getName());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($parameters[0]->getType()));
    }

    public function testInterfaceHasFreezePeriodMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('freezePeriod'));

        $method = $reflection->getMethod('freezePeriod');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('period', $parameters[0]->getName());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($parameters[0]->getType()));
    }

    public function testInterfaceHasUnfreezePeriodMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('unfreezePeriod'));

        $method = $reflection->getMethod('unfreezePeriod');
        $this->assertTrue($method->isPublic());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('period', $parameters[0]->getName());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($parameters[0]->getType()));
    }

    public function testInterfaceHasGetCurrentPeriodMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('getCurrentPeriod'));

        $method = $reflection->getMethod('getCurrentPeriod');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->getReturnType()?->allowsNull());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    public function testInterfaceHasFindPeriodByDateMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('findPeriodByDate'));

        $method = $reflection->getMethod('findPeriodByDate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->getReturnType()?->allowsNull());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('date', $parameters[0]->getName());
        $this->assertEquals('DateTimeImmutable', $this->getTypeName($parameters[0]->getType()));
    }

    public function testInterfaceHasCanClosePeriodMethod(): void
    {
        $reflection = new \ReflectionClass(CostPeriodServiceInterface::class);

        $this->assertTrue($reflection->hasMethod('canClosePeriod'));

        $method = $reflection->getMethod('canClosePeriod');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('bool', $this->getTypeName($method->getReturnType()));

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('period', $parameters[0]->getName());
        $this->assertEquals(CostPeriod::class, $this->getTypeName($parameters[0]->getType()));
    }
}

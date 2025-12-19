<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Service\CostCalculatorRegistry;
use Tourze\StockCostBundle\Service\CostCalculatorRegistryInterface;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;

/**
 * @internal
 */
#[CoversClass(CostCalculatorRegistry::class)]
final class CostCalculatorRegistryTest extends TestCase
{
    private CostCalculatorRegistry $registry;
    private CostStrategyCalculatorInterface $fifoCalculator;
    private CostStrategyCalculatorInterface $lifoCalculator;
    private CostStrategyCalculatorInterface $weightedAverageCalculator;
    private CostStrategyCalculatorInterface $standardCostCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建 Mock 计算器实例用于测试
        $this->fifoCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $this->lifoCalculator = $this->createMockCalculator(CostStrategy::LIFO);
        $this->weightedAverageCalculator = $this->createMockCalculator(CostStrategy::WEIGHTED_AVERAGE);
        $this->standardCostCalculator = $this->createMockCalculator(CostStrategy::STANDARD_COST);

        // 创建空的 Registry 用于测试
        $this->registry = new CostCalculatorRegistry();
    }

    private function createMockCalculator(CostStrategy $strategy): CostStrategyCalculatorInterface
    {
        $calculator = $this->createMock(CostStrategyCalculatorInterface::class);
        $calculator->method('getSupportedStrategy')->willReturn($strategy);
        $calculator->method('supports')->willReturnCallback(
            fn (CostStrategy $s) => $s === $strategy
        );

        return $calculator;
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostCalculatorRegistryInterface::class, $this->registry);
    }

    public function testRegisterCalculator(): void
    {
        $this->registry->registerCalculator($this->fifoCalculator);

        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($this->fifoCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));
    }

    public function testRegisterMultipleCalculators(): void
    {
        $this->registry->registerCalculator($this->fifoCalculator);
        $this->registry->registerCalculator($this->lifoCalculator);

        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::LIFO));
        $this->assertFalse($this->registry->hasCalculatorForStrategy(CostStrategy::WEIGHTED_AVERAGE));

        $this->assertSame($this->fifoCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($this->lifoCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::LIFO));
    }

    public function testOverrideExistingCalculator(): void
    {
        // 先注册 FIFO 计算器
        $this->registry->registerCalculator($this->fifoCalculator);
        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));

        // 再次注册同一个 FIFO 计算器(模拟覆盖)
        $this->registry->registerCalculator($this->fifoCalculator);

        // 验证仍然可以获取到该计算器
        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($this->fifoCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));
    }

    public function testGetCalculatorForStrategyThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid cost calculation strategy: WEIGHTED_AVERAGE');

        $this->registry->getCalculatorForStrategy(CostStrategy::WEIGHTED_AVERAGE);
    }

    public function testHasCalculatorForStrategyReturnsFalseWhenNotRegistered(): void
    {
        $this->assertFalse($this->registry->hasCalculatorForStrategy(CostStrategy::STANDARD_COST));
    }

    public function testGetAllCalculators(): void
    {
        $this->registry->registerCalculator($this->fifoCalculator);
        $this->registry->registerCalculator($this->lifoCalculator);

        $allCalculators = $this->registry->getAllCalculators();

        $this->assertCount(2, $allCalculators);
        $this->assertContains($this->fifoCalculator, $allCalculators);
        $this->assertContains($this->lifoCalculator, $allCalculators);
    }

    public function testGetAllCalculatorsReturnsEmptyArrayWhenNoneRegistered(): void
    {
        $this->assertSame([], $this->registry->getAllCalculators());
    }

    public function testGetSupportedStrategies(): void
    {
        $this->registry->registerCalculator($this->fifoCalculator);
        $this->registry->registerCalculator($this->lifoCalculator);

        $supportedStrategies = $this->registry->getSupportedStrategies();

        $this->assertCount(2, $supportedStrategies);
        $this->assertContains(CostStrategy::FIFO, $supportedStrategies);
        $this->assertContains(CostStrategy::LIFO, $supportedStrategies);
    }

    public function testGetSupportedStrategiesReturnsEmptyArrayWhenNoneRegistered(): void
    {
        $this->assertSame([], $this->registry->getSupportedStrategies());
    }

    public function testUnregisterCalculator(): void
    {
        $this->registry->registerCalculator($this->fifoCalculator);
        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));

        $this->registry->unregisterCalculator(CostStrategy::FIFO);
        $this->assertFalse($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));
    }

    public function testUnregisterNonExistentCalculatorDoesNotThrowException(): void
    {
        $this->registry->unregisterCalculator(CostStrategy::WEIGHTED_AVERAGE);

        // No exception should be thrown
        $this->assertFalse($this->registry->hasCalculatorForStrategy(CostStrategy::WEIGHTED_AVERAGE));
    }

    public function testClearAllCalculators(): void
    {
        $this->registry->registerCalculator($this->fifoCalculator);
        $this->registry->registerCalculator($this->lifoCalculator);

        $this->assertCount(2, $this->registry->getAllCalculators());

        $this->registry->clearAllCalculators();

        $this->assertCount(0, $this->registry->getAllCalculators());
        $this->assertSame([], $this->registry->getSupportedStrategies());
    }

    public function testRegisterCalculatorWithConstructorInjection(): void
    {
        $calculators = [$this->fifoCalculator, $this->lifoCalculator];

        $registry = new CostCalculatorRegistry($calculators);

        $this->assertTrue($registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertTrue($registry->hasCalculatorForStrategy(CostStrategy::LIFO));
        $this->assertSame($this->fifoCalculator, $registry->getCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($this->lifoCalculator, $registry->getCalculatorForStrategy(CostStrategy::LIFO));
    }
}

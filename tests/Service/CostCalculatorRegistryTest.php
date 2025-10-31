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
class CostCalculatorRegistryTest extends TestCase
{
    private CostCalculatorRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CostCalculatorRegistry();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostCalculatorRegistryInterface::class, $this->registry);
    }

    public function testRegisterCalculator(): void
    {
        $calculator = $this->createMockCalculator(CostStrategy::FIFO);

        $this->registry->registerCalculator($calculator);

        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($calculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));
    }

    public function testRegisterMultipleCalculators(): void
    {
        $fifoCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $lifoCalculator = $this->createMockCalculator(CostStrategy::LIFO);

        $this->registry->registerCalculator($fifoCalculator);
        $this->registry->registerCalculator($lifoCalculator);

        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertTrue($this->registry->hasCalculatorForStrategy(CostStrategy::LIFO));
        $this->assertFalse($this->registry->hasCalculatorForStrategy(CostStrategy::WEIGHTED_AVERAGE));

        $this->assertSame($fifoCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($lifoCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::LIFO));
    }

    public function testOverrideExistingCalculator(): void
    {
        $originalCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $newCalculator = $this->createMockCalculator(CostStrategy::FIFO);

        $this->registry->registerCalculator($originalCalculator);
        $this->assertSame($originalCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));

        $this->registry->registerCalculator($newCalculator);
        $this->assertSame($newCalculator, $this->registry->getCalculatorForStrategy(CostStrategy::FIFO));
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
        $fifoCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $lifoCalculator = $this->createMockCalculator(CostStrategy::LIFO);

        $this->registry->registerCalculator($fifoCalculator);
        $this->registry->registerCalculator($lifoCalculator);

        $allCalculators = $this->registry->getAllCalculators();

        $this->assertCount(2, $allCalculators);
        $this->assertContains($fifoCalculator, $allCalculators);
        $this->assertContains($lifoCalculator, $allCalculators);
    }

    public function testGetAllCalculatorsReturnsEmptyArrayWhenNoneRegistered(): void
    {
        $this->assertSame([], $this->registry->getAllCalculators());
    }

    public function testGetSupportedStrategies(): void
    {
        $fifoCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $lifoCalculator = $this->createMockCalculator(CostStrategy::LIFO);

        $this->registry->registerCalculator($fifoCalculator);
        $this->registry->registerCalculator($lifoCalculator);

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
        $calculator = $this->createMockCalculator(CostStrategy::FIFO);

        $this->registry->registerCalculator($calculator);
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
        $fifoCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $lifoCalculator = $this->createMockCalculator(CostStrategy::LIFO);

        $this->registry->registerCalculator($fifoCalculator);
        $this->registry->registerCalculator($lifoCalculator);

        $this->assertCount(2, $this->registry->getAllCalculators());

        $this->registry->clearAllCalculators();

        $this->assertCount(0, $this->registry->getAllCalculators());
        $this->assertSame([], $this->registry->getSupportedStrategies());
    }

    public function testRegisterCalculatorWithConstructorInjection(): void
    {
        $fifoCalculator = $this->createMockCalculator(CostStrategy::FIFO);
        $lifoCalculator = $this->createMockCalculator(CostStrategy::LIFO);
        $calculators = [$fifoCalculator, $lifoCalculator];

        $registry = new CostCalculatorRegistry($calculators);

        $this->assertTrue($registry->hasCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertTrue($registry->hasCalculatorForStrategy(CostStrategy::LIFO));
        $this->assertSame($fifoCalculator, $registry->getCalculatorForStrategy(CostStrategy::FIFO));
        $this->assertSame($lifoCalculator, $registry->getCalculatorForStrategy(CostStrategy::LIFO));
    }

    private function createMockCalculator(CostStrategy $strategy): CostStrategyCalculatorInterface
    {
        $calculator = $this->createMock(CostStrategyCalculatorInterface::class);
        $calculator->method('supports')->willReturnCallback(
            fn (CostStrategy $testStrategy): bool => $testStrategy === $strategy
        );
        $calculator->method('getSupportedStrategy')->willReturn($strategy);

        return $calculator;
    }
}

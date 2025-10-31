<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\InvalidCostStrategyException;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\CostService;
use Tourze\StockCostBundle\Service\CostServiceInterface;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;

/**
 * @internal
 */
#[CoversClass(CostService::class)]
class CostServiceTest extends TestCase
{
    private CostService $costService;

    private CostStrategyCalculatorInterface $mockCalculator;

    protected function setUp(): void
    {
        $this->mockCalculator = $this->createMock(CostStrategyCalculatorInterface::class);
        $this->costService = new CostService([$this->mockCalculator]);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostServiceInterface::class, $this->costService);
    }

    public function testCanBeInstantiated(): void
    {
        $service = new CostService([]);
        $this->assertInstanceOf(CostService::class, $service);
    }

    public function testCalculateCostWithDefaultStrategy(): void
    {
        $expectedResult = new CostCalculationResult(
            sku: 'SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );

        $this->mockCalculator
            ->expects($this->once())
            ->method('supports')
            ->with(CostStrategy::FIFO)
            ->willReturn(true)
        ;

        $this->mockCalculator
            ->expects($this->once())
            ->method('calculate')
            ->with('SKU-001', 100)
            ->willReturn($expectedResult)
        ;

        $result = $this->costService->calculateCost('SKU-001', 100);

        $this->assertInstanceOf(CostCalculationResult::class, $result);
        $this->assertEquals('SKU-001', $result->getSku());
        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(15.50, $result->getUnitCost());
        $this->assertEquals(1550.00, $result->getTotalCost());
    }

    public function testCalculateCostWithSpecificStrategy(): void
    {
        $expectedResult = new CostCalculationResult(
            sku: 'SKU-002',
            quantity: 50,
            unitCost: 25.00,
            totalCost: 1250.00,
            strategy: CostStrategy::LIFO
        );

        $this->mockCalculator
            ->expects($this->once())
            ->method('supports')
            ->with(CostStrategy::LIFO)
            ->willReturn(true)
        ;

        $this->mockCalculator
            ->expects($this->once())
            ->method('calculate')
            ->with('SKU-002', 50)
            ->willReturn($expectedResult)
        ;

        $result = $this->costService->calculateCost('SKU-002', 50, CostStrategy::LIFO);

        $this->assertEquals(CostStrategy::LIFO, $result->getStrategy());
    }

    public function testBatchCalculateCost(): void
    {
        $items = [
            ['sku' => 'SKU-001', 'quantity' => 100],
            ['sku' => 'SKU-002', 'quantity' => 50],
        ];

        $expectedResults = [
            new CostCalculationResult('SKU-001', 100, 15.50, 1550.00, CostStrategy::FIFO),
            new CostCalculationResult('SKU-002', 50, 25.00, 1250.00, CostStrategy::FIFO),
        ];

        $this->mockCalculator
            ->method('supports')
            ->willReturn(true)
        ;

        $this->mockCalculator
            ->method('calculate')
            ->willReturnCallback(function (string $sku, int $quantity) {
                if ('SKU-001' === $sku) {
                    return new CostCalculationResult('SKU-001', 100, 15.50, 1550.00, CostStrategy::FIFO);
                }

                return new CostCalculationResult('SKU-002', 50, 25.00, 1250.00, CostStrategy::FIFO);
            })
        ;

        $results = $this->costService->batchCalculateCost($items);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(CostCalculationResult::class, $results);
        $this->assertEquals('SKU-001', $results[0]->getSku());
        $this->assertEquals('SKU-002', $results[1]->getSku());
    }

    public function testGetDefaultStrategy(): void
    {
        $strategy = $this->costService->getDefaultStrategy();
        $this->assertEquals(CostStrategy::FIFO, $strategy);
    }

    public function testSetDefaultStrategy(): void
    {
        $this->costService->setDefaultStrategy(CostStrategy::LIFO);
        $this->assertEquals(CostStrategy::LIFO, $this->costService->getDefaultStrategy());
    }

    public function testThrowsExceptionWhenNoCalculatorSupportsStrategy(): void
    {
        $this->mockCalculator
            ->method('supports')
            ->willReturn(false)
        ;

        $this->expectException(InvalidCostStrategyException::class);
        $this->expectExceptionMessage('Invalid cost calculation strategy: WEIGHTED_AVERAGE');

        $this->costService->calculateCost('SKU-001', 100, CostStrategy::WEIGHTED_AVERAGE);
    }

    public function testPerformanceRequirement(): void
    {
        // 测试1秒内完成单个SKU计算的性能要求
        $this->mockCalculator
            ->method('supports')
            ->willReturn(true)
        ;

        $this->mockCalculator
            ->method('calculate')
            ->willReturn(new CostCalculationResult('SKU-001', 100, 15.50, 1550.00, CostStrategy::FIFO))
        ;

        $startTime = microtime(true);
        $this->costService->calculateCost('SKU-001', 100);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, 'Cost calculation should complete within 1 second');
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service\Calculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Entity\StockRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\Calculator\WeightedAverageCostCalculator;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * @internal
 */
#[CoversClass(WeightedAverageCostCalculator::class)]
class WeightedAverageCostCalculatorTest extends TestCase
{
    private WeightedAverageCostCalculator $calculator;

    private StockRecordServiceInterface $mockStockRecordService;

    protected function setUp(): void
    {
        $this->mockStockRecordService = $this->createMock(StockRecordServiceInterface::class);
        $this->calculator = new WeightedAverageCostCalculator($this->mockStockRecordService);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostStrategyCalculatorInterface::class, $this->calculator);
    }

    public function testSupportsWeightedAverageStrategy(): void
    {
        $this->assertTrue($this->calculator->supports(CostStrategy::WEIGHTED_AVERAGE));
    }

    public function testDoesNotSupportOtherStrategies(): void
    {
        $this->assertFalse($this->calculator->supports(CostStrategy::FIFO));
        $this->assertFalse($this->calculator->supports(CostStrategy::LIFO));
        $this->assertFalse($this->calculator->supports(CostStrategy::STANDARD_COST));
    }

    public function testGetSupportedStrategy(): void
    {
        $this->assertEquals(CostStrategy::WEIGHTED_AVERAGE, $this->calculator->getSupportedStrategy());
    }

    public function testCalculateWeightedAverageCost(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 100, 10.00, 100),
            $this->createStockRecord('2024-01-02', 50, 12.00, 50),
            $this->createStockRecord('2024-01-03', 75, 14.00, 75),
        ];

        $this->mockStockRecordService
            ->expects($this->once())
            ->method('getStockRecordsForSku')
            ->with('SKU-001')
            ->willReturn($stockRecords)
        ;

        $result = $this->calculator->calculate('SKU-001', 100);

        $this->assertInstanceOf(CostCalculationResult::class, $result);
        $this->assertEquals('SKU-001', $result->getSku());
        $this->assertEquals(100, $result->getQuantity());
        $this->assertEqualsWithDelta(11.78, $result->getUnitCost(), 0.01);
        $this->assertEqualsWithDelta(1177.78, $result->getTotalCost(), 0.01);
        $this->assertEquals(CostStrategy::WEIGHTED_AVERAGE, $result->getStrategy());
        $this->assertFalse($result->isPartialCalculation());
    }

    public function testCalculateWithExactStock(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 80, 15.00, 80),
            $this->createStockRecord('2024-01-02', 20, 25.00, 20),
        ];

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->with('SKU-002')
            ->willReturn($stockRecords)
        ;

        $result = $this->calculator->calculate('SKU-002', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(17.00, $result->getUnitCost());
        $this->assertEquals(1700.00, $result->getTotalCost());
        $this->assertFalse($result->isPartialCalculation());
    }

    public function testCalculateWithSingleRecord(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 100, 12.50, 100),
        ];

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn($stockRecords)
        ;

        $result = $this->calculator->calculate('SKU-003', 50);

        $this->assertEquals(12.50, $result->getUnitCost());
        $this->assertEquals(625.00, $result->getTotalCost());
    }

    public function testCalculateWithInsufficientStock(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 30, 10.00, 30),
            $this->createStockRecord('2024-01-02', 20, 15.00, 20),
        ];

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn($stockRecords)
        ;

        $result = $this->calculator->calculate('SKU-004', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(12.00, $result->getUnitCost());
        $this->assertEquals(1200.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCalculateWithNoStock(): void
    {
        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn([])
        ;

        $result = $this->calculator->calculate('SKU-005', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCalculateIgnoresZeroQuantityRecords(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 50, 10.00, 100),
            $this->createStockRecord('2024-01-02', 0, 15.00, 50),
            $this->createStockRecord('2024-01-03', 50, 20.00, 50),
        ];

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn($stockRecords)
        ;

        $result = $this->calculator->calculate('SKU-006', 80);

        $this->assertEquals(15.00, $result->getUnitCost());
        $this->assertEquals(1200.00, $result->getTotalCost());
    }

    public function testCanCalculateWithValidData(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 50, 10.00, 50),
        ];

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn($stockRecords)
        ;

        $this->assertTrue($this->calculator->canCalculate('SKU-001', 30));
    }

    public function testCannotCalculateWithZeroQuantity(): void
    {
        $this->assertFalse($this->calculator->canCalculate('SKU-001', 0));
        $this->assertFalse($this->calculator->canCalculate('SKU-001', -5));
    }

    public function testCannotCalculateWithNoStock(): void
    {
        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn([])
        ;

        $this->assertFalse($this->calculator->canCalculate('SKU-001', 10));
    }

    public function testRecalculateMultipleSkus(): void
    {
        $this->mockStockRecordService
            ->method('getCurrentStock')
            ->willReturnMap([
                ['SKU-001', 100],
                ['SKU-002', 50],
                ['SKU-003', 0],
            ])
        ;

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturnCallback(function (string $sku) {
                return match ($sku) {
                    'SKU-001' => [$this->createStockRecord('2024-01-01', 100, 10.00, 100)],
                    'SKU-002' => [$this->createStockRecord('2024-01-01', 50, 15.00, 50)],
                    default => [],
                };
            })
        ;

        $results = $this->calculator->recalculate(['SKU-001', 'SKU-002', 'SKU-003']);

        $this->assertCount(2, $results);
        $this->assertEquals('SKU-001', $results[0]->getSku());
        $this->assertEquals('SKU-002', $results[1]->getSku());
    }

    public function testCalculateWithComplexWeightedAverage(): void
    {
        $stockRecords = [
            $this->createStockRecord('2024-01-01', 40, 10.00, 40),
            $this->createStockRecord('2024-01-02', 30, 15.00, 30),
            $this->createStockRecord('2024-01-03', 30, 20.00, 30),
        ];

        $this->mockStockRecordService
            ->method('getStockRecordsForSku')
            ->willReturn($stockRecords)
        ;

        $result = $this->calculator->calculate('SKU-007', 70);

        $expectedWeightedAverage = (40 * 10.00 + 30 * 15.00 + 30 * 20.00) / 100;
        $this->assertEquals($expectedWeightedAverage, $result->getUnitCost());
        $this->assertEquals($expectedWeightedAverage * 70, $result->getTotalCost());
    }

    private function createStockRecord(string $date, int $currentQuantity, float $unitCost, int $originalQuantity): StockRecord
    {
        $record = new StockRecord();
        $record->setSku('TEST-SKU');
        $record->setRecordDate(new \DateTimeImmutable($date));
        $record->setCurrentQuantity($currentQuantity);
        $record->setUnitCost($unitCost);
        $record->setOriginalQuantity($originalQuantity);

        return $record;
    }
}

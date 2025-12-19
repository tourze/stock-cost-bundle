<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service\Calculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
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
#[RunTestsInSeparateProcesses]
final class WeightedAverageCostCalculatorTest extends AbstractIntegrationTestCase
{
    private WeightedAverageCostCalculator $calculator;

    private StockRecordServiceInterface $stockRecordService;

    protected function onSetUp(): void
    {
        $this->calculator = self::getContainer()->get(WeightedAverageCostCalculator::class);
        $this->stockRecordService = self::getContainer()->get(StockRecordServiceInterface::class);
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
        $this->createStockRecord('SKU-001', '2024-01-01', 100, 10.00, 100);
        $this->createStockRecord('SKU-001', '2024-01-02', 50, 12.00, 50);
        $this->createStockRecord('SKU-001', '2024-01-03', 75, 14.00, 75);

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
        $this->createStockRecord('SKU-002', '2024-01-01', 80, 15.00, 80);
        $this->createStockRecord('SKU-002', '2024-01-02', 20, 25.00, 20);

        $result = $this->calculator->calculate('SKU-002', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(17.00, $result->getUnitCost());
        $this->assertEquals(1700.00, $result->getTotalCost());
        $this->assertFalse($result->isPartialCalculation());
    }

    public function testCalculateWithSingleRecord(): void
    {
        $this->createStockRecord('SKU-003', '2024-01-01', 100, 12.50, 100);

        $result = $this->calculator->calculate('SKU-003', 50);

        $this->assertEquals(12.50, $result->getUnitCost());
        $this->assertEquals(625.00, $result->getTotalCost());
    }

    public function testCalculateWithInsufficientStock(): void
    {
        $this->createStockRecord('SKU-004', '2024-01-01', 30, 10.00, 30);
        $this->createStockRecord('SKU-004', '2024-01-02', 20, 15.00, 20);

        $result = $this->calculator->calculate('SKU-004', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(12.00, $result->getUnitCost());
        $this->assertEquals(1200.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCalculateWithNoStock(): void
    {
        $result = $this->calculator->calculate('SKU-005', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCalculateIgnoresZeroQuantityRecords(): void
    {
        $this->createStockRecord('SKU-006', '2024-01-01', 50, 10.00, 100);
        $this->createStockRecord('SKU-006', '2024-01-02', 0, 15.00, 50);
        $this->createStockRecord('SKU-006', '2024-01-03', 50, 20.00, 50);

        $result = $this->calculator->calculate('SKU-006', 80);

        $this->assertEquals(15.00, $result->getUnitCost());
        $this->assertEquals(1200.00, $result->getTotalCost());
    }

    public function testCanCalculateWithValidData(): void
    {
        $this->createStockRecord('SKU-007', '2024-01-01', 50, 10.00, 50);

        $this->assertTrue($this->calculator->canCalculate('SKU-007', 30));
    }

    public function testCannotCalculateWithZeroQuantity(): void
    {
        $this->assertFalse($this->calculator->canCalculate('SKU-008', 0));
        $this->assertFalse($this->calculator->canCalculate('SKU-008', -5));
    }

    public function testCannotCalculateWithNoStock(): void
    {
        $this->assertFalse($this->calculator->canCalculate('SKU-009', 10));
    }

    public function testRecalculateMultipleSkus(): void
    {
        $this->createStockRecord('SKU-010', '2024-01-01', 100, 10.00, 100);
        $this->createStockRecord('SKU-011', '2024-01-01', 50, 15.00, 50);

        $results = $this->calculator->recalculate(['SKU-010', 'SKU-011', 'SKU-012']);

        $this->assertCount(2, $results);
        $this->assertEquals('SKU-010', $results[0]->getSku());
        $this->assertEquals('SKU-011', $results[1]->getSku());
    }

    public function testCalculateWithComplexWeightedAverage(): void
    {
        $this->createStockRecord('SKU-013', '2024-01-01', 40, 10.00, 40);
        $this->createStockRecord('SKU-013', '2024-01-02', 30, 15.00, 30);
        $this->createStockRecord('SKU-013', '2024-01-03', 30, 20.00, 30);

        $result = $this->calculator->calculate('SKU-013', 70);

        $expectedWeightedAverage = (40 * 10.00 + 30 * 15.00 + 30 * 20.00) / 100;
        $this->assertEquals($expectedWeightedAverage, $result->getUnitCost());
        $this->assertEquals($expectedWeightedAverage * 70, $result->getTotalCost());
    }

    private function createStockRecord(
        string $sku,
        string $date,
        int $currentQuantity,
        float $unitCost,
        int $originalQuantity
    ): StockRecord {
        $record = new StockRecord();
        $record->setSku($sku);
        $record->setRecordDate(new \DateTimeImmutable($date));
        $record->setCurrentQuantity($currentQuantity);
        $record->setUnitCost($unitCost);
        $record->setOriginalQuantity($originalQuantity);

        self::getEntityManager()->persist($record);
        self::getEntityManager()->flush();

        return $record;
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service\Calculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\StockRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\Calculator\StandardCostCalculator;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;
use Tourze\StockCostBundle\Service\StandardCostServiceInterface;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * @internal
 */
#[CoversClass(StandardCostCalculator::class)]
#[RunTestsInSeparateProcesses]
final class StandardCostCalculatorTest extends AbstractIntegrationTestCase
{
    private StandardCostCalculator $calculator;

    private StandardCostServiceInterface $standardCostService;

    private StockRecordServiceInterface $stockRecordService;

    protected function onSetUp(): void
    {
        $this->calculator = self::getContainer()->get(StandardCostCalculator::class);
        $this->standardCostService = self::getContainer()->get(StandardCostServiceInterface::class);
        $this->stockRecordService = self::getContainer()->get(StockRecordServiceInterface::class);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostStrategyCalculatorInterface::class, $this->calculator);
    }

    public function testSupportsStandardCostStrategy(): void
    {
        $this->assertTrue($this->calculator->supports(CostStrategy::STANDARD_COST));
    }

    public function testDoesNotSupportOtherStrategies(): void
    {
        $this->assertFalse($this->calculator->supports(CostStrategy::FIFO));
        $this->assertFalse($this->calculator->supports(CostStrategy::LIFO));
        $this->assertFalse($this->calculator->supports(CostStrategy::WEIGHTED_AVERAGE));
    }

    public function testGetSupportedStrategy(): void
    {
        $this->assertEquals(CostStrategy::STANDARD_COST, $this->calculator->getSupportedStrategy());
    }

    public function testCalculateWithStandardCost(): void
    {
        $this->standardCostService->setStandardCost('SKU-001', 15.50);

        $result = $this->calculator->calculate('SKU-001', 100);

        $this->assertInstanceOf(CostCalculationResult::class, $result);
        $this->assertEquals('SKU-001', $result->getSku());
        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(15.50, $result->getUnitCost());
        $this->assertEquals(1550.00, $result->getTotalCost());
        $this->assertEquals(CostStrategy::STANDARD_COST, $result->getStrategy());
        $this->assertFalse($result->isPartialCalculation());
    }

    public function testCalculateWithZeroStandardCost(): void
    {
        $this->standardCostService->setStandardCost('SKU-002', 0.00);

        $result = $this->calculator->calculate('SKU-002', 50);

        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertFalse($result->isPartialCalculation());
    }

    public function testCalculateWithNullStandardCost(): void
    {
        $result = $this->calculator->calculate('SKU-003', 75);

        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCanCalculateWithStandardCost(): void
    {
        $this->standardCostService->setStandardCost('SKU-004', 10.00);

        $this->assertTrue($this->calculator->canCalculate('SKU-004', 30));
    }

    public function testCannotCalculateWithZeroQuantity(): void
    {
        $this->assertFalse($this->calculator->canCalculate('SKU-005', 0));
        $this->assertFalse($this->calculator->canCalculate('SKU-005', -5));
    }

    public function testCannotCalculateWithoutStandardCost(): void
    {
        $this->assertFalse($this->calculator->canCalculate('SKU-006', 10));
    }

    public function testRecalculateMultipleSkus(): void
    {
        $this->createStockRecord('SKU-007', '2024-01-01', 100, 8.00, 100);
        $this->createStockRecord('SKU-008', '2024-01-01', 50, 12.00, 50);

        $this->standardCostService->setStandardCost('SKU-007', 10.00);
        $this->standardCostService->setStandardCost('SKU-008', 15.00);

        $results = $this->calculator->recalculate(['SKU-007', 'SKU-008', 'SKU-009']);

        $this->assertCount(2, $results);
        $this->assertEquals('SKU-007', $results[0]->getSku());
        $this->assertEquals('SKU-008', $results[1]->getSku());
    }

    public function testCalculateWithVariance(): void
    {
        $this->standardCostService->setStandardCost('SKU-010', 10.00);

        $result = $this->calculator->calculate('SKU-010', 100);

        $details = $result->getCalculationDetails();
        $this->assertArrayHasKey('standardCost', $details);
        $this->assertEquals(10.00, $details['standardCost']);
        $this->assertArrayHasKey('calculationMethod', $details);
        $this->assertEquals('standard_cost', $details['calculationMethod']);
    }

    public function testCalculateWithHighPrecision(): void
    {
        $this->standardCostService->setStandardCost('SKU-011', 12.345);

        $result = $this->calculator->calculate('SKU-011', 100);

        $this->assertEquals(12.345, $result->getUnitCost());
        $this->assertEquals(1234.50, $result->getTotalCost());
    }

    public function testCalculateWithLargeQuantity(): void
    {
        $this->standardCostService->setStandardCost('SKU-012', 0.01);

        $result = $this->calculator->calculate('SKU-012', 100000);

        $this->assertEquals(0.01, $result->getUnitCost());
        $this->assertEquals(1000.00, $result->getTotalCost());
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

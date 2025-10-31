<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service\Calculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\StockRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\Calculator\FifoCostCalculator;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;
use Tourze\StockCostBundle\Service\StockRecordService;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * @internal
 */
#[CoversClass(FifoCostCalculator::class)]
#[RunTestsInSeparateProcesses]
class FifoCostCalculatorTest extends AbstractIntegrationTestCase
{
    private FifoCostCalculator $calculator;

    protected function onSetUp(): void
    {
        $this->calculator = self::getService(FifoCostCalculator::class);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostStrategyCalculatorInterface::class, $this->calculator);
    }

    public function testSupportsFifoStrategy(): void
    {
        $this->assertTrue($this->calculator->supports(CostStrategy::FIFO));
    }

    public function testDoesNotSupportOtherStrategies(): void
    {
        $this->assertFalse($this->calculator->supports(CostStrategy::LIFO));
        $this->assertFalse($this->calculator->supports(CostStrategy::WEIGHTED_AVERAGE));
        $this->assertFalse($this->calculator->supports(CostStrategy::STANDARD_COST));
    }

    public function testCalculateCostWithSufficientStock(): void
    {
        // 创建测试数据 - 按时间顺序的库存记录
        $record1 = $this->createStockRecord('2024-01-01', 100, 10.00, 100);
        $record2 = $this->createStockRecord('2024-01-02', 50, 12.00, 50);

        $record1->setSku('SKU-FIFO-001');
        $record2->setSku('SKU-FIFO-001');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $result = $this->calculator->calculate('SKU-FIFO-001', 120);

        $this->assertInstanceOf(CostCalculationResult::class, $result);
        $this->assertEquals('SKU-FIFO-001', $result->getSku());
        $this->assertEquals(120, $result->getQuantity());
        $this->assertEqualsWithDelta(10.33, $result->getUnitCost(), 0.01);
        $this->assertEquals(1240.00, $result->getTotalCost());
        $this->assertEquals(CostStrategy::FIFO, $result->getStrategy());
    }

    public function testCalculateCostWithExactStock(): void
    {
        $record1 = $this->createStockRecord('2024-01-01', 100, 15.00, 100);
        $record1->setSku('SKU-FIFO-002');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->flush();

        $result = $this->calculator->calculate('SKU-FIFO-002', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(15.00, $result->getUnitCost());
        $this->assertEquals(1500.00, $result->getTotalCost());
    }

    public function testCalculateCostUsesOldestStockFirst(): void
    {
        // 创建乱序的库存记录来测试FIFO排序
        $record1 = $this->createStockRecord('2024-01-03', 30, 20.00, 30);
        $record2 = $this->createStockRecord('2024-01-01', 50, 10.00, 50);
        $record3 = $this->createStockRecord('2024-01-02', 40, 15.00, 40);

        $record1->setSku('SKU-FIFO-003');
        $record2->setSku('SKU-FIFO-003');
        $record3->setSku('SKU-FIFO-003');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $result = $this->calculator->calculate('SKU-FIFO-003', 80);

        // FIFO应该使用最老的库存(2024-01-01)先
        $this->assertEqualsWithDelta(11.875, $result->getUnitCost(), 0.01);
        $this->assertEquals(950.00, $result->getTotalCost());
    }

    public function testCalculateCostWithInsufficientStock(): void
    {
        $record1 = $this->createStockRecord('2024-01-01', 30, 10.00, 30);
        $record1->setSku('SKU-FIFO-004');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->flush();

        $result = $this->calculator->calculate('SKU-FIFO-004', 50);

        $this->assertEquals(50, $result->getQuantity());
        $this->assertEquals(10.00, $result->getUnitCost());
        $this->assertEquals(500.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCalculateCostWithNoStock(): void
    {
        $result = $this->calculator->calculate('SKU-FIFO-005', 100);

        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCalculateCostIgnoresZeroQuantityRecords(): void
    {
        $record1 = $this->createStockRecord('2024-01-01', 0, 10.00, 100);
        $record2 = $this->createStockRecord('2024-01-02', 50, 15.00, 50);

        $record1->setSku('SKU-FIFO-006');
        $record2->setSku('SKU-FIFO-006');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $result = $this->calculator->calculate('SKU-FIFO-006', 30);

        $this->assertEquals(15.00, $result->getUnitCost());
        $this->assertEquals(450.00, $result->getTotalCost());
    }

    public function testCanCalculateReturnsTrueWithPositiveQuantityAndStockRecords(): void
    {
        $record1 = $this->createStockRecord('2024-01-01', 100, 10.00, 100);
        $record1->setSku('SKU-FIFO-CAN-001');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->flush();

        $result = $this->calculator->canCalculate('SKU-FIFO-CAN-001', 50);

        $this->assertTrue($result);
    }

    public function testCanCalculateReturnsFalseWithZeroQuantity(): void
    {
        $result = $this->calculator->canCalculate('SKU-FIFO-CAN-002', 0);

        $this->assertFalse($result);
    }

    public function testCanCalculateReturnsFalseWithNegativeQuantity(): void
    {
        $result = $this->calculator->canCalculate('SKU-FIFO-CAN-003', -10);

        $this->assertFalse($result);
    }

    public function testCanCalculateReturnsFalseWithNoStockRecords(): void
    {
        $result = $this->calculator->canCalculate('SKU-FIFO-CAN-004', 50);

        $this->assertFalse($result);
    }

    public function testRecalculateWithMultipleSkus(): void
    {
        // 创建多个SKU的库存记录
        $skus = ['SKU-FIFO-RE-001', 'SKU-FIFO-RE-002', 'SKU-FIFO-RE-003'];

        $record1 = $this->createStockRecord('2024-01-01', 100, 10.00, 100);
        $record1->setSku('SKU-FIFO-RE-001');

        $record2 = $this->createStockRecord('2024-01-01', 50, 15.00, 50);
        $record2->setSku('SKU-FIFO-RE-002');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $results = $this->calculator->recalculate($skus);

        $this->assertCount(2, $results); // Only 2 SKUs have stock
        $this->assertInstanceOf(CostCalculationResult::class, $results[0]);
        $this->assertInstanceOf(CostCalculationResult::class, $results[1]);

        /** @var array<string> $skuResults */
        $skuResults = array_map(fn (CostCalculationResult $r) => $r->getSku(), $results);
        $this->assertContains('SKU-FIFO-RE-001', $skuResults);
        $this->assertContains('SKU-FIFO-RE-002', $skuResults);
    }

    public function testRecalculateWithEmptySkuArray(): void
    {
        $results = $this->calculator->recalculate([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testRecalculateIgnoresSkusWithZeroStock(): void
    {
        // 创建库存为0的记录
        $record1 = $this->createStockRecord('2024-01-01', 0, 10.00, 0);
        $record1->setSku('SKU-FIFO-RE-ZERO');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->flush();

        $results = $this->calculator->recalculate(['SKU-FIFO-RE-ZERO']);

        $this->assertEmpty($results);
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

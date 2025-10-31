<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service\Calculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
class StandardCostCalculatorTest extends TestCase
{
    private StandardCostCalculator $calculator;

    private StandardCostServiceInterface $mockStandardCostService;

    private StockRecordServiceInterface $mockStockRecordService;

    protected function setUp(): void
    {
        $this->mockStandardCostService = $this->createMock(StandardCostServiceInterface::class);
        $this->mockStockRecordService = $this->createMock(StockRecordServiceInterface::class);
        $this->calculator = new StandardCostCalculator(
            $this->mockStandardCostService,
            $this->mockStockRecordService
        );
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
        $this->mockStandardCostService
            ->expects($this->once())
            ->method('getStandardCost')
            ->with('SKU-001')
            ->willReturn(15.50)
        ;

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
        $this->mockStandardCostService
            ->method('getStandardCost')
            ->with('SKU-002')
            ->willReturn(0.00)
        ;

        $result = $this->calculator->calculate('SKU-002', 50);

        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertFalse($result->isPartialCalculation());
    }

    public function testCalculateWithNullStandardCost(): void
    {
        $this->mockStandardCostService
            ->method('getStandardCost')
            ->with('SKU-003')
            ->willReturn(null)
        ;

        $result = $this->calculator->calculate('SKU-003', 75);

        $this->assertEquals(0.00, $result->getUnitCost());
        $this->assertEquals(0.00, $result->getTotalCost());
        $this->assertTrue($result->isPartialCalculation());
    }

    public function testCanCalculateWithStandardCost(): void
    {
        $this->mockStandardCostService
            ->method('hasStandardCost')
            ->with('SKU-001')
            ->willReturn(true)
        ;

        $this->assertTrue($this->calculator->canCalculate('SKU-001', 30));
    }

    public function testCannotCalculateWithZeroQuantity(): void
    {
        $this->assertFalse($this->calculator->canCalculate('SKU-001', 0));
        $this->assertFalse($this->calculator->canCalculate('SKU-001', -5));
    }

    public function testCannotCalculateWithoutStandardCost(): void
    {
        $this->mockStandardCostService
            ->method('hasStandardCost')
            ->with('SKU-002')
            ->willReturn(false)
        ;

        $this->assertFalse($this->calculator->canCalculate('SKU-002', 10));
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

        $this->mockStandardCostService
            ->method('getStandardCost')
            ->willReturnMap([
                ['SKU-001', 10.00],
                ['SKU-002', 15.00],
            ])
        ;

        $results = $this->calculator->recalculate(['SKU-001', 'SKU-002', 'SKU-003']);

        $this->assertCount(2, $results);
        $this->assertEquals('SKU-001', $results[0]->getSku());
        $this->assertEquals('SKU-002', $results[1]->getSku());
    }

    public function testCalculateWithVariance(): void
    {
        $this->mockStandardCostService
            ->method('getStandardCost')
            ->with('SKU-001')
            ->willReturn(10.00)
        ;

        $result = $this->calculator->calculate('SKU-001', 100);

        $details = $result->getCalculationDetails();
        $this->assertArrayHasKey('standardCost', $details);
        $this->assertEquals(10.00, $details['standardCost']);
        $this->assertArrayHasKey('calculationMethod', $details);
        $this->assertEquals('standard_cost', $details['calculationMethod']);
    }

    public function testCalculateWithHighPrecision(): void
    {
        $this->mockStandardCostService
            ->method('getStandardCost')
            ->with('SKU-004')
            ->willReturn(12.345)
        ;

        $result = $this->calculator->calculate('SKU-004', 100);

        $this->assertEquals(12.345, $result->getUnitCost());
        $this->assertEquals(1234.50, $result->getTotalCost());
    }

    public function testCalculateWithLargeQuantity(): void
    {
        $this->mockStandardCostService
            ->method('getStandardCost')
            ->with('SKU-005')
            ->willReturn(0.01)
        ;

        $result = $this->calculator->calculate('SKU-005', 100000);

        $this->assertEquals(0.01, $result->getUnitCost());
        $this->assertEquals(1000.00, $result->getTotalCost());
    }
}

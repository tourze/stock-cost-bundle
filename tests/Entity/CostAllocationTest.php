<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Service\Calculator\ActivityAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\CostAllocationCalculator;
use Tourze\StockCostBundle\Service\Calculator\QuantityAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\RatioAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\ValueAllocationStrategy;
use Tourze\StockCostBundle\Validator\CostAllocationValidator;

/**
 * @internal
 */
#[CoversClass(CostAllocation::class)]
class CostAllocationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CostAllocation();
    }

    /**
     * @return array<array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            ['allocationName', 'Test Allocation'],
            ['sourceType', CostType::INDIRECT],
            ['totalAmount', 10000.00],
            ['allocationMethod', AllocationMethod::RATIO],
            ['allocationDate', new \DateTimeImmutable('2024-01-01')],
            ['period', new CostPeriod()],
        ];
    }

    public function testCostAllocationCanBeInstantiated(): void
    {
        $allocation = new CostAllocation();

        $this->assertInstanceOf(CostAllocation::class, $allocation);
    }

    public function testCostAllocationHasRequiredProperties(): void
    {
        $allocation = new CostAllocation();
        $period = new CostPeriod();

        $allocation->setAllocationName('Office Rent Allocation');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(10000.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setAllocationDate(new \DateTimeImmutable('2024-01-01'));
        $allocation->setPeriod($period);

        $this->assertEquals('Office Rent Allocation', $allocation->getAllocationName());
        $this->assertEquals(CostType::INDIRECT, $allocation->getSourceType());
        $this->assertEquals(10000.00, $allocation->getTotalAmount());
        $this->assertEquals(AllocationMethod::RATIO, $allocation->getAllocationMethod());
        $this->assertEquals('2024-01-01', $allocation->getAllocationDate()->format('Y-m-d'));
        $this->assertSame($period, $allocation->getPeriod());
    }

    public function testCostAllocationHasTimestamps(): void
    {
        $allocation = new CostAllocation();

        $this->assertInstanceOf(\DateTimeImmutable::class, $allocation->getCreatedAt());
    }

    public function testCostAllocationTargetsAsJson(): void
    {
        $allocation = new CostAllocation();
        $targets = [
            ['sku_id' => 'SKU-001', 'ratio' => 0.3],
            ['sku_id' => 'SKU-002', 'ratio' => 0.7],
        ];

        $allocation->setTargets($targets);

        $this->assertEquals($targets, $allocation->getTargets());
        $this->assertIsArray($allocation->getTargets());
    }

    public function testCostAllocationSupportsMultipleAllocationMethods(): void
    {
        $allocation = new CostAllocation();

        // 测试比例分摊
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $this->assertEquals(AllocationMethod::RATIO, $allocation->getAllocationMethod());

        // 测试数量分摊
        $allocation->setAllocationMethod(AllocationMethod::QUANTITY);
        $this->assertEquals(AllocationMethod::QUANTITY, $allocation->getAllocationMethod());

        // 测试价值分摊
        $allocation->setAllocationMethod(AllocationMethod::VALUE);
        $this->assertEquals(AllocationMethod::VALUE, $allocation->getAllocationMethod());

        // 测试作业分摊
        $allocation->setAllocationMethod(AllocationMethod::ACTIVITY);
        $this->assertEquals(AllocationMethod::ACTIVITY, $allocation->getAllocationMethod());
    }

    public function testCostAllocationValidatesPositiveAmount(): void
    {
        $allocation = new CostAllocation();

        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Total amount must be positive');

        $allocation->setTotalAmount(-100.00);
    }

    public function testCostAllocationCalculatesIndividualAllocations(): void
    {
        $allocation = new CostAllocation();
        $allocation->setTotalAmount(1000.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'ratio' => 0.3],
            ['sku_id' => 'SKU-002', 'ratio' => 0.7],
        ]);

        // 使用新的 CostAllocationCalculator 而不是废弃方法
        $validator = new CostAllocationValidator();
        $calculator = new CostAllocationCalculator(
            $validator,
            new RatioAllocationStrategy($validator),
            new QuantityAllocationStrategy($validator),
            new ValueAllocationStrategy($validator),
            new ActivityAllocationStrategy($validator)
        );

        $allocations = $calculator->calculate($allocation);

        $this->assertEquals(300.00, $allocations['SKU-001']);
        $this->assertEquals(700.00, $allocations['SKU-002']);
    }

    public function testCostAllocationSupportsQuantityBasedAllocation(): void
    {
        $allocation = new CostAllocation();
        $allocation->setTotalAmount(1000.00);
        $allocation->setAllocationMethod(AllocationMethod::QUANTITY);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'quantity' => 100],
            ['sku_id' => 'SKU-002', 'quantity' => 200],
        ]);

        // 使用新的 CostAllocationCalculator 而不是废弃方法
        $validator = new CostAllocationValidator();
        $calculator = new CostAllocationCalculator(
            $validator,
            new RatioAllocationStrategy($validator),
            new QuantityAllocationStrategy($validator),
            new ValueAllocationStrategy($validator),
            new ActivityAllocationStrategy($validator)
        );

        $allocations = $calculator->calculate($allocation);

        // 按数量比例: 100/(100+200) = 1/3, 200/(100+200) = 2/3
        $this->assertEquals(333.33, round($allocations['SKU-001'], 2));
        $this->assertEquals(666.67, round($allocations['SKU-002'], 2));
    }

    public function testCostAllocationStringable(): void
    {
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Test Allocation');
        $allocation->setTotalAmount(1000.00);
        $allocation->setSourceType(CostType::INDIRECT);

        $string = $allocation->__toString();

        $this->assertStringContainsString('Test Allocation', $string);
        $this->assertStringContainsString('1000.00', $string);
        $this->assertStringContainsString('INDIRECT', $string);
    }

    public function testCostAllocationEmptyTargetsThrowsException(): void
    {
        $allocation = new CostAllocation();
        $allocation->setTotalAmount(1000.00);
        $allocation->setTargets([]);

        // 使用新的 CostAllocationCalculator 而不是废弃方法
        $validator = new CostAllocationValidator();
        $calculator = new CostAllocationCalculator(
            $validator,
            new RatioAllocationStrategy($validator),
            new QuantityAllocationStrategy($validator),
            new ValueAllocationStrategy($validator),
            new ActivityAllocationStrategy($validator)
        );

        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Allocation targets cannot be empty');

        $calculator->calculate($allocation);
    }
}

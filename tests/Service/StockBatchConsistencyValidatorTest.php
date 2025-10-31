<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\ProductServiceContracts\SKU;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Service\StockBatchConsistencyValidator;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * @internal
 */
#[CoversClass(StockBatchConsistencyValidator::class)]
class StockBatchConsistencyValidatorTest extends TestCase
{
    private StockBatchConsistencyValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StockBatchConsistencyValidator();
    }

    public function testValidateCostRecordWithBatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCostRecordDetectsSkuMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-002'); // 不匹配
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('SKU mismatch between CostRecord and StockBatch', $result['errors']);
    }

    public function testValidateCostRecordDetectsBatchNoMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B002'); // 不匹配
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('Batch number mismatch between CostRecord and StockBatch', $result['errors']);
    }

    public function testValidateCostRecordDetectsUnitCostMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(12.00); // 不匹配
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(600.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('Unit cost mismatch between CostRecord and StockBatch', $result['errors']);
    }

    public function testValidateCostRecordDetectsExcessiveQuantity(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(150); // 超过批次数量
        $costRecord->setTotalCost(1575.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('CostRecord quantity exceeds StockBatch available quantity', $result['errors']);
    }

    public function testValidateCostRecordDetectsCostTotalMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(500.00); // 错误的总计
        $costRecord->setStockBatch($stockBatch);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertStringContainsString('Total cost calculation mismatch', $result['errors'][0]);
    }

    public function testValidateCostRecordDetectsNegativeQuantity(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setStockBatch($stockBatch);

        // 使用反射设置负数量，绕过setter验证
        $reflection = new \ReflectionClass($costRecord);
        $quantityProperty = $reflection->getProperty('quantity');
        $quantityProperty->setAccessible(true);
        $quantityProperty->setValue($costRecord, -50);

        // 同时设置负的总成本以保持一致性
        $totalCostProperty = $reflection->getProperty('totalCost');
        $totalCostProperty->setAccessible(true);
        $totalCostProperty->setValue($costRecord, -525.00);

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('Quantity must be positive', $result['errors']);
    }

    public function testValidateCostRecordWithoutStockBatch(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        // 不设置 StockBatch

        $result = $this->validator->validateCostRecordWithBatch($costRecord);

        // 没有 StockBatch 时只验证成本计算和数量约束
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    private function createStockBatch(string $batchNo, string $skuId, float $unitCost, int $quantity): StockBatch
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn($skuId);

        $batch = new StockBatch();
        $batch->setBatchNo($batchNo);
        $batch->setSku($sku);
        $batch->setUnitCost($unitCost);
        $batch->setQuantity($quantity);

        return $batch;
    }
}

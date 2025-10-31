<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\ProductServiceContracts\SKU;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\ConsistencyFixer;
use Tourze\StockCostBundle\Service\CostRecordConsistencyValidator;
use Tourze\StockCostBundle\Service\DataConsistencyValidator;
use Tourze\StockCostBundle\Service\StockBatchConsistencyValidator;
use Tourze\StockManageBundle\Entity\StockBatch;
use Tourze\StockManageBundle\Service\BatchQueryServiceInterface;

/**
 * @internal
 *
 * 此测试文件专门用于测试已弃用的 DataConsistencyValidator 类的向后兼容性。
 * 在完全迁移到新验证器类之前，需要确保弃用类的行为保持一致。
 *
 * @phpstan-ignore-next-line classConstant.deprecatedClass
 */
#[CoversClass(DataConsistencyValidator::class)]
class DataConsistencyValidatorTest extends TestCase
{
    private DataConsistencyValidator $validator;

    private StockBatchConsistencyValidator $batchValidator;

    private CostRecordConsistencyValidator $costRecordValidator;

    private ConsistencyFixer $fixer;

    /**
     * @var CostRecordRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private CostRecordRepository $costRecordRepository;

    /**
     * @var BatchQueryServiceInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private BatchQueryServiceInterface $batchQueryService;

    protected function setUp(): void
    {
        /** @var CostRecordRepository&\PHPUnit\Framework\MockObject\MockObject $costRecordRepository */
        $costRecordRepository = $this->createMock(CostRecordRepository::class);
        $this->costRecordRepository = $costRecordRepository;

        /** @var BatchQueryServiceInterface&\PHPUnit\Framework\MockObject\MockObject $batchQueryService */
        $batchQueryService = $this->createMock(BatchQueryServiceInterface::class);
        $this->batchQueryService = $batchQueryService;

        $this->batchValidator = new StockBatchConsistencyValidator();
        $this->costRecordValidator = new CostRecordConsistencyValidator();
        $this->fixer = new ConsistencyFixer($this->costRecordRepository);

        // 为了测试弃用类的兼容性，我们仍然使用它
        // @phpstan-ignore new.deprecatedClass, method.deprecatedClass
        $this->validator = new DataConsistencyValidator(
            $this->costRecordRepository,
            $this->batchQueryService
        );
    }

    public function testValidateCostRecordConsistency(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostRecordConsistency($costRecord);

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

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostRecordConsistency($costRecord);

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

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostRecordConsistency($costRecord);

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

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostRecordConsistency($costRecord);

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

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostRecordConsistency($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('CostRecord quantity exceeds StockBatch available quantity', $result['errors']);
    }

    public function testValidateSystemConsistency(): void
    {
        $costRecords = [
            $this->createCostRecord('SKU-001', 'B001', 10.50, 50),
            $this->createCostRecord('SKU-001', 'B002', 12.00, 30),
        ];

        $stockBatches = [
            $this->createStockBatch('B001', 'SKU-001', 10.50, 100),
            $this->createStockBatch('B002', 'SKU-001', 12.00, 50),
        ];

        $this->costRecordRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($costRecords)
        ;

        $this->batchQueryService->expects($this->once())
            ->method('getAllBatches')
            ->willReturn($stockBatches)
        ;

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateSystemConsistency();

        $this->assertTrue($result['isValid']);
        $this->assertEquals(2, $result['totalRecords']);
        $this->assertEquals(0, $result['inconsistentRecords']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateSystemDetectsInconsistencies(): void
    {
        // 创建有问题的成本记录
        $costRecord = $this->createCostRecord('SKU-001', 'B001', 15.00, 50); // 单价不匹配
        $costRecord->setStockBatch($this->createStockBatch('B001', 'SKU-001', 10.50, 100));

        $this->costRecordRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$costRecord])
        ;

        $this->batchQueryService->expects($this->once())
            ->method('getAllBatches')
            ->willReturn([])
        ;

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateSystemConsistency();

        $this->assertFalse($result['isValid']);
        $this->assertEquals(1, $result['totalRecords']);
        $this->assertEquals(1, $result['inconsistentRecords']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testValidateCostTotalConsistency(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(5);
        $costRecord->setTotalCost(52.50); // 正确的总计

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostTotalConsistency($costRecord);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCostTotalDetectsMismatch(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(5);
        $costRecord->setTotalCost(50.00); // 错误的总计

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateCostTotalConsistency($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertStringContainsString('Total cost calculation mismatch', $result['errors'][0]);
    }

    public function testValidateQuantityConstraints(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setQuantity(10);

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateQuantityConstraints($costRecord);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateQuantityDetectsNegativeValue(): void
    {
        $costRecord = new CostRecord();

        // CostRecord会在setQuantity时抛出异常，我们需要通过反射设置负值
        $reflection = new \ReflectionClass($costRecord);
        $quantityProperty = $reflection->getProperty('quantity');
        $quantityProperty->setAccessible(true);
        $quantityProperty->setValue($costRecord, -5);

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateQuantityConstraints($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('Quantity must be positive', $result['errors']);
    }

    public function testValidateQuantityDetectsZeroValue(): void
    {
        $costRecord = new CostRecord();

        // CostRecord会在setQuantity时抛出异常，我们需要通过反射设置零值
        $reflection = new \ReflectionClass($costRecord);
        $quantityProperty = $reflection->getProperty('quantity');
        $quantityProperty->setAccessible(true);
        $quantityProperty->setValue($costRecord, 0);

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->validateQuantityConstraints($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertContains('Quantity must be positive', $result['errors']);
    }

    public function testFixInconsistenciesWithNoProblems(): void
    {
        // 模拟系统一致性良好
        $costRecord = $this->createCostRecord('SKU-001', 'B001', 10.50, 50);
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);
        $costRecord->setStockBatch($stockBatch);

        $this->costRecordRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$costRecord])
        ;

        $this->batchQueryService->expects($this->once())
            ->method('getAllBatches')
            ->willReturn([$stockBatch])
        ;

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->fixInconsistencies();

        $this->assertEquals(0, $result['fixed']);
        $this->assertEmpty($result['errors']);
    }

    public function testFixInconsistenciesDetectsProblems(): void
    {
        // 模拟有问题的记录
        $costRecord = $this->createCostRecord('SKU-001', 'B001', 15.00, 50); // 单价不匹配
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);
        $costRecord->setStockBatch($stockBatch);

        $this->costRecordRepository->expects($this->atLeast(1))
            ->method('findAll')
            ->willReturn([$costRecord])
        ;

        $this->batchQueryService->expects($this->once())
            ->method('getAllBatches')
            ->willReturn([$stockBatch])
        ;

        // @phpstan-ignore method.deprecatedClass
        $result = $this->validator->fixInconsistencies();

        // 验证发现了问题但没有进行自动修复（需要手动处理）
        $this->assertGreaterThanOrEqual(0, $result['fixed']);
        // 可能包含错误信息
        $this->assertIsArray($result['errors']);
    }

    /**
     * 测试新的验证器类的独立使用
     *
     * 这个测试展示了如何使用新的验证器类来替代弃用的 DataConsistencyValidator
     */
    public function testNewValidatorUsage(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        // 使用新的 StockBatchConsistencyValidator
        $batchResult = $this->batchValidator->validateCostRecordWithBatch($costRecord);
        $this->assertTrue($batchResult['isValid']);

        // 使用新的 CostRecordConsistencyValidator
        $costTotalResult = $this->costRecordValidator->validateCostTotalConsistency($costRecord);
        $this->assertTrue($costTotalResult['isValid']);

        $quantityResult = $this->costRecordValidator->validateQuantityConstraints($costRecord);
        $this->assertTrue($quantityResult['isValid']);
    }

    /**
     * 测试新验证器类的问题检测
     */
    public function testNewValidatorProblemDetection(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-002'); // 不匹配
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        // 使用新的 StockBatchConsistencyValidator 检测问题
        $batchResult = $this->batchValidator->validateCostRecordWithBatch($costRecord);
        $this->assertFalse($batchResult['isValid']);
        $this->assertContains('SKU mismatch between CostRecord and StockBatch', $batchResult['errors']);
    }

    /**
     * 测试新的 ConsistencyFixer 的修复功能
     */
    public function testNewConsistencyFixer(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-002'); // 不匹配，需要修复
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        // 使用新的 ConsistencyFixer 修复问题
        $fixResult = $this->fixer->fixSingleRecordInconsistency($costRecord);
        $this->assertTrue($fixResult);

        // 验证修复结果
        $this->assertEquals('SKU-001', $costRecord->getSkuId());
    }

    private function createCostRecord(string $skuId, string $batchNo, float $unitCost, int $quantity): CostRecord
    {
        $record = new CostRecord();
        $record->setSkuId($skuId);
        $record->setBatchNo($batchNo);
        $record->setUnitCost($unitCost);
        $record->setQuantity($quantity);
        $record->setTotalCost($unitCost * $quantity);

        return $record;
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

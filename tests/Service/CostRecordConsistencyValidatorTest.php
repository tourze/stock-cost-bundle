<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Service\CostRecordConsistencyValidator;

/**
 * @internal
 */
#[CoversClass(CostRecordConsistencyValidator::class)]
class CostRecordConsistencyValidatorTest extends TestCase
{
    private CostRecordConsistencyValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CostRecordConsistencyValidator();
    }

    public function testValidateCostTotalConsistency(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);

        $result = $this->validator->validateCostTotalConsistency($costRecord);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateCostTotalConsistencyMismatch(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(520.00); // 不匹配的总成本

        $result = $this->validator->validateCostTotalConsistency($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Total cost calculation mismatch', $result['errors'][0]);
    }

    public function testValidateQuantityConstraints(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setQuantity(50);

        $result = $this->validator->validateQuantityConstraints($costRecord);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateQuantityConstraintsZero(): void
    {
        // 创建一个数量为0的记录，使用反射来绕过setter验证
        $costRecord = new CostRecord();
        $reflection = new \ReflectionClass($costRecord);
        $property = $reflection->getProperty('quantity');
        $property->setAccessible(true);
        $property->setValue($costRecord, 0);

        $result = $this->validator->validateQuantityConstraints($costRecord);

        $this->assertFalse($result['isValid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Quantity must be positive', $result['errors'][0]);
    }

    public function testRecalculateTotalCost(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(0.00); // 初始值

        $this->validator->recalculateTotalCost($costRecord);

        $this->assertEquals(525.00, $costRecord->getTotalCost());
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;
use Tourze\StockCostBundle\Validator\CostRecordValidator;

/**
 * @internal
 */
#[CoversClass(CostRecordValidator::class)]
class CostRecordValidatorTest extends TestCase
{
    public function testValidateSkuId(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('SKU ID cannot be empty');

        CostRecordValidator::validateSkuId('');
    }

    public function testValidateValidSkuId(): void
    {
        $this->expectNotToPerformAssertions();
        CostRecordValidator::validateSkuId('SKU-001');
    }

    public function testValidateAndConvertUnitCost(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Unit cost cannot be negative');

        CostRecordValidator::validateAndConvertUnitCost(-10.0);
    }

    public function testValidateAndConvertValidUnitCost(): void
    {
        $result = CostRecordValidator::validateAndConvertUnitCost(15.50);
        $this->assertEquals(15.50, $result);

        $result = CostRecordValidator::validateAndConvertUnitCost('15.50');
        $this->assertEquals(15.50, $result);

        $result = CostRecordValidator::validateAndConvertUnitCost(null);
        $this->assertNull($result);
    }

    public function testValidateAndConvertQuantity(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        CostRecordValidator::validateAndConvertQuantity(-10);
    }

    public function testValidateAndConvertValidQuantity(): void
    {
        $result = CostRecordValidator::validateAndConvertQuantity(100);
        $this->assertEquals(100, $result);

        $result = CostRecordValidator::validateAndConvertQuantity('100');
        $this->assertEquals(100, $result);

        $result = CostRecordValidator::validateAndConvertQuantity(null);
        $this->assertNull($result);
    }

    public function testValidateAndConvertTotalCost(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Total cost cannot be negative');

        CostRecordValidator::validateAndConvertTotalCost(-10.0);
    }

    public function testValidateAndConvertValidTotalCost(): void
    {
        $result = CostRecordValidator::validateAndConvertTotalCost(1550.00);
        $this->assertEquals(1550.00, $result);

        $result = CostRecordValidator::validateAndConvertTotalCost('1550.00');
        $this->assertEquals(1550.00, $result);

        $result = CostRecordValidator::validateAndConvertTotalCost(null);
        $this->assertNull($result);
    }

    public function testValidateCostStrategy(): void
    {
        $this->expectNotToPerformAssertions();
        CostRecordValidator::validateCostStrategy(CostStrategy::FIFO);
    }

    public function testValidateCostType(): void
    {
        $this->expectNotToPerformAssertions();
        CostRecordValidator::validateCostType(CostType::DIRECT);
    }

    public function testValidateAndConvertMetadata(): void
    {
        $arrayData = ['source' => 'import', 'date' => '2024-01-01'];
        $result = CostRecordValidator::validateAndConvertMetadata($arrayData);
        $this->assertEquals($arrayData, $result);

        $jsonString = '{"source": "import", "date": "2024-01-01"}';
        $result = CostRecordValidator::validateAndConvertMetadata($jsonString);
        $this->assertEquals($arrayData, $result);

        $result = CostRecordValidator::validateAndConvertMetadata(null);
        $this->assertNull($result);

        $result = CostRecordValidator::validateAndConvertMetadata('');
        $this->assertNull($result);
    }

    public function testValidateCostConsistency(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessageMatches('/Cost calculation inconsistent/');

        CostRecordValidator::validateCostConsistency(15.50, 100, 1000.00); // 应该是 1550.00
    }

    public function testValidateValidCostConsistency(): void
    {
        $this->expectNotToPerformAssertions();
        CostRecordValidator::validateCostConsistency(15.50, 100, 1550.00);
    }
}

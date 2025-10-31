<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Exception\CostCalculationException;

/**
 * @internal
 */
#[CoversClass(CostAllocationException::class)]
class CostAllocationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsCorrectBaseClass(): void
    {
        $exception = new CostAllocationException('测试异常');

        $this->assertInstanceOf(CostCalculationException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testInvalidTotalAmountException(): void
    {
        $amount = -100.50;
        $exception = CostAllocationException::invalidTotalAmount($amount);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Total amount must be positive, got -100.50', $exception->getMessage());
    }

    public function testInvalidTotalAmountWithZero(): void
    {
        $amount = 0.00;
        $exception = CostAllocationException::invalidTotalAmount($amount);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Total amount must be positive, got 0.00', $exception->getMessage());
    }

    public function testInvalidTotalAmountWithSmallNegativeAmount(): void
    {
        $amount = -0.01;
        $exception = CostAllocationException::invalidTotalAmount($amount);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Total amount must be positive, got -0.01', $exception->getMessage());
    }

    public function testUnsupportedAllocationMethodException(): void
    {
        $method = 'invalid_method';
        $exception = CostAllocationException::unsupportedAllocationMethod($method);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Unsupported allocation method: invalid_method', $exception->getMessage());
    }

    public function testUnsupportedAllocationMethodWithEmptyString(): void
    {
        $method = '';
        $exception = CostAllocationException::unsupportedAllocationMethod($method);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Unsupported allocation method: ', $exception->getMessage());
    }

    public function testUnsupportedAllocationMethodWithSpecialCharacters(): void
    {
        $method = 'invalid@method#123';
        $exception = CostAllocationException::unsupportedAllocationMethod($method);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Unsupported allocation method: invalid@method#123', $exception->getMessage());
    }

    public function testEmptyTargetsException(): void
    {
        $exception = CostAllocationException::emptyTargets();

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Allocation targets cannot be empty', $exception->getMessage());
    }

    public function testInvalidRatioException(): void
    {
        $ratio = 1.5;
        $exception = CostAllocationException::invalidRatio($ratio);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Ratio must be between 0 and 1, got 1.5000', $exception->getMessage());
    }

    public function testInvalidRatioWithNegativeValue(): void
    {
        $ratio = -0.1;
        $exception = CostAllocationException::invalidRatio($ratio);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Ratio must be between 0 and 1, got -0.1000', $exception->getMessage());
    }

    public function testInvalidRatioWithVerySmallValue(): void
    {
        $ratio = -0.0001;
        $exception = CostAllocationException::invalidRatio($ratio);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Ratio must be between 0 and 1, got -0.0001', $exception->getMessage());
    }

    public function testInvalidRatioWithLargeValue(): void
    {
        $ratio = 2.5;
        $exception = CostAllocationException::invalidRatio($ratio);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Ratio must be between 0 and 1, got 2.5000', $exception->getMessage());
    }

    public function testInvalidQuantityException(): void
    {
        $quantity = -10;
        $exception = CostAllocationException::invalidQuantity($quantity);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Quantity must be positive, got -10', $exception->getMessage());
    }

    public function testInvalidQuantityWithZero(): void
    {
        $quantity = 0;
        $exception = CostAllocationException::invalidQuantity($quantity);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Quantity must be positive, got 0', $exception->getMessage());
    }

    public function testInvalidQuantityWithLargeNegativeValue(): void
    {
        $quantity = -999999;
        $exception = CostAllocationException::invalidQuantity($quantity);

        $this->assertInstanceOf(CostAllocationException::class, $exception);
        $this->assertEquals('Quantity must be positive, got -999999', $exception->getMessage());
    }

    public function testAllStaticMethodsReturnSameExceptionType(): void
    {
        $exceptions = [
            CostAllocationException::invalidTotalAmount(-100.0),
            CostAllocationException::unsupportedAllocationMethod('invalid'),
            CostAllocationException::emptyTargets(),
            CostAllocationException::invalidRatio(1.5),
            CostAllocationException::invalidQuantity(-10),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(CostAllocationException::class, $exception);
            $this->assertInstanceOf(CostCalculationException::class, $exception);
        }
    }

    public function testExceptionMessagesAreNotEmpty(): void
    {
        $exceptions = [
            CostAllocationException::invalidTotalAmount(-100.0),
            CostAllocationException::unsupportedAllocationMethod('invalid'),
            CostAllocationException::emptyTargets(),
            CostAllocationException::invalidRatio(1.5),
            CostAllocationException::invalidQuantity(-10),
        ];

        foreach ($exceptions as $exception) {
            $this->assertNotEmpty($exception->getMessage());
            $this->assertIsString($exception->getMessage());
        }
    }

    public function testExceptionCanBeCaught(): void
    {
        $this->expectException(CostAllocationException::class);
        throw CostAllocationException::invalidTotalAmount(-100.0);
    }

    public function testExceptionCanBeCaughtAsBaseClass(): void
    {
        $this->expectException(CostCalculationException::class);
        throw CostAllocationException::invalidTotalAmount(-100.0);
    }

    public function testExceptionHasCorrectCode(): void
    {
        $exception = CostAllocationException::invalidTotalAmount(-100.0);

        // 检查异常码是默认的0（除非在构造函数中设置了特定值）
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionHasNoPreviousException(): void
    {
        $exception = CostAllocationException::invalidTotalAmount(-100.0);

        $this->assertNull($exception->getPrevious());
    }

    public function testFloatFormattingPrecision(): void
    {
        // 测试浮点数格式化的精度
        $exception = CostAllocationException::invalidTotalAmount(-123.456789);

        $this->assertStringContainsString('-123.46', $exception->getMessage());
    }

    public function testVeryLargeNumbers(): void
    {
        $largeAmount = -999999999.99;
        $exception = CostAllocationException::invalidTotalAmount($largeAmount);

        $this->assertStringContainsString('-999999999.99', $exception->getMessage());
    }
}

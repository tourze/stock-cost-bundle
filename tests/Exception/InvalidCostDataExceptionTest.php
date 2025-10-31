<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;

/**
 * @internal
 */
#[CoversClass(InvalidCostDataException::class)]
class InvalidCostDataExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return InvalidCostDataException::class;
    }

    public function testForEmptySku(): void
    {
        $exception = InvalidCostDataException::forEmptySku();

        $this->assertInstanceOf(InvalidCostDataException::class, $exception);
        $this->assertEquals('SKU cannot be empty', $exception->getMessage());
    }

    public function testForNegativeQuantity(): void
    {
        $exception = InvalidCostDataException::forNegativeQuantity(-5);

        $this->assertInstanceOf(InvalidCostDataException::class, $exception);
        $this->assertEquals('Quantity must be positive, got: -5', $exception->getMessage());
    }

    public function testForInvalidPeriod(): void
    {
        $exception = InvalidCostDataException::forInvalidPeriod();

        $this->assertInstanceOf(InvalidCostDataException::class, $exception);
        $this->assertEquals('Period start must be before period end', $exception->getMessage());
    }

    public function testForNegativeCost(): void
    {
        $exception = InvalidCostDataException::forNegativeCost(-1500.50);

        $this->assertInstanceOf(InvalidCostDataException::class, $exception);
        $this->assertEquals('Total cost cannot be negative, got: -1500.5', $exception->getMessage());
    }
}

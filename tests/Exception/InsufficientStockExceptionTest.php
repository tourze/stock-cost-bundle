<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\StockCostBundle\Exception\CostCalculationException;
use Tourze\StockCostBundle\Exception\InsufficientStockException;

/**
 * @internal
 */
#[CoversClass(InsufficientStockException::class)]
class InsufficientStockExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsBaseException(): void
    {
        $exception = new InsufficientStockException('Not enough stock');

        $this->assertInstanceOf(CostCalculationException::class, $exception);
        $this->assertEquals('Not enough stock', $exception->getMessage());
    }

    public function testExceptionHasDefaultMessage(): void
    {
        $exception = new InsufficientStockException();

        $this->assertEquals('Insufficient stock for cost calculation', $exception->getMessage());
    }

    public function testExceptionCanProvideStockInfo(): void
    {
        $exception = InsufficientStockException::forQuantity('SKU123', 100, 50);

        $expected = 'Insufficient stock for SKU123: requested 100, available 50';
        $this->assertEquals($expected, $exception->getMessage());
    }
}

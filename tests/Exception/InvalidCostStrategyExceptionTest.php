<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\StockCostBundle\Exception\CostCalculationException;
use Tourze\StockCostBundle\Exception\InvalidCostStrategyException;

/**
 * @internal
 */
#[CoversClass(InvalidCostStrategyException::class)]
class InvalidCostStrategyExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsBaseException(): void
    {
        $exception = new InvalidCostStrategyException('Invalid strategy');

        $this->assertInstanceOf(CostCalculationException::class, $exception);
        $this->assertEquals('Invalid strategy', $exception->getMessage());
    }

    public function testExceptionHasDefaultMessage(): void
    {
        $exception = new InvalidCostStrategyException();

        $this->assertEquals('Invalid cost calculation strategy', $exception->getMessage());
    }

    public function testExceptionCanProvideStrategyInfo(): void
    {
        $exception = InvalidCostStrategyException::forStrategy('UNKNOWN_STRATEGY');

        $expected = 'Invalid cost calculation strategy: UNKNOWN_STRATEGY';
        $this->assertEquals($expected, $exception->getMessage());
    }

    public function testExceptionCanProvideAllowedStrategies(): void
    {
        $allowedStrategies = ['FIFO', 'LIFO', 'WEIGHTED_AVERAGE'];
        $exception = InvalidCostStrategyException::forStrategyWithAllowed('UNKNOWN', $allowedStrategies);

        $expected = 'Invalid cost calculation strategy: UNKNOWN. Allowed strategies: FIFO, LIFO, WEIGHTED_AVERAGE';
        $this->assertEquals($expected, $exception->getMessage());
    }
}

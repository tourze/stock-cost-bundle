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
#[CoversClass(CostCalculationException::class)]
class CostCalculationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeInstantiated(): void
    {
        $exception = new InvalidCostStrategyException('Test message');

        $this->assertInstanceOf(CostCalculationException::class, $exception);
        $this->assertInstanceOf(InvalidCostStrategyException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new InvalidCostStrategyException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(CostCalculationException::class, $exception);
    }

    public function testExceptionSupportsCode(): void
    {
        $exception = new InvalidCostStrategyException('Test message', 500);

        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionSupportsPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidCostStrategyException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidCostStrategyExceptionForStrategy(): void
    {
        $exception = InvalidCostStrategyException::forStrategy('INVALID_STRATEGY');

        $this->assertInstanceOf(InvalidCostStrategyException::class, $exception);
        $this->assertStringContainsString('INVALID_STRATEGY', $exception->getMessage());
    }

    public function testInvalidCostStrategyExceptionForStrategyWithAllowed(): void
    {
        $allowedStrategies = ['FIFO', 'LIFO', 'WEIGHTED_AVERAGE'];
        $exception = InvalidCostStrategyException::forStrategyWithAllowed('INVALID', $allowedStrategies);

        $this->assertInstanceOf(InvalidCostStrategyException::class, $exception);
        $this->assertStringContainsString('INVALID', $exception->getMessage());
        $this->assertStringContainsString('FIFO', $exception->getMessage());
        $this->assertStringContainsString('LIFO', $exception->getMessage());
        $this->assertStringContainsString('WEIGHTED_AVERAGE', $exception->getMessage());
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Exception\CostCalculationException;
use Tourze\StockCostBundle\Exception\CostPeriodException;

/**
 * @internal
 */
#[CoversClass(CostPeriodException::class)]
class CostPeriodExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsCorrectBaseClass(): void
    {
        $exception = new CostPeriodException('测试异常');

        $this->assertInstanceOf(CostCalculationException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testCannotClosePeriodWithOpenStatus(): void
    {
        $status = CostPeriodStatus::OPEN;
        $exception = CostPeriodException::cannotClosePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only OPEN periods can be closed, current status: OPEN', $exception->getMessage());
    }

    public function testCannotClosePeriodWithClosedStatus(): void
    {
        $status = CostPeriodStatus::CLOSED;
        $exception = CostPeriodException::cannotClosePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only OPEN periods can be closed, current status: CLOSED', $exception->getMessage());
    }

    public function testCannotClosePeriodWithFrozenStatus(): void
    {
        $status = CostPeriodStatus::FROZEN;
        $exception = CostPeriodException::cannotClosePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only OPEN periods can be closed, current status: FROZEN', $exception->getMessage());
    }

    public function testCannotFreezePeriodWithOpenStatus(): void
    {
        $status = CostPeriodStatus::OPEN;
        $exception = CostPeriodException::cannotFreezePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only CLOSED periods can be frozen, current status: OPEN', $exception->getMessage());
    }

    public function testCannotFreezePeriodWithClosedStatus(): void
    {
        $status = CostPeriodStatus::CLOSED;
        $exception = CostPeriodException::cannotFreezePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only CLOSED periods can be frozen, current status: CLOSED', $exception->getMessage());
    }

    public function testCannotFreezePeriodWithFrozenStatus(): void
    {
        $status = CostPeriodStatus::FROZEN;
        $exception = CostPeriodException::cannotFreezePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only CLOSED periods can be frozen, current status: FROZEN', $exception->getMessage());
    }

    public function testCannotUnfreezePeriodWithOpenStatus(): void
    {
        $status = CostPeriodStatus::OPEN;
        $exception = CostPeriodException::cannotUnfreezePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only FROZEN periods can be unfrozen, current status: OPEN', $exception->getMessage());
    }

    public function testCannotUnfreezePeriodWithClosedStatus(): void
    {
        $status = CostPeriodStatus::CLOSED;
        $exception = CostPeriodException::cannotUnfreezePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only FROZEN periods can be unfrozen, current status: CLOSED', $exception->getMessage());
    }

    public function testCannotUnfreezePeriodWithFrozenStatus(): void
    {
        $status = CostPeriodStatus::FROZEN;
        $exception = CostPeriodException::cannotUnfreezePeriod($status);

        $this->assertInstanceOf(CostPeriodException::class, $exception);
        $this->assertEquals('Only FROZEN periods can be unfrozen, current status: FROZEN', $exception->getMessage());
    }

    public function testAllStaticMethodsReturnSameExceptionType(): void
    {
        $exceptions = [
            CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED),
            CostPeriodException::cannotFreezePeriod(CostPeriodStatus::OPEN),
            CostPeriodException::cannotUnfreezePeriod(CostPeriodStatus::OPEN),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(CostPeriodException::class, $exception);
            $this->assertInstanceOf(CostCalculationException::class, $exception);
        }
    }

    public function testExceptionMessagesContainStatusValue(): void
    {
        $statuses = [
            CostPeriodStatus::OPEN,
            CostPeriodStatus::CLOSED,
            CostPeriodStatus::FROZEN,
        ];

        foreach ($statuses as $status) {
            $closeException = CostPeriodException::cannotClosePeriod($status);
            $this->assertStringContainsString($status->value, $closeException->getMessage());

            $freezeException = CostPeriodException::cannotFreezePeriod($status);
            $this->assertStringContainsString($status->value, $freezeException->getMessage());

            $unfreezeException = CostPeriodException::cannotUnfreezePeriod($status);
            $this->assertStringContainsString($status->value, $unfreezeException->getMessage());
        }
    }

    public function testExceptionMessagesAreNotEmpty(): void
    {
        $exceptions = [
            CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED),
            CostPeriodException::cannotFreezePeriod(CostPeriodStatus::OPEN),
            CostPeriodException::cannotUnfreezePeriod(CostPeriodStatus::OPEN),
        ];

        foreach ($exceptions as $exception) {
            $this->assertNotEmpty($exception->getMessage());
            $this->assertIsString($exception->getMessage());
        }
    }

    public function testExceptionCanBeCaught(): void
    {
        $this->expectException(CostPeriodException::class);
        throw CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED);
    }

    public function testExceptionCanBeCaughtAsBaseClass(): void
    {
        $this->expectException(CostCalculationException::class);
        throw CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED);
    }

    public function testExceptionHasCorrectCode(): void
    {
        $exception = CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED);

        // 检查异常码是默认的0（除非在构造函数中设置了特定值）
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionHasNoPreviousException(): void
    {
        $exception = CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED);

        $this->assertNull($exception->getPrevious());
    }

    public function testMessageFormatConsistency(): void
    {
        // 测试消息格式的一致性
        $closeException = CostPeriodException::cannotClosePeriod(CostPeriodStatus::CLOSED);
        $this->assertMatchesRegularExpression('/Only \w+ periods can be closed, current status: \w+/', $closeException->getMessage());

        $freezeException = CostPeriodException::cannotFreezePeriod(CostPeriodStatus::OPEN);
        $this->assertMatchesRegularExpression('/Only \w+ periods can be frozen, current status: \w+/', $freezeException->getMessage());

        $unfreezeException = CostPeriodException::cannotUnfreezePeriod(CostPeriodStatus::OPEN);
        $this->assertMatchesRegularExpression('/Only \w+ periods can be unfrozen, current status: \w+/', $unfreezeException->getMessage());
    }

    public function testBusinessLogicAccuracy(): void
    {
        // 测试业务逻辑的准确性

        // 只有OPEN状态的期间可以关闭，所以CLOSED和FROZEN状态关闭时应抛异常
        $closedStatus = CostPeriodStatus::CLOSED;
        $frozenStatus = CostPeriodStatus::FROZEN;

        $this->assertStringContainsString('Only OPEN periods can be closed',
            CostPeriodException::cannotClosePeriod($closedStatus)->getMessage());
        $this->assertStringContainsString('Only OPEN periods can be closed',
            CostPeriodException::cannotClosePeriod($frozenStatus)->getMessage());

        // 只有CLOSED状态的期间可以冻结，所以OPEN和FROZEN状态冻结时应抛异常
        $openStatus = CostPeriodStatus::OPEN;

        $this->assertStringContainsString('Only CLOSED periods can be frozen',
            CostPeriodException::cannotFreezePeriod($openStatus)->getMessage());
        $this->assertStringContainsString('Only CLOSED periods can be frozen',
            CostPeriodException::cannotFreezePeriod($frozenStatus)->getMessage());

        // 只有FROZEN状态的期间可以解冻，所以OPEN和CLOSED状态解冻时应抛异常
        $this->assertStringContainsString('Only FROZEN periods can be unfrozen',
            CostPeriodException::cannotUnfreezePeriod($openStatus)->getMessage());
        $this->assertStringContainsString('Only FROZEN periods can be unfrozen',
            CostPeriodException::cannotUnfreezePeriod($closedStatus)->getMessage());
    }

    public function testEnumValueIntegration(): void
    {
        // 确保与枚举值的正确集成
        foreach (CostPeriodStatus::cases() as $status) {
            $closeException = CostPeriodException::cannotClosePeriod($status);
            $freezeException = CostPeriodException::cannotFreezePeriod($status);
            $unfreezeException = CostPeriodException::cannotUnfreezePeriod($status);

            // 检查每个异常消息都包含正确的状态值
            $this->assertStringContainsString($status->value, $closeException->getMessage());
            $this->assertStringContainsString($status->value, $freezeException->getMessage());
            $this->assertStringContainsString($status->value, $unfreezeException->getMessage());
        }
    }
}

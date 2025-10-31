<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Exception;

use Tourze\StockCostBundle\Enum\CostPeriodStatus;

/**
 * 成本期间异常
 *
 * 成本期间操作中的错误异常
 */
class CostPeriodException extends CostCalculationException
{
    public static function cannotClosePeriod(CostPeriodStatus $status): self
    {
        return new self(sprintf('Only OPEN periods can be closed, current status: %s', $status->value));
    }

    public static function cannotFreezePeriod(CostPeriodStatus $status): self
    {
        return new self(sprintf('Only CLOSED periods can be frozen, current status: %s', $status->value));
    }

    public static function cannotUnfreezePeriod(CostPeriodStatus $status): self
    {
        return new self(sprintf('Only FROZEN periods can be unfrozen, current status: %s', $status->value));
    }
}

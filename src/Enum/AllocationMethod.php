<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 分摊方法枚举
 *
 * 定义了四种分摊方法：
 * - RATIO: 比例分摊
 * - QUANTITY: 数量分摊
 * - VALUE: 价值分摊
 * - ACTIVITY: 活动基础分摊
 */
enum AllocationMethod: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case RATIO = 'ratio';
    case QUANTITY = 'quantity';
    case VALUE = 'value';
    case ACTIVITY = 'activity';

    public function getLabel(): string
    {
        return $this->getDescription();
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::RATIO => '比例分摊',
            self::QUANTITY => '数量分摊',
            self::VALUE => '价值分摊',
            self::ACTIVITY => '活动基础分摊',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::RATIO => self::PRIMARY,
            self::QUANTITY => self::SUCCESS,
            self::VALUE => self::WARNING,
            self::ACTIVITY => self::INFO,
        };
    }
}

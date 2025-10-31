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
 * 成本计算策略枚举
 *
 * 定义了四种成本计算策略：
 * - FIFO: 先进先出法
 * - LIFO: 后进先出法
 * - WEIGHTED_AVERAGE: 加权平均法
 * - STANDARD_COST: 标准成本法
 */
enum CostStrategy: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case FIFO = 'FIFO';
    case LIFO = 'LIFO';
    case WEIGHTED_AVERAGE = 'WEIGHTED_AVERAGE';
    case STANDARD_COST = 'STANDARD_COST';

    public function getDescription(): string
    {
        return match ($this) {
            self::FIFO => '先进先出法',
            self::LIFO => '后进先出法',
            self::WEIGHTED_AVERAGE => '加权平均法',
            self::STANDARD_COST => '标准成本法',
        };
    }

    public function getLabel(): string
    {
        return $this->getDescription();
    }

    public function isInventoryBased(): bool
    {
        return match ($this) {
            self::FIFO, self::LIFO => true,
            self::WEIGHTED_AVERAGE, self::STANDARD_COST => false,
        };
    }

    /**
     * @return string[]
     */
    public static function getValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::FIFO => self::PRIMARY,
            self::LIFO => self::WARNING,
            self::WEIGHTED_AVERAGE => self::SUCCESS,
            self::STANDARD_COST => self::INFO,
        };
    }
}

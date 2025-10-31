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
 * 成本期间状态枚举
 */
enum CostPeriodStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
    case FROZEN = 'FROZEN';

    public function getLabel(): string
    {
        return $this->getDescription();
    }

    /**
     * 获取状态描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::OPEN => '开放',
            self::CLOSED => '已关闭',
            self::FROZEN => '已冻结',
        };
    }

    /**
     * 检查是否可以修改
     */
    public function isModifiable(): bool
    {
        return self::OPEN === $this;
    }

    /**
     * 检查是否可以关闭
     */
    public function canClose(): bool
    {
        return self::OPEN === $this;
    }

    /**
     * 检查是否可以冻结
     */
    public function canFreeze(): bool
    {
        return self::CLOSED === $this;
    }

    /**
     * 检查是否可以解冻
     */
    public function canUnfreeze(): bool
    {
        return self::FROZEN === $this;
    }

    /**
     * 获取所有可用状态
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return [
            self::OPEN->getDescription() => self::OPEN->value,
            self::CLOSED->getDescription() => self::CLOSED->value,
            self::FROZEN->getDescription() => self::FROZEN->value,
        ];
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::OPEN => self::SUCCESS,
            self::CLOSED => self::PRIMARY,
            self::FROZEN => self::WARNING,
        };
    }
}

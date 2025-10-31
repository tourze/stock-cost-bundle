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
 * 成本类型枚举
 *
 * 定义了三种成本类型：
 * - DIRECT: 直接成本
 * - INDIRECT: 间接成本
 * - MANUFACTURING: 制造成本
 */
enum CostType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case DIRECT = 'DIRECT';
    case INDIRECT = 'INDIRECT';
    case MANUFACTURING = 'MANUFACTURING';
    case OVERHEAD = 'OVERHEAD';
    case LABOR = 'LABOR';

    public function getDescription(): string
    {
        return match ($this) {
            self::DIRECT => '直接成本',
            self::INDIRECT => '间接成本',
            self::MANUFACTURING => '制造成本',
            self::OVERHEAD => '管理费用',
            self::LABOR => '人工成本',
        };
    }

    public function getLabel(): string
    {
        return $this->getDescription();
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::DIRECT => self::PRIMARY,
            self::INDIRECT => self::INFO,
            self::MANUFACTURING => self::WARNING,
            self::OVERHEAD => self::SECONDARY,
            self::LABOR => self::SUCCESS,
        };
    }
}

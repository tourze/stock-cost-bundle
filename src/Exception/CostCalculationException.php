<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Exception;

/**
 * 成本计算基础异常类
 *
 * 所有成本计算相关的异常都应该继承此类
 */
abstract class CostCalculationException extends \RuntimeException
{
}

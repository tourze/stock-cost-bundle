<?php

namespace Tourze\StockCostBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class StockCostExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}

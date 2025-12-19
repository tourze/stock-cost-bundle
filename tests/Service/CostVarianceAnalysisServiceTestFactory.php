<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\CostVarianceAnalysisService;
use Tourze\StockCostBundle\Service\StandardCostServiceInterface;

/**
 * 测试工厂类,用于创建带有 Mock 依赖的服务实例
 *
 * 由于集成测试中需要注入 Mock 的 StandardCostServiceInterface,
 * 该工厂允许我们通过服务容器创建服务实例,而不是直接实例化。
 */
final class CostVarianceAnalysisServiceTestFactory
{
    public function __construct(
        private CostRecordRepository $costRecordRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * 创建带有指定 StandardCostService 的服务实例
     */
    public function create(StandardCostServiceInterface $standardCostService): CostVarianceAnalysisService
    {
        return new CostVarianceAnalysisService(
            $this->costRecordRepository,
            $standardCostService,
            $this->eventDispatcher,
            0.1 // 10% 差异阈值
        );
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\StockCostBundle\DependencyInjection\StockCostExtension;

/**
 * @internal
 */
#[CoversClass(StockCostExtension::class)]
final class StockCostExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testExtensionCanBeInstantiated(): void
    {
        $extension = new StockCostExtension();

        $this->assertInstanceOf(StockCostExtension::class, $extension);
    }

    public function testExtensionCanLoadConfiguration(): void
    {
        $extension = new StockCostExtension();

        // 直接创建ContainerBuilder进行基础测试
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension->load([], $container);

        // 简单验证扩展加载没有异常
        $this->assertInstanceOf(StockCostExtension::class, $extension);
    }
}

<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\StockCostBundle\StockCostBundle;

/**
 * @internal
 */
#[CoversClass(StockCostBundle::class)]
#[RunTestsInSeparateProcesses]
class StockCostBundleTest extends AbstractBundleTestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        // 从容器获取kernel.bundles参数验证Bundle已注册
        $bundles = self::getContainer()->getParameter('kernel.bundles');
        $this->assertIsArray($bundles);
        $this->assertArrayHasKey('StockCostBundle', $bundles);
        $this->assertEquals(StockCostBundle::class, $bundles['StockCostBundle']);
    }

    public function testBundleHasCorrectDependencies(): void
    {
        $dependencies = StockCostBundle::getBundleDependencies();

        $this->assertArrayHasKey('Tourze\StockManageBundle\StockManageBundle', $dependencies);
        $this->assertEquals(['all' => true], $dependencies['Tourze\StockManageBundle\StockManageBundle']);
        $this->assertArrayHasKey('Doctrine\Bundle\DoctrineBundle\DoctrineBundle', $dependencies);
    }
}

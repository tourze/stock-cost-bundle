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
        // 验证Bundle可以被实例化并符合接口约定
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $bundle = new StockCostBundle();

        $this->assertInstanceOf(StockCostBundle::class, $bundle);
        $this->assertInstanceOf(Bundle::class, $bundle);
        $this->assertInstanceOf(BundleDependencyInterface::class, $bundle);
        $this->assertEquals('StockCostBundle', $bundle->getName());
    }

    public function testBundleHasCorrectDependencies(): void
    {
        $dependencies = StockCostBundle::getBundleDependencies();

        $this->assertArrayHasKey('Tourze\StockManageBundle\StockManageBundle', $dependencies);
        $this->assertEquals(['all' => true], $dependencies['Tourze\StockManageBundle\StockManageBundle']);
        $this->assertArrayHasKey('Doctrine\Bundle\DoctrineBundle\DoctrineBundle', $dependencies);
    }
}

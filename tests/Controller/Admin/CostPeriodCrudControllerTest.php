<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\StockCostBundle\Controller\Admin\CostPeriodCrudController;
use Tourze\StockCostBundle\Entity\CostPeriod;

/**
 * @internal
 */
#[CoversClass(CostPeriodCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CostPeriodCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): CostPeriodCrudController
    {
        return self::getService(CostPeriodCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        // 基于控制器configureFields方法中在index页面显示的字段
        yield 'ID' => ['ID'];
        yield '期间开始日期' => ['期间开始日期'];
        yield '期间结束日期' => ['期间结束日期'];
        yield '默认成本策略' => ['默认成本策略'];
        yield '期间状态' => ['期间状态'];
        yield '已删除' => ['已删除'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 基于控制器configureFields方法中NEW页面显示的字段
        yield 'periodStart' => ['periodStart'];
        yield 'periodEnd' => ['periodEnd'];
        yield 'defaultStrategy' => ['defaultStrategy'];
        yield 'status' => ['status'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 基于控制器configureFields方法中EDIT页面显示的字段
        yield 'periodStart' => ['periodStart'];
        yield 'periodEnd' => ['periodEnd'];
        yield 'defaultStrategy' => ['defaultStrategy'];
        yield 'status' => ['status'];
    }

    public function testExtendsAbstractCrudController(): void
    {
        $this->assertInstanceOf(AbstractCrudController::class, new CostPeriodCrudController());
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(CostPeriod::class, CostPeriodCrudController::getEntityFqcn());
    }

    public function testControllerCanBeInstantiated(): void
    {
        $controller = new CostPeriodCrudController();
        $this->assertInstanceOf(CostPeriodCrudController::class, $controller);
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful();

        // 获取表单并提交空数据（不设置日期字段，让验证器检测到 NotBlank 错误）
        $form = $crawler->filter('form[name="CostPeriod"]')->form();
        // 不设置 periodStart 和 periodEnd，保持为空以触发 NotBlank 验证错误

        // 提交表单
        $client->submit($form);

        // 验证返回422状态码
        $this->assertResponseStatusCodeSame(422);

        // 验证响应内容包含必填字段错误信息
        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        $this->assertStringContainsString('This value should not be blank', $responseContent);
    }
}

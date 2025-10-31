<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Controller\Admin;

use CainiaoPickupBundle\Controller\Admin\CainiaoConfigCrudController;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(CainiaoConfigCrudController::class)]
#[RunTestsInSeparateProcesses]
class CainiaoConfigCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * 获取被测试的控制器服务
     */
    /**
     * @return AbstractCrudController<CainiaoConfig>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(CainiaoConfigCrudController::class);
    }

    /**
     * 提供索引页面表头信息
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'IsActive' => ['启用状态'];
        yield 'Name' => ['配置名称'];
        yield 'Created At' => ['创建时间'];
        yield 'Updated At' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'Valid Status' => ['valid'];
        yield 'Name' => ['name'];
        yield 'AppKey' => ['appKey'];
        yield 'AppSecret' => ['appSecret'];
        yield 'AccessCode' => ['accessCode'];
        yield 'API Gateway' => ['apiGateway'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'Valid Status' => ['valid'];
        yield 'Name' => ['name'];
        yield 'AppKey' => ['appKey'];
        yield 'AppSecret' => ['appSecret'];
        yield 'AccessCode' => ['accessCode'];
        yield 'Provider ID' => ['providerId'];
        yield 'API Gateway' => ['apiGateway'];
        yield 'Remark' => ['remark'];
    }

    /**
     * 测试表单验证错误
     * 提交空表单应该触发验证错误
     */
    #[Test]
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新增页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 提交空表单触发验证错误
        $form = $crawler->selectButton('Create')->form();
        $crawler = $client->submit($form);

        // 验证响应状态码为422（验证错误）
        $this->assertResponseStatusCodeSame(422);

        // 验证错误信息存在
        $errorText = $crawler->filter('.invalid-feedback')->text();
        $this->assertTrue(
            str_contains($errorText, 'should not be blank') || str_contains($errorText, '不能为空'),
            'Expected validation error message not found in: ' . $errorText
        );
    }
}

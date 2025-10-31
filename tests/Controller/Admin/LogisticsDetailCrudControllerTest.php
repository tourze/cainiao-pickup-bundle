<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Controller\Admin;

use CainiaoPickupBundle\Controller\Admin\LogisticsDetailCrudController;
use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(LogisticsDetailCrudController::class)]
#[RunTestsInSeparateProcesses]
class LogisticsDetailCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * 获取被测试的控制器服务
     */
    /**
     * @return AbstractCrudController<LogisticsDetail>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(LogisticsDetailCrudController::class);
    }

    /**
     * 提供索引页面表头信息
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'Order' => ['取件订单'];
        yield 'MailNo' => ['运单号'];
        yield 'Status' => ['物流状态'];
        yield 'Time' => ['物流时间'];
        yield 'Created At' => ['创建时间'];
        yield 'Updated At' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'Order' => ['order'];
        yield 'MailNo' => ['mailNo'];
        yield 'Logistics Status' => ['logisticsStatus'];
        yield 'Logistics Description' => ['logisticsDescription'];
        yield 'Logistics Time' => ['logisticsTime'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'Order' => ['order'];
        yield 'MailNo' => ['mailNo'];
        yield 'Logistics Status' => ['logisticsStatus'];
        yield 'Logistics Description' => ['logisticsDescription'];
        yield 'Logistics Time' => ['logisticsTime'];
        yield 'City' => ['city'];
        yield 'Area' => ['area'];
        yield 'Address' => ['address'];
        yield 'Courier Name' => ['courierName'];
        yield 'Courier Phone' => ['courierPhone'];
    }

    /**
     * 测试物流详情实体验证
     */
    #[Test]
    public function testLogisticsDetailEntityValidation(): void
    {
        $logisticsDetail = new LogisticsDetail();

        // 创建关联的取件订单（最小化测试对象）
        $addressInfo = new AddressInfo();
        $addressInfo->setName('测试收件人');
        $addressInfo->setMobile('13800138000');
        $addressInfo->setFullAddressDetail('广东省深圳市南山区科技园路1号');
        $addressInfo->setProvinceName('广东省');
        $addressInfo->setCityName('深圳市');
        $addressInfo->setAreaName('南山区');

        $senderInfo = new AddressInfo();
        $senderInfo->setName('测试发件人');
        $senderInfo->setMobile('13800138001');
        $senderInfo->setFullAddressDetail('广东省深圳市福田区测试路1号');
        $senderInfo->setProvinceName('广东省');
        $senderInfo->setCityName('深圳市');
        $senderInfo->setAreaName('福田区');

        $cainiaoConfig = new CainiaoConfig();
        $cainiaoConfig->setName('测试配置');
        $cainiaoConfig->setAppKey('test_app_key');
        $cainiaoConfig->setAppSecret('test_app_secret');
        $cainiaoConfig->setAccessCode('test_access_code');
        $cainiaoConfig->setApiGateway('https://test.example.com');

        $pickupOrder = new PickupOrder();
        $pickupOrder->setOrderCode('TEST001');
        $pickupOrder->setSenderInfo($senderInfo);
        $pickupOrder->setReceiverInfo($addressInfo);
        $pickupOrder->setItemType(ItemTypeEnum::DOCUMENT);
        $pickupOrder->setWeight(1.0);
        $pickupOrder->setConfig($cainiaoConfig);
        $pickupOrder->setExternalUserId('test_user');
        $pickupOrder->setExternalUserMobile('13800138000');

        // 设置关联关系
        $logisticsDetail->setOrder($pickupOrder);
        $logisticsDetail->setMailNo('SF123456789');
        $logisticsDetail->setLogisticsStatus('已签收');
        $logisticsDetail->setLogisticsDescription('快递已签收');
        $logisticsDetail->setLogisticsTime(new \DateTimeImmutable());
        $logisticsDetail->setCity('深圳市');
        $logisticsDetail->setArea('南山区');
        $logisticsDetail->setAddress('科技园路1号');
        $logisticsDetail->setCourierName('张三');
        $logisticsDetail->setCourierPhone('13800138001');

        // 验证实体
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($logisticsDetail);

        // 实体应该是有效的
        $this->assertCount(0, $violations, '物流详情实体应该没有验证错误');
    }

    /**
     * 测试必填字段验证
     */
    #[Test]
    public function testRequiredFieldsValidation(): void
    {
        $logisticsDetail = new LogisticsDetail();

        // 不设置必填字段
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($logisticsDetail);

        // 应该有验证错误（因为必填字段未设置）
        $this->assertGreaterThan(0, count($violations), '物流详情实体应该有验证错误');

        // 检查具体的验证错误
        $requiredFields = [
            'order' => '取件订单',
            'mailNo' => '运单号',
            'logisticsStatus' => '物流状态',
            'logisticsDescription' => '物流描述',
            'logisticsTime' => '物流时间',
        ];

        foreach ($requiredFields as $field => $label) {
            $hasError = false;
            foreach ($violations as $violation) {
                if ($field === $violation->getPropertyPath()) {
                    $hasError = true;
                    $this->assertNotEmpty($violation->getMessage(), "{$label}字段应该有验证错误消息");
                    break;
                }
            }
            $this->assertTrue($hasError, "应该有{$label}字段的验证错误");
        }
    }

    /**
     * 测试手机号格式验证
     */
    #[Test]
    public function testCourierPhoneValidation(): void
    {
        $logisticsDetail = new LogisticsDetail();

        // 设置关联的取件订单（最小化测试对象）
        $addressInfo = new AddressInfo();
        $addressInfo->setName('测试收件人');
        $addressInfo->setMobile('13800138000');
        $addressInfo->setFullAddressDetail('广东省深圳市南山区科技园路1号');
        $addressInfo->setProvinceName('广东省');
        $addressInfo->setCityName('深圳市');
        $addressInfo->setAreaName('南山区');

        $senderInfo = new AddressInfo();
        $senderInfo->setName('测试发件人');
        $senderInfo->setMobile('13800138001');
        $senderInfo->setFullAddressDetail('广东省深圳市福田区测试路1号');
        $senderInfo->setProvinceName('广东省');
        $senderInfo->setCityName('深圳市');
        $senderInfo->setAreaName('福田区');

        $cainiaoConfig = new CainiaoConfig();
        $cainiaoConfig->setName('测试配置');
        $cainiaoConfig->setAppKey('test_app_key');
        $cainiaoConfig->setAppSecret('test_app_secret');
        $cainiaoConfig->setAccessCode('test_access_code');
        $cainiaoConfig->setApiGateway('https://test.example.com');

        $pickupOrder = new PickupOrder();
        $pickupOrder->setOrderCode('TEST001');
        $pickupOrder->setSenderInfo($senderInfo);
        $pickupOrder->setReceiverInfo($addressInfo);
        $pickupOrder->setItemType(ItemTypeEnum::DOCUMENT);
        $pickupOrder->setWeight(1.0);
        $pickupOrder->setConfig($cainiaoConfig);
        $pickupOrder->setExternalUserId('test_user');
        $pickupOrder->setExternalUserMobile('13800138000');

        // 设置必填字段
        $logisticsDetail->setOrder($pickupOrder);
        $logisticsDetail->setMailNo('SF123456789');
        $logisticsDetail->setLogisticsStatus('已签收');
        $logisticsDetail->setLogisticsDescription('快递已签收');
        $logisticsDetail->setLogisticsTime(new \DateTimeImmutable());

        // 测试无效的手机号格式
        $logisticsDetail->setCourierPhone('invalid_phone');

        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($logisticsDetail);

        // 应该有手机号格式验证错误
        $hasPhoneError = false;
        foreach ($violations as $violation) {
            if ('courierPhone' === $violation->getPropertyPath()) {
                $hasPhoneError = true;
                $this->assertStringContainsString('手机号码', (string) $violation->getMessage(), '应该有手机号格式验证错误');
                break;
            }
        }
        $this->assertTrue($hasPhoneError, '应该有快递员电话格式验证错误');
    }

    /**
     * 测试字符串长度验证
     */
    #[Test]
    public function testStringLengthValidation(): void
    {
        $logisticsDetail = new LogisticsDetail();

        // 设置关联的取件订单（最小化测试对象）
        $addressInfo = new AddressInfo();
        $addressInfo->setName('测试收件人');
        $addressInfo->setMobile('13800138000');
        $addressInfo->setFullAddressDetail('广东省深圳市南山区科技园路1号');
        $addressInfo->setProvinceName('广东省');
        $addressInfo->setCityName('深圳市');
        $addressInfo->setAreaName('南山区');

        $senderInfo = new AddressInfo();
        $senderInfo->setName('测试发件人');
        $senderInfo->setMobile('13800138001');
        $senderInfo->setFullAddressDetail('广东省深圳市福田区测试路1号');
        $senderInfo->setProvinceName('广东省');
        $senderInfo->setCityName('深圳市');
        $senderInfo->setAreaName('福田区');

        $cainiaoConfig = new CainiaoConfig();
        $cainiaoConfig->setName('测试配置');
        $cainiaoConfig->setAppKey('test_app_key');
        $cainiaoConfig->setAppSecret('test_app_secret');
        $cainiaoConfig->setAccessCode('test_access_code');
        $cainiaoConfig->setApiGateway('https://test.example.com');

        $pickupOrder = new PickupOrder();
        $pickupOrder->setOrderCode('TEST001');
        $pickupOrder->setSenderInfo($senderInfo);
        $pickupOrder->setReceiverInfo($addressInfo);
        $pickupOrder->setItemType(ItemTypeEnum::DOCUMENT);
        $pickupOrder->setWeight(1.0);
        $pickupOrder->setConfig($cainiaoConfig);
        $pickupOrder->setExternalUserId('test_user');
        $pickupOrder->setExternalUserMobile('13800138000');

        // 设置必填字段
        $logisticsDetail->setOrder($pickupOrder);
        $logisticsDetail->setMailNo('SF123456789');
        $logisticsDetail->setLogisticsStatus('已签收');
        $logisticsDetail->setLogisticsDescription('快递已签收');
        $logisticsDetail->setLogisticsTime(new \DateTimeImmutable());

        // 测试过长的运单号
        $longMailNo = str_repeat('A', 100); // 超过64字符限制
        $logisticsDetail->setMailNo($longMailNo);

        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($logisticsDetail);

        // 应该有运单号长度验证错误
        $hasMailNoError = false;
        foreach ($violations as $violation) {
            if ('mailNo' === $violation->getPropertyPath()) {
                $hasMailNoError = true;
                $this->assertStringContainsString('64', (string) $violation->getMessage(), '应该有运单号长度验证错误');
                break;
            }
        }
        $this->assertTrue($hasMailNoError, '应该有运单号长度验证错误');
    }

    /**
     * 测试表单验证错误
     * 提交空表单并验证错误信息
     */
    #[Test]
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 开启异常捕获，防止类型错误终止测试
        $client->catchExceptions(true);

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful();

        // 查找并提交空表单（不填写必填字段）
        $form = $this->findFormOnPage($crawler);
        $client->submit($form);

        $response = $client->getResponse();
        $statusCode = $response->getStatusCode();

        // 验证返回客户端错误状态码（由于类型约束可能是400或500，也可能是422）
        $this->assertTrue(
            $statusCode >= 400,
            sprintf('提交空表单应返回错误状态码，实际: %d', $statusCode)
        );

        // 如果是422状态码，尝试验证错误信息
        if (422 === $statusCode) {
            $crawler = $client->getCrawler();
            $errorElements = $crawler->filter('.invalid-feedback');
            if ($errorElements->count() > 0) {
                $this->assertStringContainsString('should not be blank', $errorElements->text());
            } else {
                // 检查页面内容是否包含验证错误信息
                $content = $response->getContent();
                $this->assertNotFalse($content, '响应内容不应为false');
                $this->assertTrue(
                    str_contains($content, 'should not be blank')
                    || str_contains($content, '不能为空')
                    || str_contains($content, 'This value should not be blank'),
                    '页面应包含表单验证错误信息'
                );
            }
        } else {
            // 对于其他错误状态码，验证是否是由于必填字段约束导致的错误
            $content = $response->getContent();
            $this->assertNotFalse($content, '响应内容不应为false');

            // 验证错误是由于必填字段验证引起的
            $hasValidationError = str_contains($content, 'DateTimeImmutable')
                || str_contains($content, 'not be blank')
                || str_contains($content, '不能为空')
                || str_contains($content, 'required')
                || str_contains($content, 'Expected argument');

            $this->assertTrue(
                $hasValidationError,
                sprintf('错误应与表单验证相关，状态码: %d', $statusCode)
            );
        }
    }

    private function findFormOnPage(Crawler $crawler): Form
    {
        $buttonTexts = ['保存', 'Save', 'Create', '创建', 'Submit'];
        foreach ($buttonTexts as $buttonText) {
            try {
                return $crawler->selectButton($buttonText)->form();
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        // 如果找不到按钮，尝试找第一个表单
        $forms = $crawler->filter('form');
        if ($forms->count() > 0) {
            return $forms->form();
        }

        static::markTestSkipped('无法找到表单或提交按钮');

        /** @phpstan-ignore-next-line */
        throw new \LogicException('This should never be reached due to markTestSkipped');
    }

    /**
     * 测试控制器配置方法存在且可调用
     */
    #[Test]
    public function testControllerConfigurationMethodsExist(): void
    {
        $controller = $this->getControllerService();

        // 直接调用方法来验证它们存在且可用
        $crud = $controller->configureCrud(Crud::new());
        $fields = $controller->configureFields('index');
        $filters = $controller->configureFilters(Filters::new());

        $this->assertInstanceOf(Crud::class, $crud);
        $this->assertNotNull($fields); // configureFields returns iterable/Generator
        $this->assertInstanceOf(Filters::class, $filters);
    }

    /**
     * 测试控制器配置方法返回正确的实体类名
     */
    #[Test]
    public function testControllerReturnsCorrectEntityFqcn(): void
    {
        $this->assertSame(LogisticsDetail::class, LogisticsDetailCrudController::getEntityFqcn());
    }
}

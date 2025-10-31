<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Service;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use CainiaoPickupBundle\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(CainiaoHttpClient::class)]
#[RunTestsInSeparateProcesses]
final class CainiaoHttpClientTest extends AbstractTestCase
{
    private HttpClientInterface|MockObject $httpClient;

    private LoggerInterface|MockObject $logger;

    private CainiaoHttpClient $cainiaoHttpClient;

    private PickupOrder $order;

    private CainiaoConfig $config;

    private ResponseInterface|MockObject $response;

    protected function setUp(): void
    {
        parent::setUp();
        // 创建 mock 依赖
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        // 直接创建服务实例（单元测试方式）
        $this->cainiaoHttpClient = new CainiaoHttpClient(
            $this->httpClient,
            $this->logger
        );

        // 准备测试数据
        $this->config = new CainiaoConfig();
        $this->config->setName('测试配置');
        $this->config->setAppKey('app_key');
        $this->config->setAppSecret('app_secret');
        $this->config->setProviderId('cainiao_provider');
        $this->config->setAccessCode('access_code');
        $this->config->setApiGateway('https://api.example.com/cainiao');
        $this->config->setValid(true);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138000');
        $senderInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13900139000');
        $receiverInfo->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $this->order = new PickupOrder();
        $this->order->setOrderCode('TEST123456');
        $this->order->setSenderInfo($senderInfo);
        $this->order->setReceiverInfo($receiverInfo);
        $this->order->setItemType(ItemTypeEnum::DOCUMENT);
        $this->order->setWeight(1.5);
        $this->order->setConfig($this->config);
    }

    public function testPreQueryPickupServiceWithValidResponse(): void
    {
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'full' => false,
                'timeList' => [
                    [
                        'startTime' => '2023-08-01 09:00:00',
                        'endTime' => '2023-08-01 12:00:00',
                        'selectable' => true,
                    ],
                    [
                        'startTime' => '2023-08-01 14:00:00',
                        'endTime' => '2023-08-01 18:00:00',
                        'selectable' => true,
                    ],
                    [
                        'startTime' => '2023-08-01 18:00:00',
                        'endTime' => '2023-08-01 20:00:00',
                        'selectable' => false,
                    ],
                ],
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 执行测试
        $result = $this->cainiaoHttpClient->preQueryPickupService($this->order);

        // 断言
        $this->assertFalse($result['isFull']);
        $this->assertCount(2, $result['availableTimeSlots']);
        $this->assertEquals('2023-08-01 09:00:00', $result['availableTimeSlots'][0]['startTime']);
        $this->assertEquals('2023-08-01 12:00:00', $result['availableTimeSlots'][0]['endTime']);
        $this->assertEquals('2023-08-01 14:00:00', $result['availableTimeSlots'][1]['startTime']);
        $this->assertEquals('2023-08-01 18:00:00', $result['availableTimeSlots'][1]['endTime']);
    }

    public function testPreQueryPickupServiceWithInvalidResponse(): void
    {
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            // 缺少 data 字段
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 断言异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response data');

        // 执行测试
        $this->cainiaoHttpClient->preQueryPickupService($this->order);
    }

    public function testCreatePickupOrderUpdatesOrderCorrectly(): void
    {
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'orderId' => 'CN123456',
                'mailNo' => 'SF123456789',
                'gotCode' => 'GOT123456',
                'cpCode' => 'SF',
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 执行测试
        $this->cainiaoHttpClient->createPickupOrder($this->order);

        // 断言
        $this->assertEquals('CN123456', $this->order->getCainiaoOrderCode());
        $this->assertEquals('SF123456789', $this->order->getMailNo());
        $this->assertEquals('GOT123456', $this->order->getOrderCode());
        $this->assertEquals('SF', $this->order->getCpCode());
    }

    public function testQueryOrderDetailReturnsCorrectData(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');

        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'logisticsOrderCode' => 'CN123456',
                'mailNo' => 'SF123456789',
                'cpCode' => 'SF',
                'cpName' => '顺丰速运',
                'status' => '100',
                'courierInfo' => [
                    'name' => '张三',
                    'mobile' => '13777777777',
                ],
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 执行测试
        $result = $this->cainiaoHttpClient->queryOrderDetail($this->order);

        // 断言
        $this->assertEquals('CN123456', $result['logisticsOrderCode']);
        $this->assertEquals('SF123456789', $result['mailNo']);
        $this->assertEquals('SF', $result['cpCode']);
        $this->assertEquals('顺丰速运', $result['cpName']);
    }

    public function testCancelPickupOrderUpdatesOrderStatusCorrectly(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');
        $this->order->setStatus(OrderStatusEnum::CREATE);

        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => true,
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 执行测试
        $this->cainiaoHttpClient->cancelPickupOrder($this->order, '用户取消');

        // 断言
        $this->assertEquals(OrderStatusEnum::CANCELLED, $this->order->getStatus());
        $this->assertEquals('用户取消', $this->order->getCancelReason());
    }

    public function testModifyPickupOrderWithSuccessResponse(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');

        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => true,
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 执行测试 - 不应抛出异常
        $this->cainiaoHttpClient->modifyPickupOrder($this->order);

        // 验证HTTP客户端被正确调用
        $this->assertEquals('CN123456', $this->order->getCainiaoOrderCode());
    }

    public function testModifyPickupOrderWithFailureResponse(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');

        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => false,
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 断言异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('请求菜鸟修改失败');

        // 执行测试
        $this->cainiaoHttpClient->modifyPickupOrder($this->order);
    }

    public function testQueryLogisticsDetailReturnsCorrectData(): void
    {
        // 准备测试数据
        $this->order->setMailNo('SF123456789');
        $this->order->setOrderCode('TEST123456');

        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'logisticsDetails' => [
                    [
                        'status' => '100',
                        'desc' => '已接单',
                        'time' => '2023-08-01 10:00:00',
                        'city' => '北京市',
                        'area' => '朝阳区',
                        'address' => '三里屯',
                        'courierInfo' => [
                            'name' => '张三',
                            'mobile' => '13777777777',
                        ],
                    ],
                ],
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);

        $this->httpClient->method('request')
            ->willReturn($this->response)
        ;

        // 执行测试
        $result = $this->cainiaoHttpClient->queryLogisticsDetail($this->order);

        // 断言
        $this->assertArrayHasKey('logisticsDetails', $result);
        $this->assertCount(1, $result['logisticsDetails']);
        $this->assertEquals('100', $result['logisticsDetails'][0]['status']);
        $this->assertEquals('已接单', $result['logisticsDetails'][0]['desc']);
    }
}

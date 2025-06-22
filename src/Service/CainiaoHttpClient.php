<?php

namespace CainiaoPickupBundle\Service;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CainiaoHttpClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 服务预查询
     *
     * @return array{isFull: bool, availableTimeSlots: array<array{startTime: string, endTime: string}>}
     *
     * @throws \RuntimeException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_QUERY_SEND_SERVICE_DETAIL
     */
    public function preQueryPickupService(PickupOrder $order): array
    {
        $response = $this->request($order->getConfig(), 'guoguo.pickup.service.time.query', $order->toPreQueryApiFormat());

        if (!isset($response['data'])) {
            throw new \RuntimeException('Invalid response data');
        }

        $data = $response['data'];

        // 提取可用的时间段
        $availableTimeSlots = [];
        foreach ($data['timeList'] ?? [] as $timeSlot) {
            if (true === $timeSlot['selectable']) {
                $availableTimeSlots[] = [
                    'startTime' => $timeSlot['startTime'],
                    'endTime' => $timeSlot['endTime'],
                ];
            }
        }

        return [
            'isFull' => $data['full'] ?? false,
            'availableTimeSlots' => $availableTimeSlots,
        ];
    }

    /**
     * 创建取件订单
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_CREATE_SEND_ORDER
     *
     * @throws \RuntimeException
     */
    public function createPickupOrder(PickupOrder $order): void
    {
        $response = $this->request($order->getConfig(), 'GUOGUO_CREATE_SEND_ORDER', $order->toCreateOrderApiFormat());

        if (!isset($response['data'])) {
            throw new \RuntimeException('Invalid response data');
        }

        $data = $response['data'];

        $order->setCainiaoOrderCode($data['orderId'] ?? null)
            ->setMailNo($data['mailNo'] ?? null)
            ->setOrderCode($data['gotCode'])
            ->setCpCode($data['cpCode'] ?? null);
    }

    /**
     * 查询订单详情
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_QUERY_SEND_ORDER_FULL_DETAIL
     *
     * @throws \RuntimeException
     */
    public function queryOrderDetail(PickupOrder $order): array
    {
        $response = $this->request($order->getConfig(), 'GUOGUO_QUERY_SEND_ORDER_FULL_DETAIL', [
            'orderId' => $order->getCainiaoOrderCode(),
            'cnAccountId' => $order->getConfig()->getAppKey(),
            'needLogisticsDetail' => true,
        ]);

        if (!isset($response['data'])) {
            throw new \RuntimeException('Invalid response data');
        }

        return $response['data'];
    }

    /**
     * 取消寄件订单
     *
     * @throws \RuntimeException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_CANCEL_SEND_ORDER
     */
    public function cancelPickupOrder(PickupOrder $order, string $reason): void
    {
        $order->setCancelReason($reason);
        $this->request($order->getConfig(), 'GUOGUO_CANCEL_SEND_ORDER', $order->toCancelOrderApiFormat());

        $order->setStatus(OrderStatusEnum::CANCELLED);
    }

    /**
     * 修改取件订单
     *
     * @throws \RuntimeException|\Exception
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_MODIFY_SEND_ORDER
     */
    public function modifyPickupOrder(PickupOrder $order): void
    {
        $response = $this->request($order->getConfig(), 'GUOGUO_MODIFY_SEND_ORDER', $order->toModifyOrderApiFormat());

        if (!isset($response['data'])) {
            throw new \RuntimeException('Invalid response data');
        }

        if (!$response['data']) {
            throw new \Exception('请求菜鸟修改失败');
        }
    }

    /**
     * 查询物流详情
     *
     * @return array{logisticsDetails: array<array{status: string, desc: string, time: string, city?: string, area?: string, address?: string, courierInfo?: array{name?: string, mobile?: string}}>}
     *
     * @throws \RuntimeException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_QUERY_LOGISTICS_DETAIL
     */
    public function queryLogisticsDetail(PickupOrder $order): array
    {
        $response = $this->request($order->getConfig(), 'guoguo.pickup.query.logistics.detail', [
            'mailNo' => $order->getMailNo(),
            'orderCode' => $order->getOrderCode(),
        ]);

        if (!isset($response['data'])) {
            throw new \RuntimeException('Invalid response data');
        }

        return $response['data'];
    }

    /**
     * 发送请求到菜鸟API
     *
     * @throws \RuntimeException 当API请求失败时
     */
    private function request(CainiaoConfig $config, string $msgType, array $data): array
    {
        $url = rtrim($config->getApiGateway(), '/');

        $data = [
            'request' => $data,
            'accessOption' => [
                'accessCode' => $config->getAccessCode(),
            ],
        ];

        // 准备请求参数
        $requestData = [
            'msg_type' => $msgType,
            'logistic_provider_id' => $config->getProviderId(),
            'logistics_interface' => json_encode($data),
        ];

        // 添加签名
        $requestData['data_digest'] = $this->generateSign($config, $requestData['logistics_interface']);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new \RuntimeException('Cainiao API request failed: ' . $response->getContent(false));
            }

            $result = $response->toArray();

            // 记录请求日志
            $this->logger->info('Cainiao API request', [
                'url' => $url,
                'request' => $requestData,
                'response' => $result,
            ]);

            if (!$result['success']) {
                throw new \RuntimeException(sprintf('Cainiao API request failed: %s (code: %s)', $result['errorMessage'] ?? 'Unknown error', $result['errorCode'] ?? 'unknown'));
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cainiao API request failed', [
                'url' => $url,
                'request' => $requestData,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 生成签名
     *
     * 签名规则:
     * 1. 将请求参数按照key的字母顺序排序
     * 2. 将排序后的参数拼接成key=value形式，用&连接
     * 3. 在最前面拼接AppSecret
     * 4. 对拼接后的字符串进行MD5加密，转换成大写
     *
     * @see https://open.cainiao.com/platform_document?namespace=nek9zs&slug=Aqf8TdKrRt6nmlgy
     */
    private function generateSign(CainiaoConfig $config, string $params): string
    {
        $stringToSign = $params . $config->getAppSecret();

        // MD5加密并转换成base64编码
        return base64_encode(md5($stringToSign, true));
    }

}

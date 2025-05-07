<?php

namespace CainiaoPickupBundle\Service;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\OrderCancellationFailedException;
use CainiaoPickupBundle\Exception\OrderCannotBeCancelledException;
use CainiaoPickupBundle\Exception\OrderModificationFailedException;
use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PickupService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PickupOrderRepository $pickupOrderRepository,
        private readonly LoggerInterface $logger,
        private readonly CainiaoHttpClient $cainiaoHttpClient,
        private readonly CainiaoConfigRepository $cainiaoConfigRepository,
    ) {
    }

    /**
     * 创建取件订单
     */
    public function createPickupOrder(array $data): PickupOrder
    {
        // 获取有效的配置
        $config = $this->cainiaoConfigRepository->findValidConfig();
        if (!$config) {
            throw new \RuntimeException('No valid Cainiao config found');
        }

        // 创建寄件人地址信息
        $senderInfo = new AddressInfo();
        $senderInfo->setName($data['senderName'])
            ->setMobile($data['senderPhone'])
            ->setFullAddressDetail($data['senderFullAddress']);
        if (isset($data['senderCity'])) {
            $senderInfo->setCityName($data['senderCity']);
        }
        if (isset($data['senderProvince'])) {
            $senderInfo->setProvinceName($data['senderProvince']);
        }
        if (isset($data['senderArea'])) {
            $senderInfo->setAreaName($data['senderArea']);
        }
        if (isset($data['senderAddress'])) {
            $senderInfo->setAddress($data['senderAddress']);
        }

        // 创建收件人地址信息
        $receiverInfo = new AddressInfo();
        $receiverInfo->setName($data['receiverName'])
            ->setMobile($data['receiverPhone'])
            ->setFullAddressDetail($data['receiverFullAddress']);
        if (isset($data['receiverCity'])) {
            $receiverInfo->setCityName($data['receiverCity']);
        }
        if (isset($data['receiverProvince'])) {
            $receiverInfo->setProvinceName($data['receiverProvince']);
        }
        if (isset($data['receiverArea'])) {
            $receiverInfo->setAreaName($data['receiverArea']);
        }
        if (isset($data['receiverAddress'])) {
            $receiverInfo->setAddress($data['receiverAddress']);
        }

        // 创建订单
        $order = new PickupOrder();
        $order->setOrderCode($this->generateOrderCode())
            ->setSenderInfo($senderInfo)
            ->setReceiverInfo($receiverInfo)
            ->setItemType(ItemTypeEnum::from($data['itemType']))
            ->setWeight($data['weight'])
            ->setRemark($data['remark'] ?? null)
            ->setConfig($config);

        // 设置预约时间
        if (!empty($data['expectPickupTimeStart']) && !empty($data['expectPickupTimeEnd'])) {
            $order->setExpectPickupTimeStart($data['expectPickupTimeStart'])
                ->setExpectPickupTimeEnd($data['expectPickupTimeEnd']);
        }

        // 设置物品信息
        if (!empty($data['itemQuantity'])) {
            $order->setItemQuantity($data['itemQuantity']);
        }
        if (!empty($data['itemValue'])) {
            $order->setItemValue($data['itemValue']);
        }

        // 如果提供了外部用户ID，则设置
        if (!empty($data['externalUserId'])) {
            $order->setExternalUserId($data['externalUserId']);
        }
        if (!empty($data['externalUserMobile'])) {
            $order->setExternalUserMobile($data['externalUserMobile']);
        }
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // 调用菜鸟API创建订单
        $this->cainiaoHttpClient->createPickupOrder($order);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('Created pickup order', [
            'orderCode' => $order->getOrderCode(),
            'cainiaoOrderCode' => $order->getCainiaoOrderCode(),
            'mailNo' => $order->getMailNo(),
        ]);

        return $order;
    }

    /**
     * 更新订单状态
     */
    public function updateOrderStatus(PickupOrder $order, OrderStatusEnum $status): PickupOrder
    {
        $order->setStatus($status);
        $this->entityManager->flush();

        $this->logger->info('Updated pickup order status', [
            'orderCode' => $order->getOrderCode(),
            'status' => $status->value,
        ]);

        return $order;
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetail(string $orderCode): ?PickupOrder
    {
        return $this->pickupOrderRepository->findByOrderCode($orderCode);
    }

    /**
     * 获取指定状态的订单列表
     */
    public function getOrdersByStatus(OrderStatusEnum $status): array
    {
        return $this->pickupOrderRepository->findByStatus($status);
    }

    /**
     * 取消取件订单
     *
     * @throws OrderCannotBeCancelledException  当订单状态不允许取消时
     * @throws OrderCancellationFailedException 当取消订单失败时
     */
    public function cancelPickupOrder(PickupOrder $order, string $reason): PickupOrder
    {
        if (!in_array($order->getStatus(), [OrderStatusEnum::CREATE, OrderStatusEnum::WAREHOUSE_ACCEPT])) {
            throw new OrderCannotBeCancelledException(sprintf('Order %s cannot be cancelled, current status: %s', $order->getOrderCode(), $order->getStatus()->value));
        }

        $this->cainiaoHttpClient->cancelPickupOrder($order, $reason);

        $order->setStatus(OrderStatusEnum::CANCELLED)
            ->setCancelReason($reason)
            ->setCancelTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Order cancelled successfully', [
            'orderCode' => $order->getOrderCode(),
            'reason' => $reason,
            'status' => OrderStatusEnum::CANCELLED->value,
        ]);

        return $order;
    }

    /**
     * 修改取件订单
     *
     * @throws OrderModificationFailedException 当订单状态不允许修改时
     */
    public function modifyPickupOrder(PickupOrder $order, array $data): PickupOrder
    {
        if (!in_array($order->getStatus(), [OrderStatusEnum::CREATE, OrderStatusEnum::WAREHOUSE_ACCEPT])) {
            throw new OrderModificationFailedException(sprintf('Order %s cannot be modified, current status: %s', $order->getOrderCode(), $order->getStatus()->value));
        }

        // 更新寄件人地址信息
        if (isset($data['senderName'], $data['senderPhone'], $data['senderAddress'])) {
            $order->getSenderInfo()
                ->setName($data['senderName'])
                ->setMobile($data['senderPhone'])
                ->setFullAddressDetail($data['senderAddress']);
        }

        // 更新收件人地址信息
        if (isset($data['receiverName'], $data['receiverPhone'], $data['receiverAddress'])) {
            $order->getReceiverInfo()
                ->setName($data['receiverName'])
                ->setMobile($data['receiverPhone'])
                ->setFullAddressDetail($data['receiverAddress']);
        }

        // 更新物品信息
        if (isset($data['itemType'])) {
            $order->setItemType(ItemTypeEnum::from($data['itemType']));
        }
        if (isset($data['weight'])) {
            $order->setWeight($data['weight']);
        }
        if (isset($data['itemQuantity'])) {
            $order->setItemQuantity($data['itemQuantity']);
        }
        if (isset($data['itemValue'])) {
            $order->setItemValue($data['itemValue']);
        }

        // 更新预约时间
        if (isset($data['expectPickupTimeStart'], $data['expectPickupTimeEnd'])) {
            $order->setExpectPickupTimeStart($data['expectPickupTimeStart'])
                ->setExpectPickupTimeEnd($data['expectPickupTimeEnd']);
        }

        // 更新备注
        if (isset($data['remark'])) {
            $order->setRemark($data['remark']);
        }

        // 调用菜鸟API修改订单
        $this->cainiaoHttpClient->modifyPickupOrder($order);

        $this->entityManager->flush();

        $this->logger->info('Modified pickup order', [
            'orderCode' => $order->getOrderCode(),
            'cainiaoOrderCode' => $order->getCainiaoOrderCode(),
        ]);

        return $order;
    }

    /**
     * 生成订单号
     */
    private function generateOrderCode(): string
    {
        return 'PK' . date('YmdHis') . rand(1000, 9999);
    }
}

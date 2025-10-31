<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Command;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\OrderException;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: self::NAME,
    description: '同步菜鸟上门取件订单的物流详情',
)]
#[WithMonologChannel(channel: 'cainiao_pickup')]
class SyncLogisticsDetailCommand extends Command
{
    public const NAME = 'cainiao:pickup:sync-logistics';

    /**
     * @param PickupOrderRepository $pickupOrderRepository
     * @param LogisticsDetailRepository $logisticsDetailRepository
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PickupOrderRepository $pickupOrderRepository,
        private readonly LogisticsDetailRepository $logisticsDetailRepository,
        private readonly CainiaoHttpClient $cainiaoHttpClient,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'order-code',
            null,
            InputOption::VALUE_OPTIONAL,
            '指定要同步的订单号，不指定则同步所有已取件的订单'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderCode = $input->getOption('order-code');

        try {
            if (null !== $orderCode) {
                if (!is_string($orderCode)) {
                    throw new \InvalidArgumentException('order-code must be a string');
                }
                $this->syncSingleOrder($orderCode, $output);
            } else {
                $this->syncAllOrders($output);
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logAndOutputError($e, $output);

            return Command::FAILURE;
        }
    }

    private function syncSingleOrder(string $orderCode, OutputInterface $output): void
    {
        $order = $this->pickupOrderRepository->findByOrderCode($orderCode);
        if (null === $order) {
            throw new OrderException(sprintf('Order not found: %s', $orderCode));
        }
        $this->syncLogistics($order, $output);
    }

    private function syncAllOrders(OutputInterface $output): void
    {
        $orders = $this->pickupOrderRepository->findByStatus(OrderStatusEnum::WAREHOUSE_CONFIRMED);
        foreach ($orders as $order) {
            try {
                $this->syncLogistics($order, $output);
            } catch (\Throwable $e) {
                $this->handleSyncError($order, $e, $output);
            }
        }
    }

    private function logAndOutputError(\Throwable $e, OutputInterface $output): void
    {
        $this->logger->error('Failed to sync logistics', [
            'error' => $e->getMessage(),
        ]);
        $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
    }

    private function handleSyncError(PickupOrder $order, \Throwable $e, OutputInterface $output): void
    {
        $this->logger->error('Failed to sync logistics', [
            'orderCode' => $order->getOrderCode(),
            'error' => $e->getMessage(),
        ]);
        $output->writeln(sprintf('<error>Failed to sync logistics for order %s: %s</error>', $order->getOrderCode(), $e->getMessage()));
    }

    private function syncLogistics(PickupOrder $order, OutputInterface $output): void
    {
        $this->validateOrder($order);

        $response = $this->cainiaoHttpClient->queryLogisticsDetail($order);
        $this->logisticsDetailRepository->deleteByOrder($order);

        /** @var array<array{status: string, desc: string, time: string, city?: string, area?: string, address?: string, courierInfo?: array{name?: string, mobile?: string}}> $logisticsDetails */
        $logisticsDetails = $response['logisticsDetails'];
        $this->saveLogisticsDetails($order, $logisticsDetails);
        $this->entityManager->flush();

        $this->outputSuccessMessage($order, $output);
    }

    private function validateOrder(PickupOrder $order): void
    {
        if (null === $order->getMailNo()) {
            throw new OrderException(sprintf('Order %s has no mail number', $order->getOrderCode()));
        }
    }

    /**
     * @param array<mixed> $details
     */
    private function saveLogisticsDetails(PickupOrder $order, array $details): void
    {
        foreach ($details as $detail) {
            if (!is_array($detail)) {
                $this->logger->warning('Invalid logistics detail format, skipping', [
                    'orderCode' => $order->getOrderCode(),
                    'detail' => $detail,
                ]);
                continue;
            }
            /** @var array<string, mixed> $validDetail */
            $validDetail = $detail;
            $logisticsDetail = $this->createLogisticsDetail($order, $validDetail);
            $this->entityManager->persist($logisticsDetail);
        }
    }

    /**
     * @param array<string, mixed> $detail
     */
    private function createLogisticsDetail(PickupOrder $order, array $detail): LogisticsDetail
    {
        // 验证必需字段
        $this->validateLogisticsDetailData($detail);

        $logisticsDetail = new LogisticsDetail();
        $logisticsDetail->setOrder($order);
        $logisticsDetail->setMailNo($order->getMailNo() ?? '');
        $logisticsDetail->setLogisticsStatus($this->castToString($detail['status']));
        $logisticsDetail->setLogisticsDescription($this->castToString($detail['desc']));
        $logisticsDetail->setLogisticsTime(new \DateTimeImmutable($this->castToString($detail['time'])));

        $this->setOptionalFields($logisticsDetail, $detail);

        return $logisticsDetail;
    }

    /**
     * @param array<string, mixed> $detail
     */
    private function setOptionalFields(LogisticsDetail $logisticsDetail, array $detail): void
    {
        $this->setLocationFields($logisticsDetail, $detail);
        $this->setCourierInfo($logisticsDetail, $detail);
    }

    /**
     * @param array<string, mixed> $detail
     */
    private function setLocationFields(LogisticsDetail $logisticsDetail, array $detail): void
    {
        if (isset($detail['city']) && is_string($detail['city']) && '' !== $detail['city']) {
            $logisticsDetail->setCity($detail['city']);
        }
        if (isset($detail['area']) && is_string($detail['area']) && '' !== $detail['area']) {
            $logisticsDetail->setArea($detail['area']);
        }
        if (isset($detail['address']) && is_string($detail['address']) && '' !== $detail['address']) {
            $logisticsDetail->setAddress($detail['address']);
        }
    }

    /**
     * @param array<string, mixed> $detail
     */
    private function setCourierInfo(LogisticsDetail $logisticsDetail, array $detail): void
    {
        if (!isset($detail['courierInfo']) || !is_array($detail['courierInfo'])) {
            return;
        }

        $courierInfo = $detail['courierInfo'];
        if (isset($courierInfo['name']) && is_string($courierInfo['name']) && '' !== $courierInfo['name']) {
            $logisticsDetail->setCourierName($courierInfo['name']);
        }
        if (isset($courierInfo['mobile']) && is_string($courierInfo['mobile']) && '' !== $courierInfo['mobile']) {
            $logisticsDetail->setCourierPhone($courierInfo['mobile']);
        }
    }

    private function outputSuccessMessage(PickupOrder $order, OutputInterface $output): void
    {
        $output->writeln(sprintf('Successfully synced logistics for order: %s', $order->getOrderCode()));

        $this->logger->info('Synced logistics detail', [
            'orderCode' => $order->getOrderCode(),
            'mailNo' => $order->getMailNo(),
        ]);
    }

    /**
     * @param array<string, mixed> $detail
     * @throws \InvalidArgumentException
     */
    private function validateLogisticsDetailData(array $detail): void
    {
        $requiredFields = ['status', 'desc', 'time'];
        foreach ($requiredFields as $field) {
            if (!isset($detail[$field])) {
                throw new \InvalidArgumentException(sprintf('Missing required field: %s', $field));
            }
            if (!is_string($detail[$field]) && !is_numeric($detail[$field])) {
                throw new \InvalidArgumentException(sprintf('Field %s must be string or numeric, got %s', $field, gettype($detail[$field])));
            }
        }
    }

    /**
     * 安全地将mixed类型转换为string
     */
    private function castToString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (null === $value) {
            return '';
        }

        throw new \InvalidArgumentException(sprintf('Cannot convert %s to string', gettype($value)));
    }
}

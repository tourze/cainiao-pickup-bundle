<?php

namespace CainiaoPickupBundle\Command;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\OrderException;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use Doctrine\ORM\EntityManagerInterface;
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
class SyncLogisticsDetailCommand extends Command
{
    public const NAME = 'cainiao:pickup:sync-logistics';
    
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
            if ($orderCode !== null) {
                // 同步指定订单
                $order = $this->pickupOrderRepository->findByOrderCode($orderCode);
                if ($order === null) {
                    throw new OrderException(sprintf('Order not found: %s', $orderCode));
                }
                $this->syncLogistics($order, $output);
            } else {
                // 同步所有已取件的订单
                $orders = $this->pickupOrderRepository->findByStatus(OrderStatusEnum::WAREHOUSE_CONFIRMED);
                foreach ($orders as $order) {
                    try {
                        $this->syncLogistics($order, $output);
                    } catch (\Throwable $e) {
                        $this->logger->error('Failed to sync logistics', [
                            'orderCode' => $order->getOrderCode(),
                            'error' => $e->getMessage(),
                        ]);
                        $output->writeln(sprintf('<error>Failed to sync logistics for order %s: %s</error>', $order->getOrderCode(), $e->getMessage()));
                    }
                }
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to sync logistics', [
                'error' => $e->getMessage(),
            ]);
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function syncLogistics(PickupOrder $order, OutputInterface $output): void
    {
        if ($order->getMailNo() === null) {
            throw new OrderException(sprintf('Order %s has no mail number', $order->getOrderCode()));
        }

        // 查询物流详情
        $response = $this->cainiaoHttpClient->queryLogisticsDetail($order);

        // 删除旧的物流详情
        $this->logisticsDetailRepository->deleteByOrder($order);

        // 保存新的物流详情
        foreach ($response['logisticsDetails'] ?? [] as $detail) {
            $logisticsDetail = new LogisticsDetail();
            $logisticsDetail->setOrder($order)
                ->setMailNo($order->getMailNo())
                ->setLogisticsStatus($detail['status'])
                ->setLogisticsDescription($detail['desc'])
                ->setLogisticsTime(new \DateTimeImmutable($detail['time']));

            // 设置可选字段
            if (!empty($detail['city'])) {
                $logisticsDetail->setCity($detail['city']);
            }
            if (!empty($detail['area'])) {
                $logisticsDetail->setArea($detail['area']);
            }
            if (!empty($detail['address'])) {
                $logisticsDetail->setAddress($detail['address']);
            }
            if (!empty($detail['courierInfo']['name'])) {
                $logisticsDetail->setCourierName($detail['courierInfo']['name']);
            }
            if (!empty($detail['courierInfo']['mobile'])) {
                $logisticsDetail->setCourierPhone($detail['courierInfo']['mobile']);
            }

            $this->entityManager->persist($logisticsDetail);
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('Successfully synced logistics for order: %s', $order->getOrderCode()));

        $this->logger->info('Synced logistics detail', [
            'orderCode' => $order->getOrderCode(),
            'mailNo' => $order->getMailNo(),
        ]);
    }
}

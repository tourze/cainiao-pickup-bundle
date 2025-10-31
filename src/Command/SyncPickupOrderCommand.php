<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Command;

use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Exception\OrderException;
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
    description: '同步菜鸟上门取件订单详情',
)]
#[WithMonologChannel(channel: 'cainiao_pickup')]
class SyncPickupOrderCommand extends Command
{
    public const NAME = 'cainiao:pickup:sync-orders';

    /**
     * @param PickupOrderRepository $pickupOrderRepository
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PickupOrderRepository $pickupOrderRepository,
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
            '指定要同步的订单号，不指定则同步所有未完成的订单'
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
                $this->syncAllUnfinishedOrders($output);
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
        $this->syncOrder($order);
        $output->writeln(sprintf('Successfully synced order: %s', $orderCode));
    }

    private function syncAllUnfinishedOrders(OutputInterface $output): void
    {
        $orders = $this->pickupOrderRepository->findUnfinishedOrders();
        foreach ($orders as $order) {
            try {
                $this->syncOrder($order);
                $output->writeln(sprintf('Successfully synced order: %s', $order->getOrderCode()));
            } catch (\Throwable $e) {
                $this->handleSyncError($order, $e, $output);
            }
        }
    }

    private function logAndOutputError(\Throwable $e, OutputInterface $output): void
    {
        $this->logger->error('Failed to sync orders', [
            'error' => $e->getMessage(),
        ]);
        $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
    }

    private function handleSyncError(PickupOrder $order, \Throwable $e, OutputInterface $output): void
    {
        $this->logger->error('Failed to sync order', [
            'orderCode' => $order->getOrderCode(),
            'error' => $e->getMessage(),
        ]);
        $output->writeln(sprintf('<error>Failed to sync order %s: %s</error>', $order->getOrderCode(), $e->getMessage()));
    }

    private function syncOrder(PickupOrder $order): void
    {
        // 查询订单详情
        $response = $this->cainiaoHttpClient->queryOrderDetail($order);

        // 更新订单信息
        $order->updateFromApiResponse($response);
        $this->entityManager->flush();

        $this->logger->info('Synced pickup order', [
            'orderCode' => $order->getOrderCode(),
            'cainiaoOrderCode' => $order->getCainiaoOrderCode(),
            'status' => $order->getStatus()->value,
        ]);
    }
}

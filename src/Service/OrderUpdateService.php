<?php

/**
 * @author Marcin Hubert <>
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Service;

use Webmozart\Assert\Assert;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Order\OrderTransitions;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Shipping\ShipmentTransitions;
use Sylius\Component\Core\OrderShippingTransitions;
use SM\Factory\FactoryInterface as StateMachineFactory;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\OrderRepository;
use Spinbits\SyliusBaselinkerPlugin\Model\OrderUpdateModel;

class OrderUpdateService
{
    private OrderRepository $orderRepository;
    private StateMachineFactory $stateMachineFactory;
    private EntityManagerInterface $orderEntityManager;

    public function __construct(
        OrderRepository $orderRepository,
        StateMachineFactory $stateMachineFactory,
        EntityManagerInterface $orderEntityManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->orderEntityManager = $orderEntityManager;
    }

    public function updateOrders(OrderUpdateModel $inputData): array
    {
        $orders = [];
        /** @var int|string $orderId */
        foreach ($inputData->getOrdersIds() as $orderId) {
            /** @var OrderInterface|null $order */
            $order = $this->orderRepository->findOneBy(['number' => $orderId]);
            Assert::isInstanceOf($order, OrderInterface::class, sprintf("Order %s was not found", (string) $orderId));

            $orders[] = $this->updateOrder($order, $inputData);
        }

        return $orders;
    }

    private function updateOrder(OrderInterface $order, OrderUpdateModel $inputData): OrderInterface
    {
        switch ($inputData->getUpdateType()) {
            case "paid":
                /** @var PaymentInterface|null $lastPayment */
                $lastPayment = $order->getLastPayment();
                if ($lastPayment === null) {
                    throw new \RuntimeException("Missing payment for order: " . (string) $order->getId());
                }

                /** @var PaymentInterface $lastPayment */
                $this->setComplete($lastPayment, (bool) $inputData->getUpdateValue());
                break;
            case "status":
                $this->updateOrderStatus($order, $inputData->getUpdateValue());
                break;
            default:
                // do nothing
                break;
        }
        $this->orderEntityManager->flush();
        return $order;
    }


    private function setComplete(PaymentInterface $payment, bool $paid): void
    {
        if (false === $paid) {
            return;
        }

        $paymentStateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);
        if ($paymentStateMachine->can(PaymentTransitions::TRANSITION_COMPLETE)) {
            $paymentStateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE);
        }
    }

    private function updateOrderStatus(OrderInterface $order, string $updateValue): void
    {
        $orderStateMachine = $this->stateMachineFactory->get($order, OrderTransitions::GRAPH);
        $orderShippingStateMachine = $this->stateMachineFactory->get($order, OrderShippingTransitions::GRAPH);
        $orderPaymentStateMachine = $this->stateMachineFactory->get($order, OrderPaymentTransitions::GRAPH);

        $paymentStateMachine = $this->stateMachineFactory->get($order->getLastPayment(), PaymentTransitions::GRAPH);
        $shipmentStateMachine = $this->stateMachineFactory->get($order->getShipments()->last(), ShipmentTransitions::GRAPH);

        switch ($updateValue) {
            case "1": // set order to new
                if ($orderStateMachine->can(OrderTransitions::TRANSITION_CREATE)) {
                    $orderStateMachine->apply(OrderTransitions::TRANSITION_CREATE);
                }
                break;
            case "2": // set order payment to paid
                if ($orderPaymentStateMachine->can(OrderPaymentTransitions::TRANSITION_PAY)) {
                    $orderPaymentStateMachine->apply(OrderPaymentTransitions::TRANSITION_PAY);
                }
                if ($paymentStateMachine->can(PaymentTransitions::TRANSITION_COMPLETE)) {
                    $paymentStateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE);
                }
                break;
            case "3": // set set order to shipped
                if ($orderShippingStateMachine->can(OrderShippingTransitions::TRANSITION_SHIP)) {
                    $orderShippingStateMachine->apply(OrderShippingTransitions::TRANSITION_SHIP);
                }
                if ($shipmentStateMachine->can(ShipmentTransitions::TRANSITION_SHIP)) {
                    $shipmentStateMachine->apply(ShipmentTransitions::TRANSITION_SHIP);
                }
                break;
            case "4": // set shipping to delivered
                if ($orderShippingStateMachine->can('deliver')) {
                    $orderShippingStateMachine->apply('deliver');
                }
                if ($shipmentStateMachine->can('deliver')) {
                    $shipmentStateMachine->apply('deliver');
                }                
                break;
            case "5": // set order to cancelled
                if ($orderStateMachine->can(OrderTransitions::TRANSITION_CANCEL)) {
                    $orderStateMachine->apply(OrderTransitions::TRANSITION_CANCEL);
                } // ????
                break;
            default:
                break;
        }
    }
}

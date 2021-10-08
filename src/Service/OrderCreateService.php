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

use Spinbits\BaselinkerSdk\Model\OrderAddModel;
use Spinbits\BaselinkerSdk\Model\ProductModel;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\Address;
use SM\Factory\Factory as StateMachineFactory;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\CustomerRepository;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\OrderRepository;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Currency\Context\CurrencyContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Payment\Factory\PaymentFactoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Resource\Factory\Factory;
use Webmozart\Assert\Assert;

class OrderCreateService
{
    private Factory $orderFactory;
    private CartItemFactoryInterface $orderItemFactory;
    private Factory $customerFactory;
    private CustomerRepository $customerRepository;
    private OrderRepository $orderRepository;
    private ProductVariantRepository $productVariantRepository;
    private LocaleContextInterface $localeContext;
    private CurrencyContextInterface $currencyContext;
    private OrderItemQuantityModifierInterface $orderItemQuantityModifier;
    private OrderProcessorInterface $orderProcessor;
    private ChannelContextInterface $channelContext;
    private StateMachineFactory $stateMachineFactory;
    private PaymentFactoryInterface $paymentFactory;
    private PaymentMethodRepositoryInterface $paymentMethodRepository;
    private ?string $paymentMethodCode = null;

    public function __construct(
        Factory $orderFactory,
        CartItemFactoryInterface $orderItemFactory,
        Factory $customerFactory,
        PaymentFactoryInterface $paymentFactory,
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        ProductVariantRepository $productVariantRepository,
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        LocaleContextInterface $localeContext,
        CurrencyContextInterface $currencyContext,
        ChannelContextInterface $channelContext,
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        OrderProcessorInterface $orderProcessor,
        StateMachineFactory $stateMachineFactory,
        ?string $paymentMethodCode = null
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->localeContext = $localeContext;
        $this->currencyContext = $currencyContext;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->orderProcessor = $orderProcessor;
        $this->customerFactory = $customerFactory;
        $this->channelContext = $channelContext;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->paymentFactory = $paymentFactory;
        $this->paymentMethodRepository = $paymentMethodRepository;

        if (null === $paymentMethodCode) {
            $this->paymentMethodCode = $this->getDefaultPaymentMethodCode();
        }
    }

    public function createOrder(OrderAddModel $orderAddModel): OrderInterface
    {
        Assert::notNull($orderAddModel->getBaselinkerId(), sprintf("BaselinkerId can not be empty."));

        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneBy(['baselinkerOrderId' => $orderAddModel->getBaselinkerId()]);
        if (null === $order) {
            /** @var Order $order */
            $order = $this->orderFactory->createNew();

            $order->setChannel($this->channelContext->getChannel());
            $order->setLocaleCode($this->localeContext->getLocaleCode());
            $order->setCurrencyCode($this->currencyContext->getCurrencyCode());

            $customer = $this->getCustomer($orderAddModel);
            $address = $this->getAddress($orderAddModel, $customer);
            $payment = $this->getPayment((string) $this->paymentMethodCode, (string) $order->getCurrencyCode());

            $order->setCustomer($customer);
            $order->setShippingAddress($address);
            $order->setBillingAddress(clone $address);
            $order->addPayment($payment);

            $modelProducts = $orderAddModel->getProducts();
            if ($modelProducts !== null) {
                foreach ($modelProducts as $product) {
                    $orderItem = $this->getOrderItem($product);
                    $order->addItem($orderItem);
                }
            }

            $this->orderProcessor->process($order);

            $this->orderRepository->add($order);
            $this->checkout($order);
        }

        $this->markPayment($order, (bool) $orderAddModel->getPaid());
        $this->orderRepository->add($order);

        return $order;
    }

    private function getCustomer(OrderAddModel $orderAddModel): CustomerInterface
    {
        /** @var CustomerInterface|null $customer */
        $customer = $this->customerRepository->findOneBy(['email' => $orderAddModel->getEmail()]);
        if (null === $customer) {
            /** @var CustomerInterface $customer */
            $customer = $this->customerFactory->createNew();
            $customer->setEmail($orderAddModel->getEmail());
            $customer->setPhoneNumber($orderAddModel->getPhone());
        }

        /** @var CustomerInterface $customer */
        return $customer;
    }

    private function getAddress(OrderAddModel $orderAddModel, CustomerInterface $customer): Address
    {
        $address = new Address();
        $address->setFirstName($orderAddModel->getDeliveryFullname() ?? "");
        $address->setLastName("");
        $address->setPostcode("");
        $address->setCustomer($customer);
        $address->setPhoneNumber($orderAddModel->getPhone() ?? "");
        $address->setStreet($orderAddModel->getDeliveryAddress() ?? "");
        $address->setCity($orderAddModel->getDeliveryCity() ?? "");
        $address->setCountryCode($orderAddModel->getDeliveryCountryCode() ?? "");

        return $address;
    }

    private function getOrderItem(ProductModel $product): OrderItemInterface
    {
        /** @var ProductVariantInterface|null $variant */
        $variant = $this->productVariantRepository->find($product->getVariantId());

        $message = sprintf("Product variant with %s id was not found!", $product->getVariantId());
        Assert::isInstanceOf($variant, ProductVariantInterface::class, $message);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->orderItemFactory->createNew();
        $orderItem->setVariant($variant);
        $this->orderItemQuantityModifier->modify($orderItem, $product->getQuantity());

        return $orderItem;
    }

    private function getPayment(string $paymentMethodCode, string $currencyCode): PaymentInterface
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->findOneBy(['code' => $paymentMethodCode]);
        /** @var Payment $payment */
        $payment = $this->paymentFactory->createNew();
        $payment->setMethod($paymentMethod);
        $payment->setCurrencyCode($currencyCode);

        return $payment;
    }

    private function checkout(OrderInterface $order): void
    {
        $stateMachine = $this->stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH);
        $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_ADDRESS);
        $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_SKIP_SHIPPING);
        $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT);
        if ($stateMachine->can(OrderCheckoutTransitions::TRANSITION_COMPLETE)) {
            $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_COMPLETE);
        }
    }

    private function markPayment(OrderInterface $order, bool $paid): void
    {
        /** @var Order $order */
        if (false === $paid) {
            return;
        }

        /** @var PaymentInterface|null $lastPayment */
        $lastPayment = $order->getLastPayment();
        if ($lastPayment === null) {
            throw new \RuntimeException("Missing payment for order: " . (string) $order->getId());
        }

        /** @var PaymentInterface $lastPayment */
        $paymentStateMachine = $this->stateMachineFactory->get($lastPayment, PaymentTransitions::GRAPH);
        if ($paymentStateMachine->can(PaymentTransitions::TRANSITION_COMPLETE)) {
            $paymentStateMachine->apply(PaymentTransitions::TRANSITION_COMPLETE);
        }
    }

    private function getDefaultPaymentMethodCode(): string
    {
        /** @var PaymentMethod[] $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findAll();
        Assert::keyExists($paymentMethods, 0, 'No payment method configured.');
        return (string) $paymentMethods[0]->getCode();
    }
}

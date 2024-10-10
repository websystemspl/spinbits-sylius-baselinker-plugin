<?php

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Handler;

use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;

class PaymentMethodsListActionHandler implements HandlerInterface
{
    private PaymentMethodRepositoryInterface $paymentMethodRepository;
    private ChannelInterface $channel;

    public function __construct(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ChannelContextInterface $channelContext
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->channel = $channelContext->getChannel();
    }

    public function handle(Input $input): array
    {
        $paymentMethods = $this->paymentMethodRepository->findEnabledForChannel($this->channel);
        $return = [];
        /** @var PaymentMethod[] $paymentMethods */
        foreach ($paymentMethods as $paymentMethod) {
            $return[(int) $paymentMethod->getId()] = $paymentMethod->getName();
        }
        return $return;
    }
}

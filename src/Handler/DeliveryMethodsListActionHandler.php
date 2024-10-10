<?php

/**
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Handler;

use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;

class DeliveryMethodsListActionHandler implements HandlerInterface
{
    private ShippingMethodRepositoryInterface $shippingMethodRepository;
    private ChannelInterface $channel;

    public function __construct(
        ShippingMethodRepositoryInterface $shippingMethodRepository,
        ChannelContextInterface $channelContext
    ) {
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->channel = $channelContext->getChannel();
    }

    public function handle(Input $input): array
    {
        $shippingMethods = $this->shippingMethodRepository->findEnabledForChannel($this->channel);
        $return = [];
        /** @var ShippingMethod[] $shippingMethods */
        foreach ($shippingMethods as $shippingMethod) {
            $return[(int) $shippingMethod->getId()] = $shippingMethod->getName();
        }
        return $return;
    }
}

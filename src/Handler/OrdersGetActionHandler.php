<?php

/**
 * @author Marcin Hubert <>
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Handler;

use Pagerfanta\Pagerfanta;
use Sylius\Component\Core\Model\Order;
use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Spinbits\SyliusBaselinkerPlugin\Filter\OrderListFilter;
use Spinbits\SyliusBaselinkerPlugin\Filter\ProductListFilter;
use Spinbits\SyliusBaselinkerPlugin\Mapper\ListOrderMapper;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Spinbits\SyliusBaselinkerPlugin\Repository\BaseLinkerOrderRepositoryInterface;

class OrdersGetActionHandler implements HandlerInterface
{
    private ListOrderMapper $mapper;
    private BaseLinkerOrderRepositoryInterface $orderRepository;
    private ChannelContextInterface $channelContext;

    public function __construct(
        ListOrderMapper $mapper,
        BaseLinkerOrderRepositoryInterface $orderRepository,
        ChannelContextInterface $channel
    ) {
        $this->mapper = $mapper;
        $this->orderRepository = $orderRepository;
        $this->channelContext = $channel;
    }

    public function handle(Input $input): array
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $filter = new OrderListFilter($input, $channel);

        $paginator = $this->orderRepository->fetchBaseLinkerData($filter);
        $return = [];
        /** @var Order[] $paginator */
        foreach ($paginator as $order) {
            $return[$order->getNumber()] = $this->mapper->map($order, $channel);
        }
        /** @var Pagerfanta $paginator */
        // $return['pages'] = $paginator->getNbPages();
        return  $return;
    }
}

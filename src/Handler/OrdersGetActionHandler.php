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
use Sylius\Component\Core\Model\Product;
use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Spinbits\SyliusBaselinkerPlugin\Filter\OrderListFilter;
use Spinbits\SyliusBaselinkerPlugin\Filter\ProductListFilter;
use Spinbits\SyliusBaselinkerPlugin\Mapper\ListProductMapper;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Spinbits\SyliusBaselinkerPlugin\Repository\BaseLinkerProductRepositoryInterface;

class OrdersGetActionHandler implements HandlerInterface
{
    private ListProductMapper $mapper;
    private BaseLinkerOrderRepositoryInterface $productRepository;
    private ChannelContextInterface $channelContext;

    public function __construct(
        ListProductMapper $mapper,
        BaseLinkerOrderRepositoryInterface $productRepository,
        ChannelContextInterface $channel
    ) {
        $this->mapper = $mapper;
        $this->productRepository = $productRepository;
        $this->channelContext = $channel;
    }

    public function handle(Input $input): array
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $filter = new OrderListFilter($input, $channel);

        $paginator = $this->productRepository->fetchBaseLinkerData($filter);
        $return = [];
        /** @var Product[] $paginator */
        foreach ($paginator as $product) {
            /** @var ProductVariantInterface $variant */
            foreach ($this->mapper->map($product, $channel) as $variant) {
                $return[(int) $product->getId()] = $variant;
            }
        }
        /** @var Pagerfanta $paginator */
        $return['pages'] = $paginator->getNbPages();
        return  $return;
    }
}

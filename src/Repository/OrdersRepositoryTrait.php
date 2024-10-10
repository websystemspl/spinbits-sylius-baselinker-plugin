<?php

/**
 * @author Marcin Hubert <>
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Repository;

use Pagerfanta\Pagerfanta;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Sylius\Component\Core\Model\PaymentInterface;
use Pagerfanta\Exception\LessThan1CurrentPageException;
use Spinbits\SyliusBaselinkerPlugin\Filter\AbstractFilter;
use Spinbits\SyliusBaselinkerPlugin\Filter\PageOnlyFilter;
use Spinbits\SyliusBaselinkerPlugin\Filter\OrderListFilter;
use Spinbits\SyliusBaselinkerPlugin\Filter\ProductDataFilter;
use Spinbits\SyliusBaselinkerPlugin\Filter\PaginatorFilterInterface;

trait OrdersRepositoryTrait
{
    private bool $pricingsJoined = false;
    private bool $translationsJoined = false;

    public function fetchBaseLinkerData(OrderListFilter $filter): Pagerfanta
    {
        $queryBuilder = $this->prepareBaseLinkerQueryBuilder($filter);
        $this->applyFilters($queryBuilder, $filter);

        return $this->appendPaginator($filter, $queryBuilder);
    }

    private function prepareBaseLinkerQueryBuilder(AbstractFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('o');

        $queryBuilder
            ->distinct()
            ->andWhere(':channel = o.channel')
            ->andWhere('o.checkoutCompletedAt IS NOT NULL')
            ->setParameter('channel', $filter->getChannel());

        return $queryBuilder;
    }

    private function appendPaginator(PaginatorFilterInterface $filter, QueryBuilder $queryBuilder): Pagerfanta
    {
        $paginator = new Pagerfanta(new QueryAdapter($queryBuilder));
        $paginator->setNormalizeOutOfRangePages(true);
        $paginator->setMaxPerPage($filter->getLimit());
        try {
            $paginator->setCurrentPage($filter->getPage());
        } catch (LessThan1CurrentPageException $exception) {
            // ignore
        }

        return $paginator;
    }

    private function applyFilters(QueryBuilder $queryBuilder, OrderListFilter $filter): void
    {
        if ($filter->hasId()) {
            $this->filterById($queryBuilder, (string) $filter->getId());
        }

        if ($filter->hasTimeFrom()) {
            $this->filterTimeFrom($queryBuilder, (int) $filter->getTimeFrom());
        }

        if ($filter->hasIdFrom()) {
            $this->filterByIdFrom($queryBuilder, (int) $filter->getIdFrom());
        }
        if ($filter->hasOnlyPaid() && (bool) $filter->getOnlyPaid()) {
            $this->filterOnlyPaid($queryBuilder);
        }
    }

    private function filterById(QueryBuilder $queryBuilder, string $id): void
    {
        $queryBuilder->andWhere('o.id = :id');
        $queryBuilder->setParameter('id', $id);
    }

    private function filterTimeFrom(QueryBuilder $queryBuilder, int $timeFrom): void
    {
        $dateTimeFrom = (new \DateTime())->setTimestamp($timeFrom);
    
        $queryBuilder
            ->andWhere('o.checkoutCompletedAt >= :timeFrom')
            ->setParameter('timeFrom', $dateTimeFrom);
    }

    private function filterByIdFrom(QueryBuilder $queryBuilder, int $idFrom): void
    {
        $queryBuilder
            ->andWhere('o.id >= :idFrom')
            ->setParameter('idFrom', $idFrom);
    }

    private function filterOnlyPaid(QueryBuilder $queryBuilder): void
    {
        $queryBuilder
            ->andWhere('o.paymentState = :completed_state')
            ->setParameter('completed_state', PaymentInterface::STATE_COMPLETED);
    }

    private function filterByIds(QueryBuilder $queryBuilder, array $ids): void
    {
        $queryBuilder->andWhere('o.id IN (:ids)');
        $queryBuilder->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);
    }
}

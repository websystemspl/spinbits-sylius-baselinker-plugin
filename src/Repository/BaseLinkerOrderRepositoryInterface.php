<?php

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Repository;

use Pagerfanta\Pagerfanta;
use Spinbits\SyliusBaselinkerPlugin\Filter\OrderListFilter;

interface BaseLinkerOrderRepositoryInterface
{
    public function fetchBaseLinkerData(OrderListFilter $filter): Pagerfanta;
}

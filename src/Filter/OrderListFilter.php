<?php

/**
 * @author Marcin Hubert <hubert.m.j@gmail.com>
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Filter;

use Spinbits\SyliusBaselinkerPlugin\Rest\Input;

class OrderListFilter extends AbstractFilter implements PaginatorFilterInterface
{
    public function getLimit(): int
    {
        $limit = (int) $this->get('filter_limit', 100);

        if ($limit > 200) {
            return 200;
        }

        if ($limit < 1) {
            return 100;
        }

        return $limit;
    }

    public function getPage(): int
    {
        $page = (int) $this->get('page', 1);
        return $page < 1 ? 1 : $page;
    }

    public function hasId(): bool
    {
        return '' !== $this->getId();
    }

    public function getId(): ?string
    {
        return (string) $this->get('order_id');
    }

    public function hasTimeFrom(): bool
    {
        return null !== $this->getTimeFrom();
    }

    public function getTimeFrom(): ?int
    {
        return $this->getNullOrInteger('time_from');
    }

    public function hasIdFrom(): bool
    {
        return null !== $this->getIdFrom();
    }

    public function getIdFrom(): ?int
    {
        return $this->getNullOrInteger('id_from');
    }

    public function hasOnlyPaid(): bool
    {
        return null !== $this->getOnlyPaid();
    }

    public function getOnlyPaid(): ?bool
    {
        return $this->getNullOrBoolean('only_paid');
    }

    public function hasSort(): bool
    {
        return count($this->getSort()) > 0;
    }

    public function getSort(): array
    {
        $filter = (string) $this->get('filter_sort');
        return strlen($filter) > 0 ? explode(" ", trim($filter)) : [];
    }

    private function getNullOrInteger(string $parameter): ?int
    {
        $filter = $this->get($parameter);
        return (null === $filter || '' === $filter ) ? null : (int) $filter;
    }

    private function getNullOrBoolean(string $parameter): ?bool
    {
        $filter = $this->get($parameter);
        return (null === $filter || '' === $filter ) ? null : (bool) $filter;
    }
}

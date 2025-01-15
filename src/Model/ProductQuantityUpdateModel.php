<?php

/**
 * @author Marcin Hubert <hubert.m.j@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Model;

class ProductQuantityUpdateModel
{
    private string $product_id;
    private string $variant_id;
    private int $quantity;
    private bool $is_set;
    private bool $is_change;

    public function __construct(string $product_id, string $variant_id, int $quantity, string $operation)
    {
        $this->product_id = $product_id;
        $this->variant_id = $variant_id;
        $this->quantity = $quantity;
        $this->is_set = $operation === 'set';
        $this->is_change = $operation === 'change';
    }

    public function getProductId(): ?string
    {
        return $this->product_id;
    }

    public function getVariantId(): ?string
    {
        return $this->variant_id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getIsSet(): bool
    {
        return $this->is_set;
    }

    public function getIsChange(): bool
    {
        return $this->is_change;
    }
}

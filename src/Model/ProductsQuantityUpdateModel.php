<?php

/**
 * @author Marcin Hubert <hubert.m.j@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Model;

use Spinbits\SyliusBaselinkerPlugin\Rest\Input;

class ProductsQuantityUpdateModel
{
    private array $productQuantityUpdateModels;

    public function __construct(Input $input)
    {
        $this->productQuantityUpdateModels = [];
        foreach($input->get('products') as $productData) {
            if(isset($productData['variant_id']) && isset($productData['quantity']) && isset($productData['operation'])) {
                $this->productQuantityUpdateModels[] = new ProductQuantityUpdateModel($productData['variant_id'], intval($productData['quantity']), $productData['operation']);
            }
        }
    }

    public function getProductQuantityUpdateModels(): array
    {
        return $this->productQuantityUpdateModels;
    }
}
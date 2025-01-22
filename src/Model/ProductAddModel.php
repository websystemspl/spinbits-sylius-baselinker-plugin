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
use Symfony\Component\Validator\Constraints as Assert;

class ProductAddModel
{
    // private string $product_id;
    private string $sku;
    private string $ean;
    private string $name;
    private int $quantity;
    private float $price;
    private int $tax;
    private float $weight;
    private float $height;
    private float $length;
    private float $width;
    private string $description;
    private string $category_code;
    // private string $description_extra1;
    // private string $description_extra2;
    // private string $description_extra3;
    // private string $description_extra4;
    // private string $man_name;//Manufacturer name
    // private array $images;
    // private array $features;
    // private array $tags;


    public function __construct(Input $input)
    {
        // $this->product_id = (string) $input->get('product_id');
        $this->sku = (string) $input->get('sku');
        $this->ean = (string) $input->get('ean');
        $this->name = (string) $input->get('name');
        $this->quantity = (int) $input->get('quantity');
        $this->price = (float) $input->get('price');
        $this->tax = (int) $input->get('tax');
        $this->weight = (float) $input->get('weight');
        $this->height = (float) $input->get('height');
        $this->length = (float) $input->get('length');
        $this->width = (float) $input->get('width');
        $this->description = (string) $input->get('description');
        $this->category_code = (string) $input->get('category_id');
        // $this->description_extra1 = (string) $input->get('description_extra1');
        // $this->description_extra2 = (string) $input->get('description_extra2');
        // $this->description_extra3 = (string) $input->get('description_extra3');
        // $this->description_extra4 = (string) $input->get('description_extra4');
        // $this->man_name = (string) $input->get('man_name');
        // $this->images = $this->decodeImages($input->get('images'));
        // $this->features = $this->decodeFeatures($input->get('features'));
        // $this->tags = $this->decodeTags($input->get('tags'));
    }

    /**
     * Get the value of sku
     *
     * @return mixed
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Get the value of ean
     *
     * @return mixed
     */
    public function getEan(): string
    {
        return $this->ean;
    }

    /**
     * Get the value of name
     *
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the value of quantity
     *
     * @return mixed
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Get the value of price
     *
     * @return mixed
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Get the value of tax
     *
     * @return mixed
     */
    public function getTax(): int
    {
        return $this->tax;
    }

    /**
     * Get the value of weight
     *
     * @return mixed
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * Get the value of height
     *
     * @return mixed
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * Get the value of length
     *
     * @return mixed
     */
    public function getLength(): float
    {
        return $this->length;
    }

    /**
     * Get the value of width
     *
     * @return mixed
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * Get the value of description
     *
     * @return mixed
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the value of category_code
     *
     * @return mixed
     */
    public function getCategoryCode(): string
    {
        return $this->category_code;
    }
}

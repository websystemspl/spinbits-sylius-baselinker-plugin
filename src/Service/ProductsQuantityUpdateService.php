<?php

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Service;

use Webmozart\Assert\Assert;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Spinbits\SyliusBaselinkerPlugin\Model\ProductQuantityUpdateModel;
use Spinbits\SyliusBaselinkerPlugin\Model\ProductsQuantityUpdateModel;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;

class ProductsQuantityUpdateService
{
    private ProductVariantRepository $productVariantRepository;
    private EntityManagerInterface $productEntityManager;

    public function __construct(
        ProductVariantRepository $productVariantRepository,
        EntityManagerInterface $productEntityManager
    ) {
        $this->productVariantRepository = $productVariantRepository;
        $this->productEntityManager = $productEntityManager;
    }

    public function updateProductsQuantity(ProductsQuantityUpdateModel $productsQuantityUpdateModel): int
    {
        $updatedProductNumber = 0;
        /** @var ProductQuantityUpdateModel $productQuantityUpdateModel */
        foreach ($productsQuantityUpdateModel->getProductQuantityUpdateModels() as $productQuantityUpdateModel) {
            /** @var ProductVariantInterface|null $product */
            if('0' === $productQuantityUpdateModel->getVariantId()) {
                $product = $this->productVariantRepository->findOneBy(['product' => $productQuantityUpdateModel->getProductId()]);
                Assert::isInstanceOf($product, ProductVariantInterface::class, sprintf("Product %s was not found", (string) $productQuantityUpdateModel->getProductId()));
            } else {
                $product = $this->productVariantRepository->find($productQuantityUpdateModel->getVariantId());
                Assert::isInstanceOf($product, ProductVariantInterface::class, sprintf("Product variant %s was not found", (string) $productQuantityUpdateModel->getVariantId()));
            }

            $updatedProductNumber += ($this->updateProductQuantity($product, $productQuantityUpdateModel) ? 1 : 0);
        }

        return $updatedProductNumber;
    }

    private function updateProductQuantity(ProductVariantInterface $product, ProductQuantityUpdateModel $productQuantityUpdateModel): bool
    {
        if($productQuantityUpdateModel->getIsSet()) {
            $product->setOnHand($productQuantityUpdateModel->getQuantity());
            $this->productEntityManager->flush();
            return true;
        } else if($productQuantityUpdateModel->getIsChange()) {
            $product->setOnHand($product->getOnHand() + $productQuantityUpdateModel->getQuantity());
            $this->productEntityManager->flush();
            return true;
        } else {
            return false;
        }
    }
}

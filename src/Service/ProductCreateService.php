<?php

/**
 * @author Marcin Hubert <>
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Service;

use App\Entity\Channel\Channel;
use App\Entity\Product\Product;
use App\Entity\Product\ProductTaxon;
use App\Entity\Taxation\TaxCategory;
use App\Entity\Channel\ChannelPricing;
use App\Entity\Product\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Shipping\ShippingCategory;
use App\Repository\Taxon\TaxonRepository;
use App\Entity\Product\ProductTranslation;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Spinbits\SyliusBaselinkerPlugin\Model\ProductAddModel;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository as TaxRateRepository;

class ProductCreateService
{
    private EntityManagerInterface $entityManager;
    private ProductVariantRepository $productVariantRepository;
    private TaxonRepository $taxonRepository;
    private TaxRateRepository $taxRateRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductVariantRepository $productVariantRepository,
        TaxonRepository $taxonRepository,
        TaxRateRepository $taxRateRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productVariantRepository = $productVariantRepository;
        $this->taxonRepository = $taxonRepository;
        $this->taxRateRepository = $taxRateRepository;
    }

    public function createProduct(ProductAddModel $productAddModel): ?int
    {
        if (null === $this->productVariantRepository->findOneBy(['code' => $productAddModel->getSku()])) {
            $product = new Product();
            $product->setCode($productAddModel->getSku());
            $product->setEnabled(true);
            $product->setCreatedAt(new \DateTime());
            $product->setUpdatedAt(new \DateTime());
            $product->setColor('');
            $product->addChannel($this->entityManager->getReference(Channel::class, 1));
            

            $slugger = new AsciiSlugger();
            $translation = new ProductTranslation();
            $translation->setLocale('pl_PL');
            $translation->setName($productAddModel->getName());
            $translation->setSlug(strval($slugger->slug($productAddModel->getName())));
            $translation->setDescription($productAddModel->getName());
            $product->addTranslation($translation);
            $this->entityManager->persist($product);

            if(($taxon = $this->taxonRepository->findOneBy(['code' => $productAddModel->getCategoryCode()])) !== null) {
                $productTaxon = new ProductTaxon();
                $productTaxon->setProduct($product);
                $productTaxon->setTaxon($taxon);
                $this->entityManager->persist($productTaxon);
                
                $product->setMainTaxon($taxon);    
                $this->entityManager->persist($product);
            }


            $productVariant = new ProductVariant();
            $productVariant->setCode($productAddModel->getSku());
            $productVariant->setEan13($productAddModel->getEan());
            $productVariant->setProduct($product);
            $productVariant->setShippingCategory($this->entityManager->getReference(ShippingCategory::class, 1));
            $productVariant->setOnHand($productAddModel->getQuantity());
            $productVariant->setWidth($productAddModel->getWidth());
            $productVariant->setHeight($productAddModel->getHeight());
            $productVariant->setDepth($productAddModel->getLength());
            $productVariant->setWeight($productAddModel->getWeight());
            if(($taxRate = $this->taxRateRepository->findOneBy(['amount' => floatval($productAddModel->getTax() / 100)])) !== null) {
                $productVariant->setTaxCategory($this->entityManager->getReference(TaxCategory::class, $taxRate->getCategory()->getId()));
            }

            $this->entityManager->persist($productVariant);

            $price = new ChannelPricing();
            $price->setProductVariant($productVariant);
            $price->setPrice(intval(round($productAddModel->getPrice() * 100)));
            $price->setChannelCode('bambiboo');

            $this->entityManager->persist($price);
            
            $this->entityManager->flush();

            return $product->getId();
        }
        return null;
    }
}

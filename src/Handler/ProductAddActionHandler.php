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

use Spinbits\SyliusBaselinkerPlugin\Service\ProductCreateService;
use Spinbits\SyliusBaselinkerPlugin\Handler\HandlerInterface;
use Spinbits\SyliusBaselinkerPlugin\Model\ProductAddModel;
use Spinbits\SyliusBaselinkerPlugin\Rest\Exception\InvalidArgumentException;
use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductAddActionHandler implements HandlerInterface
{
    private ValidatorInterface $validator;
    private ProductCreateService $productCreateService;

    public function __construct(ValidatorInterface $validator, ProductCreateService $productCreateService)
    {
        $this->validator = $validator;
        $this->productCreateService = $productCreateService;
    }

    /**
     * @param Input $input
     * @return array
     * @throws InvalidArgumentException
     */
    public function handle(Input $input): array
    {
        $productAddModel = new ProductAddModel($input);

        $result = $this->validator->validate($productAddModel);
        $this->assertIsValid($result);

        $productId = $this->productCreateService->createProduct($productAddModel);

        return ['product_id' => $productId];
    }

    /**
     * @param ConstraintViolationListInterface $result
     *
     * @throws InvalidArgumentException
     */
    private function assertIsValid(ConstraintViolationListInterface $result): void
    {
        if (count($result) < 1) {
            return;
        }

        /** @var ConstraintViolation[] $result */
        $errors = [];
        foreach ($result as $violation) {
            $errors[] = $violation->getPropertyPath() . ": " . $violation->getMessage();
        }

        throw new InvalidArgumentException('validation failed: ' . implode("; ", $errors));
    }
}

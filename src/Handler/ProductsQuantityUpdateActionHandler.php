<?php

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Handler;

use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Spinbits\SyliusBaselinkerPlugin\Model\ProductsQuantityUpdateModel;
use Spinbits\SyliusBaselinkerPlugin\Service\ProductsQuantityUpdateService;

class ProductsQuantityUpdateActionHandler implements HandlerInterface
{
    private ValidatorInterface $validator;
    private ProductsQuantityUpdateService $productsQuantityUpdateService;

    public function __construct(ValidatorInterface $validator, ProductsQuantityUpdateService $productsQuantityUpdateService)
    {
        $this->validator = $validator;
        $this->productsQuantityUpdateService = $productsQuantityUpdateService;
    }

    /**
     * @param Input $input
     * @return array
     * @throws InvalidArgumentException
     */
    public function handle(Input $input): array
    {
        $input = new ProductsQuantityUpdateModel($input);
        $result = $this->validator->validate($input);
        $this->assertIsValid($result);

        $numberOfUpdatedProducts = $this->productsQuantityUpdateService->updateProductsQuantity($input);

        return ['counter' => $numberOfUpdatedProducts];
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

        $errors = [];
        /** @var ConstraintViolation[] $result */
        foreach ($result as $violation) {
            $errors[] = $violation->getPropertyPath() . ": " . $violation->getMessage();
        }

        throw new \InvalidArgumentException('validation failed: ' . implode("; ", $errors));
    }
}

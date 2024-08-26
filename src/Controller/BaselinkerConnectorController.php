<?php

/**
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Symfony\Component\HttpFoundation\JsonResponse;
use Spinbits\SyliusBaselinkerPlugin\RequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Spinbits\SyliusBaselinkerPlugin\Handler\FileVersionActionHandler;

final class BaselinkerConnectorController extends AbstractController
{
    private RequestHandler $requestHandler;

    /**
     * @param RequestHandler $requestHandler
     * @param ContainerInterface $container
     */
    public function __construct(RequestHandler $requestHandler, ContainerInterface $container)
    {
        $this->container = $container;
        $this->requestHandler = $requestHandler;
    }

    public function connectorAction(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return new JsonResponse([
                'error' => true,
                'error_code' => 'no_password',
                'error_text' => 'Wrong request'
            ]);
        }

        /** @var array<string, mixed> $input */
        $input = $request->request->all();

        //handle baselinker test connection
        if(empty($input)) {
            return new JsonResponse((new FileVersionActionHandler())->handle(new Input($input)), 200);
        } else {
            $input = new Input($input);
            $response = $this->requestHandler->handle($input);
            return new JsonResponse($response->content(), $response->code() < 100 ? 500 : $response->code());
        }
    }
}

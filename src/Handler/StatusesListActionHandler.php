<?php

/**
 * @author Jakub Lech <info@smartbyte.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);
namespace Spinbits\SyliusBaselinkerPlugin\Handler;
use Sylius\Component\Core\OrderCheckoutStates;
use Spinbits\SyliusBaselinkerPlugin\Rest\Input;
use Spinbits\SyliusBaselinkerPlugin\Handler\HandlerInterface;
class StatusesListActionHandler implements HandlerInterface
{
    public function handle(Input $input): array
    {
        return [
            1 => 'Nowe',
            2 => 'Opłacone',
            3 => 'Wysłane',
            4 => 'Dostarczone',
            5 => 'Anulowane',
        ];
    }
}
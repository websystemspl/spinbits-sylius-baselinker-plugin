<?php


declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Mapper;

use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\OrderPaymentStates;

class ListOrderMapper
{
    public function map(Order $order, ChannelInterface $channel): array
    {
        $shippingAddress = $order->getShippingAddress() ?? $order->getBillingAddress();
        $mapped = [
            'delivery_fullname' => $shippingAddress?->getFullName()??'',
            'delivery_company' => $shippingAddress?->getCompany()??'',
            'delivery_address' => $shippingAddress?->getStreet()??'',
            'delivery_city' => $shippingAddress?->getCity()??'',
            'delivery_postcode' => $shippingAddress?->getPostcode()??'',
            'delivery_state_code' => $shippingAddress?->getProvinceCode()??'',
            'delivery_country' => $shippingAddress?->getCountryCode()??'',
            'delivery_country_code' => $shippingAddress?->getCountryCode()??'',
            'invoice_fullname' => $order->getBillingAddress()?->getFullName()??'',
            'invoice_company' => $order->getBillingAddress()?->getCompany()??'',
            'invoice_address' => $order->getBillingAddress()?->getStreet()??'',
            'invoice_city' => $order->getBillingAddress()?->getCity()??'',
            'invoice_state_code' => $order->getBillingAddress()?->getProvinceCode()??'',
            'invoice_postcode' => $order->getBillingAddress()?->getPostcode()??'',
            'invoice_country' => $order->getBillingAddress()?->getCountryCode()??'',
            'invoice_country_code' => $order->getBillingAddress()?->getCountryCode()??'',
            'invoice_nip' => $order->getBillingAddress()?->getVatNumber()??'',
            'delivery_point_id' => $order->getInpostPoint() ?? '',
            'delivery_point_name' => '',
            'delivery_point_address' => '',
            'delivery_point_postcode' => '',
            'delivery_point_city' => '',
            'phone' => $shippingAddress?->getPhoneNumber()??$order->getCustomer()?->getPhoneNumber()??'',
            'email' => $order->getCustomer()?->getEmail()??'',
            'date_add' => $order->getCreatedAt()?->getTimestamp(),
            'payment_method_id' => $order->getPayments()?->first() ? strval($order->getPayments()?->first()->getMethod()?->getId()) : "0",
            'payment_method' => $order->getPayments()?->first() ? $order->getPayments()?->first()?->getMethod()?->getName() : '',
            'payment_method_cod' => $order->getPayments()?->first() ? $order->getPayments()?->first()?->getMethod()?->getCode() : '',
            'payment_external_id' => '',
            'payment_operator' => '',
            'currency' => $order->getPayments()?->first() ? $order->getPayments()?->first()?->getCurrencyCode() : '',
            'user_comments' => $order->getNotes()??'',
            'user_comments_long' => $order->getNotes()??'',
            'admin_comments' => '',
            'status_id' => $order->getState(),
            'delivery_method_id' => $order->getShipments()?->first() ? $order->getShipments()?->first()?->getMethod()?->getId() : 0,
            'delivery_method' => $order->getShipments()?->first() ? $order->getShipments()?->first()?->getMethod()?->getName() : '',
            'delivery_price' => round(intval($order->getShipments()?->first() ? $order->getShipments()?->first()?->getAdjustmentsTotal() : 0) / 100,2),
            'paid' => $order->getPaymentState() === OrderPaymentStates::STATE_PAID ? 1 : 0,
            'paid_time' => $order->getUpdatedAt()?->getTimestamp(),
            'want_invoice' => 0,
            'extra_field_1' => $order->getSubscriptionId()?->getId()??'',
            'extra_field_2' => $order->getShippingState(),
            'products' => []
        ];
        foreach($order->getItems() as $item) {
            $mapped['products'][] = [
                'id' => strval($item->getVariant()?->getProduct()?->getId()),
                'variant_id' => strval($item->getVariant()?->getId()),
                'name' => $item->getVariant()?->getProduct()?->getName(),
                'quantity' => $item->getQuantity(),
                'price' => $this->getPrice($item->getVariant(), $channel),
                'weight' => $item->getVariant()?->getWeight(),
                'tax' => 0,
                'ean' => '',
                'sku' => $item->getVariant()?->getCode(),
                'location' => 'default',
                'attributes' => [],
            ];
        }
        return $mapped;
    }

    private function getPrice(ProductVariantInterface $variant, ChannelInterface $channel): float
    {
        return round(intval($variant->getChannelPricingForChannel($channel)?->getPrice()) / 100, 2);
    }
}

<?php declare(strict_types=1);

namespace Custom\CartExtension\Struct;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Struct\Struct;

class ShippingMethodWithCost extends Struct
{
    public function __construct(
        protected ShippingMethodEntity $shippingMethod,
        protected ?CalculatedPrice $calculatedPrice,
        protected bool $selected,
        protected bool $available
    ) {
    }

    public function getShippingMethod(): ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function getCalculatedPrice(): ?CalculatedPrice
    {
        return $this->calculatedPrice;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getApiAlias(): string
    {
        return 'shipping_method_with_cost';
    }
}

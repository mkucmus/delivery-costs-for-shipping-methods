<?php declare(strict_types=1);

namespace Custom\CartExtension\Struct;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class ShippingMethodWithCost extends ShippingMethodEntity
{
    protected ?CalculatedPrice $calculatedPrice = null;
    protected bool $selected = false;
    protected bool $available = false;

    public static function createFrom(
        ShippingMethodEntity $shippingMethod,
        ?CalculatedPrice $calculatedPrice,
        bool $selected,
        bool $available
    ): self {
        $instance = new self();

        // Copy all properties from the original shipping method
        foreach (get_object_vars($shippingMethod) as $key => $value) {
            $instance->$key = $value;
        }

        $instance->calculatedPrice = $calculatedPrice;
        $instance->selected = $selected;
        $instance->available = $available;

        return $instance;
    }

    public function getCalculatedPrice(): ?CalculatedPrice
    {
        return $this->calculatedPrice;
    }

    public function setCalculatedPrice(?CalculatedPrice $calculatedPrice): void
    {
        $this->calculatedPrice = $calculatedPrice;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function getVars(): array
    {
        $vars = parent::getVars();
        $vars['calculatedPrice'] = $this->calculatedPrice;
        $vars['selected'] = $this->selected;
        $vars['available'] = $this->available;

        return $vars;
    }
}

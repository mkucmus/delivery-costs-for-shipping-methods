<?php declare(strict_types=1);

namespace Custom\CartExtension\Struct;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @extends Collection<ShippingMethodWithCost>
 */
class ShippingMethodWithCostCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'shipping_method_with_cost_collection';
    }

    protected function getExpectedClass(): string
    {
        return ShippingMethodWithCost::class;
    }
}

<?php declare(strict_types=1);

namespace Custom\CartExtension\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ShippingMethodWithCost>
 */
class ShippingMethodWithCostCollection extends EntityCollection
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

<?php declare(strict_types=1);

namespace Custom\CartExtension\Route;

use Custom\CartExtension\Struct\ShippingMethodWithCostCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class ShippingMethodsWithCostsRouteResponse extends StoreApiResponse
{
    /**
     * @var ShippingMethodWithCostCollection
     */
    protected $object;

    public function __construct(ShippingMethodWithCostCollection $shippingMethods)
    {
        parent::__construct($shippingMethods);
    }

    public function getShippingMethods(): ShippingMethodWithCostCollection
    {
        return $this->object;
    }
}

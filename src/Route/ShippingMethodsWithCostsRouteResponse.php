<?php declare(strict_types=1);

namespace Custom\CartExtension\Route;

use Custom\CartExtension\Struct\ShippingMethodWithCostCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class ShippingMethodsWithCostsRouteResponse extends StoreApiResponse
{
    public function __construct(EntitySearchResult $result)
    {
        parent::__construct($result);
    }

    public function getShippingMethods(): ShippingMethodWithCostCollection
    {
        /** @var ShippingMethodWithCostCollection $entities */
        $entities = $this->object->getEntities();

        return $entities;
    }
}

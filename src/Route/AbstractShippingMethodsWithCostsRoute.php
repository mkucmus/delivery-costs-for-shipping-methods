<?php declare(strict_types=1);

namespace Custom\CartExtension\Route;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractShippingMethodsWithCostsRoute
{
    abstract public function getDecorated(): AbstractShippingMethodsWithCostsRoute;

    abstract public function load(Request $request, SalesChannelContext $context): ShippingMethodsWithCostsRouteResponse;
}

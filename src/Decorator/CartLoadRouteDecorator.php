<?php declare(strict_types=1);

namespace Custom\CartExtension\Decorator;

use Custom\CartExtension\Service\AllShippingCostsCalculator;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartResponse;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class CartLoadRouteDecorator extends AbstractCartLoadRoute
{
    public function __construct(
        private readonly AbstractCartLoadRoute $decorated,
        private readonly AllShippingCostsCalculator $shippingCostsCalculator
    ) {
    }

    public function getDecorated(): AbstractCartLoadRoute
    {
        return $this->decorated;
    }

    public function load(Request $request, SalesChannelContext $context): CartResponse
    {
        $response = $this->decorated->load($request, $context);

        // Only calculate shipping costs if explicitly requested via query parameter
        $includeShippingMethods = $request->query->getBoolean('includeAvailableShippingMethods')
            || $request->request->getBoolean('includeAvailableShippingMethods');

        if (!$includeShippingMethods) {
            return $response;
        }

        $cart = $response->getCart();

        // Calculate shipping costs for all available methods
        $availableShippingMethods = $this->shippingCostsCalculator->calculate($cart, $context);

        // Add to cart extensions
        $cart->addExtension('availableShippingMethods', $availableShippingMethods);

        return $response;
    }
}

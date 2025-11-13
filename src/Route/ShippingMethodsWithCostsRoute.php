<?php declare(strict_types=1);

namespace Custom\CartExtension\Route;

use Custom\CartExtension\Service\AllShippingCostsCalculator;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartFactory;
use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\Checkout\Cart\Exception\CartTokenNotFoundException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\StoreApiRouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StoreApiRouteScope::ID]])]
#[Package('checkout')]
class ShippingMethodsWithCostsRoute extends AbstractShippingMethodsWithCostsRoute
{
    public function __construct(
        private readonly AllShippingCostsCalculator $shippingCostsCalculator,
        private readonly CartPersister $persister,
        private readonly CartFactory $cartFactory,
        private readonly CartCalculator $cartCalculator
    ) {
    }

    public function getDecorated(): AbstractShippingMethodsWithCostsRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(
        path: '/store-api/shipping-methods-with-costs',
        name: 'store-api.shipping-methods-with-costs',
        methods: ['GET', 'POST']
    )]
    public function load(Request $request, SalesChannelContext $context): ShippingMethodsWithCostsRouteResponse
    {
        // Get the current cart
        $cart = $this->getCart($request, $context);

        // Calculate shipping costs for all available methods
        $shippingMethods = $this->shippingCostsCalculator->calculate($cart, $context);

        return new ShippingMethodsWithCostsRouteResponse($shippingMethods);
    }

    private function getCart(Request $request, SalesChannelContext $context): Cart
    {
        $token = $request->get('token', $context->getToken());

        try {
            $cart = $this->persister->load($token, $context);
        } catch (CartTokenNotFoundException) {
            $cart = $this->cartFactory->createNew($token);
        }

        return $this->cartCalculator->calculate($cart, $context);
    }
}

<?php declare(strict_types=1);

namespace Custom\CartExtension\Service;

use Custom\CartExtension\Struct\ShippingMethodWithCost;
use Custom\CartExtension\Struct\ShippingMethodWithCostCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryBuilder;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPosition;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Rule\RuleIdMatcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class AllShippingCostsCalculator
{
    public function __construct(
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        private readonly DeliveryBuilder $deliveryBuilder,
        private readonly DeliveryCalculator $deliveryCalculator,
        private readonly RuleIdMatcher $ruleIdMatcher
    ) {
    }

    public function calculate(Cart $cart, SalesChannelContext $context): ShippingMethodWithCostCollection
    {
        $collection = new ShippingMethodWithCostCollection();

        // Get all active shipping methods
        $shippingMethods = $this->getAvailableShippingMethods($context);

        if ($shippingMethods->count() === 0) {
            return $collection;
        }

        $currentShippingMethodId = $context->getShippingMethod()->getId();

        foreach ($shippingMethods as $shippingMethod) {
            $isSelected = $shippingMethod->getId() === $currentShippingMethodId;

            // Check if method is available based on rules
            $isAvailable = $this->isShippingMethodAvailable($shippingMethod, $context);

            // Calculate cost for this shipping method
            $calculatedPrice = null;
            if ($isAvailable && $cart->getLineItems()->count() > 0) {
                $calculatedPrice = $this->calculateShippingCost($cart, $shippingMethod, $context);
            }

            $collection->add(new ShippingMethodWithCost(
                $shippingMethod,
                $calculatedPrice,
                $isSelected,
                $isAvailable
            ));
        }

        return $collection;
    }

    private function getAvailableShippingMethods(SalesChannelContext $context): ShippingMethodCollection
    {
        $request = new Request();
        $request->query->set('onlyAvailable', false);

        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('prices')
            ->addAssociation('media')
            ->addAssociation('deliveryTime');

        $response = $this->shippingMethodRoute->load($request, $context, $criteria);

        return $response->getShippingMethods();
    }

    private function isShippingMethodAvailable(ShippingMethodEntity $shippingMethod, SalesChannelContext $context): bool
    {
        // Check if shipping method matches current context rules
        $availabilityRuleId = $shippingMethod->getAvailabilityRuleId();

        if ($availabilityRuleId === null) {
            return true;
        }

        return \in_array($availabilityRuleId, $context->getRuleIds(), true);
    }

    private function calculateShippingCost(
        Cart $cart,
        ShippingMethodEntity $shippingMethod,
        SalesChannelContext $context
    ): ?CalculatedPrice {
        try {
            // Create a temporary delivery with this shipping method
            $deliveryPositions = new DeliveryPositionCollection();

            foreach ($cart->getLineItems() as $lineItem) {
                $deliveryInfo = $lineItem->getDeliveryInformation();

                if ($deliveryInfo === null) {
                    // Create default delivery information for items without it
                    $deliveryInfo = new DeliveryInformation(
                        stock: 100,
                        weight: 0.0,
                        freeDelivery: false,
                        restockTime: null,
                        deliveryTime: new DeliveryDate(new \DateTime(), new \DateTime('+3 days'))
                    );
                }

                $deliveryPositions->add(
                    new DeliveryPosition(
                        $lineItem->getId(),
                        clone $lineItem,
                        $lineItem->getQuantity(),
                        $lineItem->getPrice() ?? new CalculatedPrice(0, 0, new CalculatedPrice\CalculatedTaxCollection(), new \Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection()),
                        $deliveryInfo->getDeliveryDate()
                    )
                );
            }

            if ($deliveryPositions->count() === 0) {
                return null;
            }

            $delivery = new \Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery(
                $deliveryPositions,
                new DeliveryDate(new \DateTime(), new \DateTime('+3 days')),
                $shippingMethod,
                new ShippingLocation(
                    $context->getShippingLocation()->getCountry(),
                    $context->getShippingLocation()->getState(),
                    null
                ),
                new CalculatedPrice(0, 0, new \Shopware\Core\Checkout\Cart\Price\Struct\CalculatedTaxCollection(), new \Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection())
            );

            $deliveries = new DeliveryCollection([$delivery]);

            // Prepare cart data collection with shipping method prices
            $cartData = new CartDataCollection();
            $cartData->set(
                'shipping-method-' . $shippingMethod->getId(),
                $shippingMethod
            );

            // Calculate the delivery cost
            $this->deliveryCalculator->calculate($cartData, $cart, $deliveries, $context);

            return $delivery->getShippingCosts();
        } catch (\Throwable $e) {
            // If calculation fails, return null
            return null;
        }
    }
}

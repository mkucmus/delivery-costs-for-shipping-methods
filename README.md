# Cart Extension Plugin

This plugin extends the Shopware 6 Store API `/store-api/checkout/cart` endpoint to include all available shipping methods with their calculated costs.

## Features

- Adds `availableShippingMethods` to cart response extensions **on demand**
- Only calculates when explicitly requested via query parameter (performance-friendly)
- Calculates shipping costs for all active shipping methods
- Includes availability information based on cart rules
- Marks the currently selected shipping method
- Properly handles tax calculations and price rules

## Response Structure

The plugin adds a new `availableShippingMethods` field to the cart response under `extensions`:

```json
{
  "cart": {
    "name": "...",
    "token": "...",
    "price": {...},
    "lineItems": [...],
    "deliveries": [...],
    "extensions": {
      "availableShippingMethods": [
        {
          "shippingMethod": {
            "id": "79ef7bea9b7d49fb9d32b235fb18aa18",
            "name": "Express",
            "technicalName": "shipping_express",
            "description": "Example description",
            "deliveryTime": {...},
            "media": {...}
          },
          "calculatedPrice": {
            "unitPrice": 5.90,
            "totalPrice": 5.90,
            "quantity": 1,
            "calculatedTaxes": [...],
            "taxRules": [...],
            "apiAlias": "calculated_price"
          },
          "selected": true,
          "available": true,
          "apiAlias": "shipping_method_with_cost"
        },
        {
          "shippingMethod": {
            "id": "a9d9cc502b3547f4a89eb2830c032c78",
            "name": "Standard",
            "...": "..."
          },
          "calculatedPrice": {
            "unitPrice": 3.90,
            "totalPrice": 3.90,
            "...": "..."
          },
          "selected": false,
          "available": true,
          "apiAlias": "shipping_method_with_cost"
        }
      ]
    }
  }
}
```

## Field Descriptions

- **shippingMethod**: The complete shipping method entity with all details
- **calculatedPrice**: The calculated shipping cost for this method based on the current cart contents. `null` if the method is not available or calculation failed
- **selected**: `true` if this is the currently selected shipping method in the context
- **available**: `true` if this shipping method is available based on cart rules and availability rules

## Installation

```bash
# Refresh plugin list
bin/console plugin:refresh

# Install and activate the plugin
bin/console plugin:install --activate CartExtension

# Clear cache
bin/console cache:clear
```

## Usage

### Query Parameter

To include all available shipping methods with costs, add the `includeAvailableShippingMethods` parameter:

**GET Request:**
```
GET /store-api/checkout/cart?includeAvailableShippingMethods=1
```

**POST Request:**
```json
POST /store-api/checkout/cart
{
  "includeAvailableShippingMethods": true
}
```

### Example: Fetch cart with all shipping methods

```javascript
// Using query parameter
const response = await fetch('/store-api/checkout/cart?includeAvailableShippingMethods=1', {
  method: 'GET',
  headers: {
    'sw-access-key': 'YOUR_ACCESS_KEY'
  }
});

const { cart } = await response.json();

// Check if available shipping methods are included
if (cart.extensions?.availableShippingMethods) {
  const availableShippingMethods = cart.extensions.availableShippingMethods;

  // Display shipping options to user
  availableShippingMethods.forEach(method => {
    console.log(`${method.shippingMethod.name}: ${method.calculatedPrice?.totalPrice || 'N/A'} €`);
    console.log(`Available: ${method.available}, Selected: ${method.selected}`);
  });
}
```

### Example: Regular cart fetch (without shipping methods)

```javascript
// Without parameter - no extra calculation overhead
const response = await fetch('/store-api/checkout/cart', {
  method: 'GET',
  headers: {
    'sw-access-key': 'YOUR_ACCESS_KEY'
  }
});

const { cart } = await response.json();
// cart.extensions.availableShippingMethods will not be present
```

## Benefits

- **On-Demand Calculation**: Only calculates when needed via query parameter - no performance impact on regular cart loads
- **No Multiple API Calls**: Previously, you needed to switch shipping methods and recalculate the cart multiple times to get all costs
- **Better UX**: Users can see all shipping options with prices upfront
- **Accurate Pricing**: Uses the same calculation logic as the core cart
- **Rule-Aware**: Respects availability rules and cart conditions

## Technical Details

### Components

1. **AllShippingCostsCalculator**: Service that calculates shipping costs for all available methods
2. **CartLoadRouteDecorator**: Decorates the cart load route to add the new data
3. **ShippingMethodWithCost**: Struct representing a shipping method with its calculated cost
4. **OpenAPI Schema**: Documents the new API structure

### How It Works

1. When the cart is loaded, the decorator intercepts the response
2. If `includeAvailableShippingMethods` parameter is present and true:
   - It fetches all active shipping methods
   - For each method, it:
     - Checks availability based on rules
     - Creates a temporary delivery
     - Calculates the shipping cost using the core `DeliveryCalculator`
   - The results are added to the cart extensions
3. If the parameter is not present, the cart response is returned immediately without extra processing

## Requirements

- Shopware 6.6+
- PHP 8.1+

## License

MIT

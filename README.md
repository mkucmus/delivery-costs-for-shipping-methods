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

This plugin provides **two ways** to get shipping methods with costs:

### Option 1: Dedicated Endpoint (Recommended)

Use the dedicated endpoint `/store-api/shipping-methods-with-costs` to fetch shipping methods with costs independently:

**GET Request:**
```
GET /store-api/shipping-methods-with-costs
```

**POST Request:**
```json
POST /store-api/shipping-methods-with-costs
{
  "token": "optional-cart-token"
}
```

**Response:**
```json
{
  "elements": [
    {
      "shippingMethod": {
        "id": "79ef7bea9b7d49fb9d32b235fb18aa18",
        "name": "Express",
        "technicalName": "shipping_express",
        "deliveryTime": {...}
      },
      "calculatedPrice": {
        "unitPrice": 5.90,
        "totalPrice": 5.90,
        "quantity": 1,
        "calculatedTaxes": [...],
        "taxRules": [...]
      },
      "selected": true,
      "available": true,
      "apiAlias": "shipping_method_with_cost"
    }
  ],
  "apiAlias": "shipping_method_with_cost_collection"
}
```

### Option 2: Cart Endpoint Extension

To include all available shipping methods with costs in the cart response, add the `includeAvailableShippingMethods` parameter:

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

### Code Examples

#### Example 1: Using the dedicated endpoint (Recommended)

```javascript
// Fetch shipping methods with costs independently
const response = await fetch('/store-api/shipping-methods-with-costs', {
  method: 'GET',
  headers: {
    'sw-access-key': 'YOUR_ACCESS_KEY'
  }
});

const data = await response.json();
const shippingMethods = data.elements;

// Display shipping options to user
shippingMethods.forEach(method => {
  const price = method.calculatedPrice?.totalPrice || 'N/A';
  const currency = method.calculatedPrice ? '€' : '';

  console.log(`${method.shippingMethod.name}: ${price} ${currency}`);
  console.log(`Available: ${method.available}, Selected: ${method.selected}`);

  if (method.selected) {
    console.log('✓ Currently selected');
  }
});
```

#### Example 2: Using cart endpoint extension

```javascript
// Fetch cart with shipping methods included
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

### When to Use Which Approach?

**Use the dedicated endpoint (`/store-api/shipping-methods-with-costs`):**
- ✅ When you only need shipping methods (e.g., standalone shipping selector)
- ✅ When you want cleaner separation of concerns
- ✅ When building a dedicated shipping selection page
- ✅ For lighter API responses

**Use the cart extension (`?includeAvailableShippingMethods=1`):**
- ✅ When you need both cart data AND shipping methods in one call
- ✅ On checkout pages where cart and shipping are displayed together
- ✅ To reduce total number of API calls

## Benefits

- **Two Flexible Approaches**: Dedicated endpoint OR cart extension - choose what fits your use case
- **On-Demand Calculation**: Only calculates when needed - no performance impact on regular cart loads
- **No Multiple API Calls**: Previously, you needed to switch shipping methods and recalculate the cart multiple times to get all costs
- **Better UX**: Users can see all shipping options with prices upfront
- **Accurate Pricing**: Uses the same calculation logic as the core cart
- **Rule-Aware**: Respects availability rules and cart conditions
- **Context-Aware**: Uses current cart, session, and customer context for accurate calculations

## Technical Details

### Components

1. **ShippingMethodsWithCostsRoute**: Dedicated Store API endpoint for fetching shipping methods with costs
2. **AllShippingCostsCalculator**: Service that calculates shipping costs for all available methods
3. **CartLoadRouteDecorator**: Decorates the cart load route to optionally add shipping methods
4. **ShippingMethodWithCost**: Struct representing a shipping method with its calculated cost
5. **ShippingMethodWithCostCollection**: Collection of shipping methods with costs
6. **OpenAPI Schema**: Documents both endpoints in the API structure

### How It Works

**Dedicated Endpoint (`/store-api/shipping-methods-with-costs`):**
1. Receives request with optional cart token
2. Loads the current cart (or creates empty cart)
3. Fetches all active shipping methods
4. For each method:
   - Checks availability based on rules
   - Creates a temporary delivery with cart items
   - Calculates the shipping cost using the core `DeliveryCalculator`
5. Returns collection of shipping methods with calculated costs

**Cart Extension Approach:**
1. When the cart is loaded, the decorator intercepts the response
2. If `includeAvailableShippingMethods` parameter is present and true:
   - Uses the same calculation logic as the dedicated endpoint
   - Adds results to cart extensions
3. If the parameter is not present, the cart response is returned immediately without extra processing

## Requirements

- Shopware 6.6+
- PHP 8.1+

## License

MIT

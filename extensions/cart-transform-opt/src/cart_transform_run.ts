import type {
  CartTransformRunInput,
  CartTransformRunResult,
  Operation,
} from "../generated/api";

const NO_CHANGES: CartTransformRunResult = {
  operations: [],
};

/*
// PREVIOUS LINEUPDATE IMPLEMENTATION (KEPT FOR REFERENCE ONLY)
// Note: lineUpdate returned "update_feature_not_available" on non-Shopify Plus stores.
export function cartTransformRunLineUpdateReference(
  input: CartTransformRunInput
): CartTransformRunResult {
  const operations: Operation[] = [];
  for (const line of input.cart.lines) {
    const customOptionPriceAttr = line.customOptionPrice?.value;
    if (!customOptionPriceAttr) continue;
    const customOptionPrice = parseFloat(customOptionPriceAttr);
    if (!Number.isFinite(customOptionPrice) || customOptionPrice <= 0) continue;
    const baseUnitPrice = parseFloat(line.cost.amountPerQuantity.amount);
    if (!Number.isFinite(baseUnitPrice)) continue;
    const newUnitPrice = baseUnitPrice + customOptionPrice;
    operations.push({
      lineUpdate: {
        cartLineId: line.id,
        price: {
          adjustment: {
            fixedPricePerUnit: {
              amount: newUnitPrice,
            },
          },
        },
      },
    });
  }
  return operations.length > 0 ? { operations } : NO_CHANGES;
}
*/

export function cartTransformRun(
  input: CartTransformRunInput
): CartTransformRunResult {
  const operations: Operation[] = [];

  for (const line of input.cart.lines) {
    // 1. Verify parent merchandise variant ID exists
    const parentVariantId =
      line.merchandise && "id" in line.merchandise && typeof line.merchandise.id === "string"
        ? line.merchandise.id
        : null;

    if (!parentVariantId || !parentVariantId.startsWith("gid://shopify/ProductVariant/")) {
      continue;
    }

    // 2. Extract base unit price
    const baseUnitPrice = parseFloat(line.cost?.amountPerQuantity?.amount);
    if (!Number.isFinite(baseUnitPrice)) {
      continue;
    }

    // 3. Extract and parse custom option data
    const customOptionDataRaw = line.customOptionData?.value;
    const customOptionPriceRaw = line.customOptionPrice?.value;

    let addonVariantId: string | null = null;
    let totalOptionPrice = 0;

    if (customOptionDataRaw) {
      try {
        const parsedData = JSON.parse(customOptionDataRaw);
        if (parsedData && typeof parsedData === "object") {
          if (
            typeof parsedData.addon_variant_id === "string" &&
            parsedData.addon_variant_id.startsWith("gid://shopify/ProductVariant/")
          ) {
            addonVariantId = parsedData.addon_variant_id;
          }

          if (Array.isArray(parsedData.options) && parsedData.options.length > 0) {
            for (const opt of parsedData.options) {
              if (opt && typeof opt === "object") {
                const itemPrice = typeof opt.price === "number" ? opt.price : parseFloat(opt.price);
                if (Number.isFinite(itemPrice) && itemPrice > 0) {
                  totalOptionPrice += itemPrice;
                }
              }
            }
          }
        }
      } catch (e) {
        // Invalid JSON - handle safely without error
      }
    }

    // Fallback to numeric _custom_option_price if totalOptionPrice not computed yet
    if (totalOptionPrice <= 0 && customOptionPriceRaw) {
      const fallbackPrice = parseFloat(customOptionPriceRaw);
      if (Number.isFinite(fallbackPrice) && fallbackPrice > 0) {
        totalOptionPrice = fallbackPrice;
      }
    }

    // 4. Safety validation: Option price must be > 0 and helper addon variant GID must be valid
    if (!addonVariantId || !Number.isFinite(totalOptionPrice) || totalOptionPrice <= 0) {
      continue;
    }

    // 5. Construct lineExpand operation:
    //    Shopify requires that ALL expandedCartItems have an explicit price adjustment or NONE do.
    //    Component 1: Original base product variant (quantity: 1) with explicit baseUnitPrice
    //    Component 2: Helper Custom Option Add-On variant (quantity: 1) with explicit totalOptionPrice
    operations.push({
      lineExpand: {
        cartLineId: line.id,
        expandedCartItems: [
          {
            merchandiseId: parentVariantId,
            quantity: 1,
            price: {
              adjustment: {
                fixedPricePerUnit: {
                  amount: baseUnitPrice,
                },
              },
            },
          },
          {
            merchandiseId: addonVariantId,
            quantity: 1,
            price: {
              adjustment: {
                fixedPricePerUnit: {
                  amount: totalOptionPrice,
                },
              },
            },
          },
        ],
      },
    });
  }

  return operations.length > 0 ? { operations } : NO_CHANGES;
}
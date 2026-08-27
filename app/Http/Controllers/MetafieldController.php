<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Test;


class MetafieldController extends Controller
{


  public function save(Request $request)
  {
    $request->validate([
      'data' => 'required|string',
      'button_color' => 'required|string'
    ]);

    // Try to get the first row
    $test = Test::first();

    if ($test) {
      // Update the first row
      $test->text = $request->data;
      $test->shop = $request->button_color;
      $test->save();
      $message = 'Row updated successfully!';
    } else {
      // Insert a new row
      Test::create([
        'text' => $request->data,
        'color' => $request->button_color,
      ]);
      $message = 'Row inserted successfully!';
    }

    // Shopify metafield logic (your existing code)
    $shop = 'magecurious-demo.myshopify.com';
    $accessToken = 'shpua_12443b46259aac45b22ac2459718c28e';

    // Get the shop GID first
    $query = '{ shop { id } }';
    $response = \Illuminate\Support\Facades\Http::withHeaders([
      'X-Shopify-Access-Token' => $accessToken,
      'Content-Type' => 'application/json',
    ])->post("https://$shop/admin/api/2025-01/graphql.json", [
          'query' => $query
        ]);
    $shopId = $response->json('data.shop.id');

    // Prepare both metafields
    $metafields = [
      [
        "ownerId" => $shopId,
        "namespace" => "custom",
        "key" => "button_label",
        "type" => "single_line_text_field",
        "value" => $request->data,
      ],
      [
        "ownerId" => $shopId,
        "namespace" => "custom",
        "key" => "button_color",
        "type" => "single_line_text_field",
        "value" => $request->button_color,
      ]
    ];

    // GraphQL mutation
    $mutation = '
        mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
          metafieldsSet(metafields: $metafields) {
            metafields {
              id
              key
              namespace
              value
            }
            userErrors {
              field
              message
            }
          }
        }
    ';

    $variables = ['metafields' => $metafields];

    $response = \Illuminate\Support\Facades\Http::withHeaders([
      'X-Shopify-Access-Token' => $accessToken,
      'Content-Type' => 'application/json',
    ])->post("https://$shop/admin/api/2025-01/graphql.json", [
          'query' => $mutation,
          'variables' => $variables
        ]);

    // \Log::info('Shopify save response:', $response->json());

    $result = $response->json();

    if (isset($result['data']['metafieldsSet']['userErrors']) && count($result['data']['metafieldsSet']['userErrors']) > 0) {
      return redirect()->back()->with('error', $result['data']['metafieldsSet']['userErrors'][0]['message'] ?? 'Unknown error');
    }


    return redirect()->back()->with('success', $message);
  }

  public function getMetafield()
  {
    $shop = 'magecurious-demo.myshopify.com';
    $accessToken = 'shpua_12443b46259aac45b22ac2459718c28e';

    $query = '
          {
            shop {
              metafield(namespace: "custom", key: "button_label") {
                value
              }
            }
          }
        ';

    $response = Http::withHeaders([
      'X-Shopify-Access-Token' => $accessToken,
      'Content-Type' => 'application/json',
    ])->post("https://$shop/admin/api/2024-01/graphql.json", [
          'query' => $query
        ]);

    \Log::info('Shopify getMetafieldController appConfigurations response:', $response->json()); // Log response

    $value = optional(optional($response->json('data'))['shop']['metafield'])['value'] ?? 'Default Button';

    return response()->json(['label' => $value]);
  }

  public function setMetafield(Request $request)
  {
    $request->validate([
      'data' => 'required|string',
    ]);

    $shop = 'magecurious-demo.myshopify.com';
    $accessToken = 'shpua_12443b46259aac45b22ac2459718c28e';

    // Get the shop GID first
    $query = '{ shop { id } }';
    $response = Http::withHeaders([
      'X-Shopify-Access-Token' => $accessToken,
      'Content-Type' => 'application/json',
    ])->post("https://$shop/admin/api/2025-01/graphql.json", [
          'query' => $query
        ]);
    $shopId = $response->json('data.shop.id');

    $mutation = '
            mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
              metafieldsSet(metafields: $metafields) {
                metafields {
                  id
                  key
                  namespace
                  value
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

    $variables = [
      'metafields' => [
        [
          "ownerId" => $shopId,
          "namespace" => "custom",
          "key" => "button_label",
          "type" => "single_line_text_field",
          "value" => $request->data,
        ]
      ]
    ];

    $response = Http::withHeaders([
      'X-Shopify-Access-Token' => $accessToken,
      'Content-Type' => 'application/json',
    ])->post("https://$shop/admin/api/2025-01/graphql.json", [
          'query' => $mutation,
          'variables' => $variables
        ]);

    \Log::info('Shopify setMetafield response:', $response->json()); // Log response

    $result = $response->json();
    if (isset($result['data']['metafieldsSet']['userErrors']) && count($result['data']['metafieldsSet']['userErrors']) > 0) {
      return response()->json(['error' => $result['data']['metafieldsSet']['userErrors']], 400);
    }

    return response()->json(['success' => true, 'metafield' => $result['data']['metafieldsSet']['metafields'][0] ?? null]);
  }
}

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\LaravelWebController;
use App\Http\Controllers\MessageCartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\ShopifyConfigController;
// use App\Http\Controllers\AppStatusController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShopifyHelloController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Shopify\Auth\FileSessionStorage; // szc shipping per item
use Shopify\Auth\SessionStorage;
use Shopify\Context;
use App\Http\Controllers\OptionSetController; // Option set controller


// Test Product Options Variant start
Route::get('/', [OptionSetController::class, 'index'])->name('optionset.configuration');
Route::post('/option-set/status', [OptionSetController::class, 'updateOptionSetStatus'])->name('options.status');;
Route::post('/save-options', [OptionSetController::class, 'saveOptions'])->name('options.save');
Route::post('/delete-option', [OptionSetController::class, 'deleteOptionSet'])->name('options.delete');
Route::post('/bulk-delete-options', [OptionSetController::class, 'bulkDeleteOptions'])->name('options.bulk-delete');
Route::post('/bulk-duplicate-options', [OptionSetController::class, 'bulkDuplicateOptions'])->name('options.bulk-duplicate');
Route::get('/api/proxy/get-options', [OptionSetController::class, 'getOptionsForFrontend']);


Route::post('/upload-to-shopify', [OptionSetController::class, 'handleUpload'])->name('option.upload');

Route::post('/api/proxy/upload-to-shopify', [OptionSetController::class, 'handleUpload'])->name('option.proxy.upload');

Route::post('/get-product-details', [OptionSetController::class, 'getProductDetails'])->name('products.details');

// Test Product Options Variant end



Route::get('/appinstall/{appId}', [ShopifyAuthController::class, 'validateAppInstallation'])->name('authorize'); 
Route::post('/webhooks/app-uninstalled/{appId}', [ShopifyAuthController::class, 'appUninstalled'])->name('app-uninstalled');
Route::get('/auth/callback', [ShopifyAuthController::class, 'callbackAppInstallation'])->name('getstarted');

Route::get('/welcome', [SettingsController::class, 'welcomeToApp'])->name('welcome');
Route::get('/auth/configuration', [SettingsController::class, 'appConfigurations'])->name('configuration');

Route::post('/api/appStatus', [SettingsController::class, 'setAppStatus']);

Route::middleware([HandleCors::class])->group(function () {
	Route::get('/api/getappstatus', [SettingsController::class, 'getAppStatus']);
});
Route::post('/api/auth/verify', [SettingsController::class, 'verifySessionToken'])->name('verify');

Route::get('/auth/subscribe', [SettingsController::class, 'subscribeApp'])->name('subscribe');

Route::get('/subscriptionfailed', [SettingsController::class, 'subscriptionFailed'])->name('subscriptionfailed');

Route::get('/confirm_billing', [SettingsController::class, 'appBillingConfirmation'])->name('confirm_billing');

Route::get('/products2', function () {
         $query = <<<GRAPHQL
    query GetAllProducts(\$first: Int!, \$after: String) {
      products(first: \$first, after: \$after) {
        edges {
          node {
            id
            title
            status
            createdAt
            featuredImage {
              altText
              url
            }
            handle
            descriptionHtml
            variants(first: 10) {
              edges {
                node {
                  id
                  title
                  sku
                   presentmentPrices(first: 1) {
                edges {
                  node {
                    price {
                      amount
                      currencyCode
                    }
                  }
                }
              }
                  
                }
              }
            }
          }
          cursor
        }
        pageInfo {
          hasNextPage
        }
      }
    }
    GRAPHQL;

    $variables = [
        'first' => 10,
        'after' => null,
    ];

    $shop = Auth::user(); // Get authenticated shop
       //echo "HI<pre>";print_r($shop);die();
     $response = $shop->apiHelper()->doRequestGraphQL($query, $variables); 
    //$response = auth()->user()->api()->graph($query, $variables); // Example Shopify GraphQL call

    //$products = $response['body']['container']['data']['products']['edges'];
    echo "HI<pre>";print_r($response);die();
})->middleware(['verify.shopify'])->name('home');

Route::get('/products1', [ShopifyController::class, 'getAllProducts']);
Route::middleware(['auth'])->get('/products', function () {
    // Fetch products from Shopify
    try {
    
        $products = auth()->user()->api()->rest('GET', '/admin/api/2025-01/products.json')['body']['products'];
          echo "HI<pre>";print_r($products);die();
       $query = <<<GRAPHQL
    query GetAllProducts(\$first: Int!, \$after: String) {
      products(first: \$first, after: \$after) {
        edges {
          node {
            id
            title
            status
            createdAt
            featuredImage {
              altText
              url
            }
            handle
            descriptionHtml
            variants(first: 10) {
              edges {
                node {
                  id
                  title
                  sku
                   presentmentPrices(first: 1) {
                edges {
                  node {
                    price {
                      amount
                      currencyCode
                    }
                  }
                }
              }
                  
                }
              }
            }
          }
          cursor
        }
        pageInfo {
          hasNextPage
        }
      }
    }
    GRAPHQL;

    $variables = [
        'first' => 10,
        'after' => null,
    ];

    $response = auth()->user()->api()->graph($query, $variables); // Example Shopify GraphQL call

    $products = $response['body']['container']['data']['products']['edges'];
    //echo "HI<pre>";print_r($products);die();
        return view('products', compact('products'));
    } catch (Exception $e) {
        return back()->withErrors(['error' => 'Failed to fetch products from Shopify: ' . $e->getMessage()]);
    }
})->name('products');
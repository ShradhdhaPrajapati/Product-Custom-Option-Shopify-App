<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\OptionSet;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Shopify\Auth\FileSessionStorage;
use Illuminate\Support\Facades\Session;
use Shopify\Clients\Rest;
use Shopify\Context;

class OptionSetController extends Controller
{
    private $namespace = 'test_app';

    private $key = 'option_config';

    public function index(Request $request)
    {
       
        try {
           
            // Get shop domain (iframe / fallback)
            $shopDomain = $request->get('shop');

            if (! $shopDomain && Auth::user()) {
                $shopDomain = Auth::user()->shop;
            }

            if (! $shopDomain) {
                throw new \Exception('Shop domain missing');
            }

            $appId = 'PRODUCTCUSTOMOPTION';
            $isAppEnable = 0;
            $sets = [];

            // Get shop model
            $shop = Shop::where('shop', $shopDomain)
                ->where('app_id', $appId)
                ->first();

            if ($shop) {
                $isAppEnable = (int) $shop->isApp_Enable;

                $sets = OptionSet::where('shop_id', $shop->id)
                    ->with('options')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            $response = [
                'shop' => $shopDomain,
                'appId' => $appId,
                'isAppEnable' => $isAppEnable,
            ];

            \Log::info('CONFIG SHOP DEBUG', [
                'request_shop' => $request->get('shop'),
                'auth_shop' => Auth::user()?->shop,
                'final_shop' => $shopDomain,
                'app_id' => $appId,
                'shop_id' => $shop?->id,
                'sets_count' => $sets->count(),
            ]);

            return view('productcustomoption_configuration', compact('response', 'sets'));
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }

    public function saveOptions(Request $request)
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $appId = 'PRODUCTCUSTOMOPTION';

        $shopModel = Shop::where('shop', $shopDomain)
            ->where('app_id', $appId)
            ->first();

        if (!$shopModel) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $request->validate(['name' => 'required|string']);

        DB::beginTransaction();

        try {
            if ($request->filled('id')) {
                $optionSet = OptionSet::where('id', $request->id)
                    ->where('shop_id', $shopModel->id)
                    ->first();

                if (! $optionSet) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Option set not found',
                    ], 404);
                }
            } else {
                $optionSet = new OptionSet;
            }

            $optionSet->shop_id = $shopModel->id;
            $optionSet->name = $request->name;
            $optionSet->status = 1;
            $optionSet->product_ids = json_encode($request->product_ids ?? []);
            $optionSet->save();

            $optionSet->options()->delete();

            foreach ($request->options ?? [] as $opt) {
                $option = new Option();
                $option->option_set_id = $optionSet->id;
                $option->type = $opt['type'];
                $option->label = $opt['label'];

                $option->values = $opt['values'];

                $option->required = isset($opt['required']) ? (int)$opt['required'] : 0;
                $option->position = 0;

                $option->save();
            }

            DB::commit();
            $this->syncMetafieldToShopify($shopModel, $appId);
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function syncMetafieldToShopify($shopModel, $appId)
    {
        try {
            $this->initializeAPI($appId);

            $client = new Rest(
                $shopModel->shop,
                $shopModel->access_token
            );

            $sets = OptionSet::where('shop_id', $shopModel->id)
                ->with('options')
                ->get();

            $data = $sets->map(function ($set) {
                return [
                    'id' => $set->id,
                    'name' => $set->name,
                    'status' => $set->status,
                    'product_ids' => json_decode($set->product_ids),
                    'options' => $set->options,
                ];
            });

            $client->post('metafields.json', [
                'metafield' => [
                    'namespace' => $this->namespace,
                    'key' => $this->key,
                    'value' => json_encode($data),
                    'type' => 'json',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Metafield Sync Error: ' . $e->getMessage());
        }
    }

    public function updateOptionSetStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:option_sets,id',
            'status' => 'required|in:0,1',
        ]);

        OptionSet::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteOptionSet(Request $request)
    {
        try {
            if (! $request->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'OptionSet ID missing',
                ], 422);
            }

            $optionSet = OptionSet::with('options')->find($request->id);

            if (! $optionSet) {
                return response()->json([
                    'success' => false,
                    'message' => 'OptionSet not found',
                ], 404);
            }

            $optionSet->options()->delete();
            $optionSet->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Delete OptionSet Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDeleteOptions(Request $request)
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $appId = 'PRODUCTCUSTOMOPTION';

        $shopModel = Shop::where('shop', $shopDomain)->where('app_id', $appId)->first();
        if (! $shopModel) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $ids = $request->input('ids');
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
        }

        try {
            DB::beginTransaction();
            Option::whereIn('option_set_id', $ids)->delete();
            OptionSet::where('shop_id', $shopModel->id)
                ->whereIn('id', $ids)
                ->delete();

            DB::commit();

            $this->syncMetafieldToShopify($shopModel, $appId);

            return response()->json(['success' => true, 'message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk Delete Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkDuplicateOptions(Request $request)
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $appId = 'PRODUCTCUSTOMOPTION';

        $shopModel = Shop::where('shop', $shopDomain)->where('app_id', $appId)->first();
        if (! $shopModel) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $ids = $request->input('ids');
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
        }

        try {
            DB::beginTransaction();

            $originalSets = OptionSet::where('shop_id', $shopModel->id)
                ->whereIn('id', $ids)
                ->with('options')
                ->get();

            foreach ($originalSets as $originalSet) {
                $newSet = $originalSet->replicate();
                $newSet->name = $originalSet->name . ' (Copy)';
                $newSet->created_at = now();
                $newSet->updated_at = now();
                $newSet->save();

                foreach ($originalSet->options as $option) {
                    $newOption = $option->replicate();
                    $newOption->option_set_id = $newSet->id;
                    $newOption->created_at = now();
                    $newOption->updated_at = now();
                    $newOption->save();
                }
            }

            DB::commit();

            $this->syncMetafieldToShopify($shopModel, $appId);

            return response()->json(['success' => true, 'message' => 'Duplicated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk Duplicate Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function initializeAPI($appId)
    {
        $apiKey = env($appId . '_API_KEY');
        $apiSecret = env($appId . '_API_SECRET');
        $scopes = env($appId . '_API_SCOPES', 'read_products,write_metafields,read_metafields');
        if (empty($scopes)) {
            $scopes = env('PRODUCTCUSTOMOPTION_API_SCOPES', 'read_files,write_files,read_metaobjects,write_metaobjects,read_products,write_products');
        }

        $host = env('SHOPIFY_APP_URL');
        $hostName = str_replace(['https://', 'http://'], '', $host);

        \Shopify\Context::initialize(
            apiKey: $apiKey,
            apiSecretKey: $apiSecret,
            scopes: explode(',', $scopes),
            hostName: $hostName,
            sessionStorage: new \Shopify\Auth\FileSessionStorage(storage_path('framework/sessions')),
            apiVersion: '2025-01',
            isEmbeddedApp: true
        );
    }

    public function getOptionsForFrontend(Request $request)
    {
       
        $shopDomain = $request->get('shop');
        $productId = (string) $request->get('product_id');

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, ngrok-skip-browser-warning',
            'Content-Type' => 'application/json'
        ];

        try {
            $shop = Shop::where('shop', $shopDomain)
                ->where('app_id', 'PRODUCTCUSTOMOPTION')
                ->first();

            if (!$shop) {
                return response()->json(['success' => false, 'message' => 'Shop not found'], 404)->withHeaders($headers);
            }

            $activeSets = OptionSet::where('shop_id', $shop->id)
                ->where('status', 1)
                ->with('options')
                ->get();

            $mergedOptions = [];

            foreach ($activeSets as $set) {
                $savedIds = json_decode($set->product_ids, true);
                if (!is_array($savedIds)) $savedIds = [];
                $savedIds = array_map('strval', $savedIds);

                if (in_array('ALL', $savedIds) || in_array($productId, $savedIds) || in_array("gid://shopify/Product/" . $productId, $savedIds)) {

                    foreach ($set->options as $option) {
                        $mergedOptions[] = $option;
                    }
                }
            }

            $helperVariantId = $this->ensureHelperProductExists($shop);

            return response()->json([
                'success' => true,
                'data' => $mergedOptions,
                'helper_variant_id' => $helperVariantId
            ], 200)->withHeaders($headers);
        } catch (\Exception $e) {
           
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500)->withHeaders($headers);
        }
    }

    public function getProductDetails(Request $request)
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        if (empty($shopDomain)) {
            $shopDomain = $request->get('shop') ?? (Auth::check() ? Auth::user()->shop : null);
        }

        $shopModel = Shop::where('shop', $shopDomain)->where('app_id', 'PRODUCTCUSTOMOPTION')->first();

        if (!$shopModel) {
            return response()->json(['products' => []]);
        }
        $ids = $request->input('ids');
       

        if (empty($ids)) {
            return response()->json(['products' => []]);
        }

        $formattedIds = array_map(function($id) {
            return strpos($id, 'gid://') !== false ? $id : 'gid://shopify/Product/' . $id;
        }, $ids);

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $shopModel = Shop::where('shop', $shopDomain)->where('app_id', 'PRODUCTCUSTOMOPTION')->first();

        $this->initializeAPI('PRODUCTCUSTOMOPTION');
        $client = new \Shopify\Clients\Graphql($shopModel->shop, $shopModel->access_token);

        $query = <<<'QUERY'
        query getProducts($ids: [ID!]!) {
            nodes(ids: $ids) {
                ... on Product {
                    id
                    title
                }
            }
        }
        QUERY;

        try {
            $response = $client->query([
                'query' => $query,
                'variables' => ['ids' => $formattedIds],
            ]);

            $body = $response->getDecodedBody();
            $formattedProducts = [];

            if (isset($body['data']['nodes'])) {
                foreach ($body['data']['nodes'] as $node) {
                    if ($node) {
                        $formattedProducts[] = [
                            'id' => $node['id'],
                            'title' => $node['title']
                        ];
                    }
                }
            }
            return response()->json(['products' => $formattedProducts]);
        } catch (\Exception $e) {
            \Log::error('API Error: ' . $e->getMessage());
            return response()->json(['products' => []]);
        }
    }

    public function handleUpload(Request $request)
    {

        $request->validate(['swatch_image' => 'required|file|max:500480']);

        $shopUrl = $request->query('shop') ?? $request->input('shop');

        if (empty($shopUrl)) {
            return response()->json(['success' => false, 'message' => 'Shop parameter missing']);
        }

        $shopModel = \App\Models\Shop::where('shop', 'like', '%' . trim(str_replace(['https://', 'http://'], '', $shopUrl)) . '%')
            ->where('app_id', 'PRODUCTCUSTOMOPTION')
            ->first();

        if (!$shopModel) {
            return response()->json(['success' => false, 'message' => 'Shop not found']);
        }

        try {
            $file = $request->file('swatch_image');
            $tempPath = storage_path('app/temp_uploads');
            if (!file_exists($tempPath)) mkdir($tempPath, 0777, true);

            $tempName = time() . '_' . $file->getClientOriginalName();
            $file->move($tempPath, $tempName);
            $fullPath = $tempPath . '/' . $tempName;

            $cdnUrl = $this->uploadToShopify($shopModel, $fullPath, $tempName);

            if (file_exists($fullPath)) unlink($fullPath);

            if (!$cdnUrl) {
                return response()->json(['success' => false, 'message' => 'Upload failed.']);
            }

            return response()->json(['success' => true, 'url' => $cdnUrl]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function uploadToShopify($shopModel, $localFilePath, $filename)
    {
        $this->initializeAPI('PRODUCTCUSTOMOPTION');
        $client = new \Shopify\Clients\Graphql($shopModel->shop, $shopModel->access_token);

        $mimeType = \Illuminate\Support\Facades\File::mimeType($localFilePath);

        // 1. Staged Upload Initiation
        $queryString = <<<'QUERY'
        mutation stagedUploadsCreate($input: [StagedUploadInput!]!) {
            stagedUploadsCreate(input: $input) {
                stagedTargets { url resourceUrl parameters { name value } }
            }
        }
        QUERY;

        $mimeType = \Illuminate\Support\Facades\File::mimeType($localFilePath);
        $response = $client->query([
            'query' => $queryString,
            'variables' => [
                'input' => [[
                    'filename' => $filename,
                    'mimeType' => $mimeType,
                    'resource' => 'FILE',
                    'httpMethod' => 'POST',
                ]],
            ],
        ]);

        $body = $response->getDecodedBody();
        $target = $body['data']['stagedUploadsCreate']['stagedTargets'][0] ?? null;

        if (!$target) {
            \Log::error('Staged Upload Failed: ' . json_encode($body));
            return null;
        }

        // 2. Guzzle Multipart Upload
        $formData = [];
        foreach ($target['parameters'] as $param) {
            $formData[] = ['name' => $param['name'], 'contents' => $param['value']];
        }
        $formData[] = ['name' => 'file', 'contents' => fopen($localFilePath, 'r')];

        $httpClient = new \GuzzleHttp\Client();
        try {
            $response = $httpClient->post($target['url'], ['multipart' => $formData]);
           
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            \Log::error('Guzzle Client Error: ' . $e->getResponse()->getBody()->getContents());
            return null;
        } catch (\Exception $e) {
            \Log::error('Guzzle Upload Error: ' . $e->getMessage());
            return null;
        }

        $fileCreateMutation = <<<'QUERY'
        mutation fileCreate($files: [FileCreateInput!]!) {
            fileCreate(files: $files) {
                files {
                    id
                    ... on GenericFile { url }
                    ... on MediaImage { image { url } }
                }
            }
        }
        QUERY;

        $finalResponse = $client->query([
            'query' => $fileCreateMutation,
            'variables' => ['files' => [[
                'originalSource' => $target['resourceUrl'],
                'contentType' => 'FILE',
            ]]],
        ]);

        $body = $finalResponse->getDecodedBody();
       

        $fileData = $body['data']['fileCreate']['files'][0] ?? null;

        if (!isset($fileData['image']['url']) && isset($fileData['id'])) {
    
            sleep(4);

            $fetchQuery = <<<'QUERY'
            query($id: ID!) {
                node(id: $id) {
                    ... on MediaImage { image { url } }
                    ... on GenericFile { url }
                }
            }
            QUERY;

            $retryResponse = $client->query([
                'query' => $fetchQuery,
                'variables' => ['id' => $fileData['id']]
            ]);
            $node = $retryResponse->getDecodedBody()['data']['node'] ?? null;
            return $node['image']['url'] ?? $node['url'] ?? null;
        }

        return $fileData['image']['url'] ?? $fileData['url'] ?? null;
    }

    private function ensureHelperProductExists($shopModel)
    {
        if (!$shopModel || empty($shopModel->shop) || empty($shopModel->access_token)) {
            \Log::error("Helper product provisioning failed: Shop model or access token missing");
            return null;
        }

        try {
            $this->initializeAPI('PRODUCTCUSTOMOPTION');
            $client = new \Shopify\Clients\Graphql($shopModel->shop, $shopModel->access_token);

            // 1. Query Shopify for existing helper product & Shop Metafield
            $query = <<<'QUERY'
            query getHelperProduct {
                products(first: 5, query: "title:'Custom Option Add-On'") {
                    nodes {
                        id
                        title
                        status
                        variants(first: 5) {
                            nodes {
                                id
                                title
                                price
                            }
                        }
                    }
                }
                shop {
                    id
                    metafield(namespace: "test_app", key: "helper_variant_id") {
                        value
                    }
                }
            }
            QUERY;

            $response = $client->query(['query' => $query]);
            $body = $response->getDecodedBody();

            $products = $body['data']['products']['nodes'] ?? [];
            $storedMetafieldVal = $body['data']['shop']['metafield']['value'] ?? null;
            $shopGid = $body['data']['shop']['id'] ?? null;

            // Check if existing product node matching title "Custom Option Add-On" exists
            foreach ($products as $prod) {
                if (($prod['title'] ?? '') === 'Custom Option Add-On') {
                    $firstVariant = $prod['variants']['nodes'][0] ?? null;
                    if ($firstVariant && !empty($firstVariant['id'])) {
                        $variantGid = $firstVariant['id'];

                        if ($storedMetafieldVal !== $variantGid && $shopGid) {
                            $this->saveHelperVariantMetafield($client, $shopGid, $variantGid);
                        }

                        \Log::info("Existing helper variant found for {$shopModel->shop}: {$variantGid}");
                        return $variantGid;
                    }
                }
            }

            // 2. Helper product not found; create it using productCreate GraphQL mutation
            $createMutation = <<<'QUERY'
            mutation productCreate($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                        title
                        status
                        variants(first: 1) {
                            nodes {
                                id
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            QUERY;

            $variables = [
                'input' => [
                    'title' => 'Custom Option Add-On',
                    'status' => 'ACTIVE',
                    'productType' => 'Custom Option Add-On',
                    'vendor' => 'Product Custom Option',
                    'tags' => ['custom-option-hidden', 'nopublish'],
                    'descriptionHtml' => '<p>Internal system product for custom option add-on pricing. Do not delete.</p>',
                ]
            ];

            $createResponse = $client->query([
                'query' => $createMutation,
                'variables' => $variables
            ]);

            $createBody = $createResponse->getDecodedBody()['data']['productCreate'] ?? [];
            $userErrors = $createBody['userErrors'] ?? [];

            if (!empty($userErrors)) {
                \Log::error("Error creating helper product for {$shopModel->shop}: " . json_encode($userErrors));
                return null;
            }

            $newVariantGid = $createBody['product']['variants']['nodes'][0]['id'] ?? null;

            if ($newVariantGid) {
                \Log::info("Successfully created helper product variant for {$shopModel->shop}: {$newVariantGid}");

                if ($shopGid) {
                    $this->saveHelperVariantMetafield($client, $shopGid, $newVariantGid);
                }

                return $newVariantGid;
            }

            \Log::error("Failed to extract new variant GID for {$shopModel->shop}");
            return null;

        } catch (\Exception $e) {
            \Log::error("Exception in ensureHelperProductExists for {$shopModel->shop}: " . $e->getMessage());
            return null;
        }
    }

    private function saveHelperVariantMetafield($client, $shopGid, $variantGid)
    {
        try {
            $mutation = <<<'QUERY'
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
            QUERY;

            $variables = [
                'metafields' => [
                    [
                        'ownerId' => $shopGid,
                        'namespace' => 'test_app',
                        'key' => 'helper_variant_id',
                        'type' => 'single_line_text_field',
                        'value' => $variantGid,
                    ]
                ]
            ];

            $client->query([
                'query' => $mutation,
                'variables' => $variables
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to save helper_variant_id metafield: " . $e->getMessage());
        }
    }
}

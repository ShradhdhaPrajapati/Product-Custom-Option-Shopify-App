<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
use Illuminate\Support\Facades\Log;
class ShopifyAuthController extends Controller
{
   public function initializeAPI($appId)
   {
       if($appId)
       {
           Context::initialize(
            apiKey: env($appId.'_API_KEY'),
            apiSecretKey: env($appId.'_API_SECRET'),
            scopes: explode(',',env($appId.'_APP_SCOPES')), 
            hostName: env('SHOPIFY_APP_HOST_NAME'),
            isEmbeddedApp: true,
            sessionStorage: new FileSessionStorage('/tmp/php_sessions') // ✅ Store sessions in files
        ); 
       }
   }
   public function validateAppInstallation($appId,Request $request)
   {
	$shop = $request->query('shop');
	$host = $request->query('host');

	$appId = strtoupper($appId);   
	if (!$shop || !$appId) 
	{
	 return response()->json(['error' => 'Missing shop parameter'], 400);
	}  
	$data = DB::table('shops')
	    ->select('isApp_Enable', 'shop','app_id')
	    ->where('shop', $shop)
    	    ->where('app_id', $appId)
	    ->where('access_token','!=', "")
	    ->get()->first();


	if(!empty($data)) 
	{
	  return redirect()->route('welcome',['shop'=>$shop,'host'=>$host,'appId'=>$appId]);
	}

	$apiKey = env($appId.'_API_KEY');
	$scopes = env($appId.'_APP_SCOPES');
	$redirectUri = env('SHOPIFY_REDIRECT_URI'); 
	$response = ['message' => 'App Installed Successfully', 'shop' => $shop,'accesskey' => $apiKey,'scope' =>   $scopes,'redirectUri' => $redirectUri,'state'=>$appId];
        return view('authorize', compact('response')); 
   }
   
   public function callbackAppInstallation(Request $request)
   {
        $shop = $request->query('shop');
   	$code = $request->query('code');
   	$appId = $request->query('state');
	if (!$shop || !$code || !$appId) {
           return response()->json(['error' => 'Missing parameters'], 400);
    	}
    	if($request->query('embedded'))
    	{
	      $accessTokenUrl = "https://$shop/admin/oauth/access_token";
	      $clientId = env($appId.'_API_KEY');
	      $clientSecret = env($appId.'_API_SECRET');
	      $response = Http::post($accessTokenUrl, [
		'client_id' => $clientId,
		'client_secret' => $clientSecret,
		'code' => $code,
	      ]);
	      if ($response->failed()) {
		  return response()->json(['error' => 'Failed to get access token'], 500);
	      }
	  
	      $accessToken = $response->json()['access_token'];
	      
	      $this->initializeAPI($appId);
	      
	      $shopify = new \Shopify\Clients\Rest($shop, $accessToken);
	      $apiVsnNo = env("API_VERSION_NUMBER");
	      $shopDetails = $shopify->get('/admin/api/'.$apiVsnNo.'/shop.json');	
              $body = $shopDetails->getDecodedBody();   
              $shopEmail = isset($body['shop'])?$body['shop']['email']:"";   
	
	      \App\Models\Shop::updateOrCreate(['shop' => $shop,'app_id' => $appId],['access_token' => $accessToken,'shop_email'=>$shopEmail]);

	      // Ensure Cart Transform registration
	      $this->ensureCartTransformRegistered($shop, $accessToken);

	      //Uninstall URL
	      $this->initializeAPI($appId);
	      $shopify = new \Shopify\Clients\Rest($shop, $accessToken);
	      $shopify->post('webhooks', [
	    	'webhook' => [
			'topic' => 'app/uninstalled',
			'address' => env("SHOPIFY_APP_URL").'/webhooks/app-uninstalled/'.$appId,
			'format' => 'json'
	   	 ]
	     ]);
    	}
    	$response = ['message' => 'App Installed Successfully', 'shop' => $shop, 'appId'=>$appId];
    	return view('getstarted', compact('response'));
   }

   public function ensureCartTransformRegistered($shop, $accessToken)
   {
        try {
            $apiVsnNo = env("API_VERSION_NUMBER", "2025-01");
            $url = "https://{$shop}/admin/api/{$apiVsnNo}/graphql.json";
            
            $query = <<<'GRAPHQL'
query {
  shopifyFunctions(first: 20) {
    nodes {
      id
      title
      apiType
    }
  }
  cartTransforms(first: 10) {
    nodes {
      id
      functionId
    }
  }
}
GRAPHQL;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $accessToken,
            ])->post($url, ['query' => $query]);

            if ($response->failed()) {
                Log::error("Failed to query shopifyFunctions/cartTransforms for $shop");
                return false;
            }

            $data = $response->json('data');
            $existingTransforms = $data['cartTransforms']['nodes'] ?? [];
            if (!empty($existingTransforms)) {
                Log::info("Cart Transform already registered for $shop");
                return true;
            }

            $functions = $data['shopifyFunctions']['nodes'] ?? [];
            $cartTransformFunction = null;
            foreach ($functions as $fn) {
                if (($fn['apiType'] ?? '') === 'cart_transform' || ($fn['title'] ?? '') === 'cart-transform-opt') {
                    $cartTransformFunction = $fn;
                    break;
                }
            }

            if (!$cartTransformFunction) {
                Log::warning("No Cart Transform function found on Shopify for $shop");
                return false;
            }

            $functionId = $cartTransformFunction['id'];
            $mutation = <<<GRAPHQL
mutation {
  cartTransformCreate(functionId: "{$functionId}") {
    cartTransform {
      id
      functionId
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;

            $createResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $accessToken,
            ])->post($url, ['query' => $mutation]);

            Log::info("Cart Transform registered for $shop: " . json_encode($createResponse->json()));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to ensure Cart Transform registration for $shop: " . $e->getMessage());
            return false;
        }
   }

   public function appUninstalled($appId,Request $request)
   {
        Log::info("App uninstalled ");
        $shop = $request->header('x-shopify-shop-domain');
        if (!$shop || !$appId) {
           return response()->json(['error' => 'Missing parameters'], 400);
    	}
        // Verify HMAC signature
        $hmac = $request->header('x-shopify-hmac-sha256');
        $calculatedHmac = base64_encode(hash_hmac('sha256', $request->getContent(), env($appId.'_API_SECRET'), true));

        if (!hash_equals($hmac, $calculatedHmac)) {
           return response()->json(['error' => 'Invalid HMAC code'], 401);
        }

	$shopModel = \App\Models\Shop::where('shop', $shop)->where('app_id', $appId)->first();
	if ($shopModel)
	{
	   $shopModel->access_token = "";
    	   $shopModel->save();
	}
		
	//\App\Models\Shop::where('shop', $shop)->where('appId', $appId)->delete();
        Log::info("App uninstalled by: $shop");

        return response()->json(['message' => 'App UnInstalled Successfully'], 200); 
        
   }
}

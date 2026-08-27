<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Shopify\Utils;
use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
use Illuminate\Support\Facades\Redirect;
class SettingsController extends Controller
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
    public function verifySessionToken(Request $request)
    {	  
        $token = $request->bearerToken(); // Get token from frontend
        $appId = $request->input('appId');
	if(!$token) 
	{
	   return response()->json(['error' => 'Unauthorized'], 401);
	}
	try 
	{	        
	   $this->initializeAPI($appId);
	   $payload = Utils::decodeSessionToken($token);
	   return response()->json(['message' => 'Authenticated', 'shop' => $payload['dest']]);
	} 
	catch (\Exception $e) 
	{
	   return response()->json(['error' => $e.getMessage()], 401);
	}
    }
    public function setAppStatus(Request $request)
    {
        try {
              $appId = $request->input('app_id') ? : '';
	      if(!$this->verifyToken($request,$appId))	
	      {
	        return response()->json(['message' => 'Invalid session'], 401);
	      }
              $statusLabel = $request->input('status') ? 'Active' : 'In-Activate';
            
              $status = $request->input('status');
              $shop = $request->input('shop') ? : '';
	      $shopModel = Shop::where('shop', $shop)->where('app_id', $appId)->first();
		
		if ($shopModel) {
		    $shopModel->isApp_Enable = $status;
		    $shopModel->save();
		    $accessToken = $shopModel->access_token;
	            $this->initializeAPI($appId);
		    $shopify = new \Shopify\Clients\Rest($shop, $accessToken);

		    $response = $shopify->post('/admin/api/2025-01/metafields.json', [
		       'metafield' => [
			'namespace' => strtolower($appId),
			'key' => 'enabled',
			'value' => (int)$status,
			'type' => 'integer'
		      ]
	  	    ]);

		    return response()->json([
				'message' => "$statusLabel",
				'status' => $status,
				'csrf_token' => csrf_token()
			     ]);
		}
		
        } catch (\Exception $e) {
            \Log::error('Error saving settings: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function getAppStatus(Request $request)
    {
        try {
    	      $shopDomain = $request->header('X-Shopify-Shop-Domain'); // Get Shopify shop domain
	      $shopModel = Shop::where('shop', $shopDomain)->first();
		if ($shopModel) {
		   return response()->json([
			'enabled' => $shopModel ? (bool) $shopModel->isApp_Enable : false
		    ]);
		}
	      return response()->json([
		'error' => "Shop domain invalid"
	     ]);

        } catch (\Exception $e) {
            \Log::error('Error saving settings: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function verifyToken($request,$appId)
    {
       $token = $request->bearerToken(); // Get token from frontend
       if(!$token) 
       {
        return response()->json(['error' => 'Unauthorized'], 401);
       }
       try 
       {
        $this->initializeAPI($appId);
        $payload = Utils::decodeSessionToken($token);
        return true;
       } 
       catch (\Exception $e) 
       {
        return false;
       }
    }
    public function welcomeToApp(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');
        $appId = $request->query('appId');
        if (!$shop || !$appId) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }
        $shopModel = \App\Models\Shop::where('shop', $shop)->where('app_id', $appId)->first();
        if ($shopModel) {
            $chargeId = $shopModel->charge_id;
            if (!empty($chargeId)) {
                $accessToken = $shopModel->access_token;
                $this->initializeAPI($appId);
                $shopify = new \Shopify\Clients\Rest($shop, $accessToken);
                $response = $shopify->get("recurring_application_charges/{$chargeId}");

                $body = $response->getDecodedBody();
                $status = isset($body['recurring_application_charge']['status']) ? $body['recurring_application_charge']['status'] : "inactive";
                if ($status == "active") {
                    $response = ['message' => 'App Installed Successfully', 'shop' => $shop, "host" => $host, 'appId' => $appId];
                    return view('welcome', compact('response'));
                }
            }

            $appPrice = env($appId . "_APPPRICE");

            if($appPrice == 0){
                $response = ['message' => 'App Installed Successfully', 'shop' => $shop, "host" => $host, 'appId' => $appId];
                return view('welcome', compact('response'));
            }

            $shopModel->isApp_Enable = 0;
            $shopModel->save();
        }
        $appName = env($appId . "_APPNAME");
        $appPrice = env($appId . "_APPPRICE");
        return redirect()->route('subscriptionfailed', ['shop' => $shop, 'host' => $host, 'appId' => $appId, 'appName' => $appName, 'appPrice' => $appPrice]);
    }

    public function appConfigurations(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');
        $appId = $request->query('appId');
        $appPrice = env($appId . "_APPPRICE");
        if (!$shop || !$appId) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        if ($appId === 'PRODUCTCUSTOMOPTION') {
            return redirect()->route('optionset.configuration', [
                'shop' => $shop,
                'host' => $host,
                'appId' => $appId,
            ]);
        }
        /*$data = DB::table('shops')
           ->select('isApp_Enable', 'shop','access_token','charge_id')
           ->where('shop', $shop)
           ->get()->first();*/

        $data = \App\Models\Shop::where('shop', $shop)->where('app_id', $appId)->first();

        $appIdConfig = strtolower($appId);
        $appIdConfig = ucwords($appIdConfig);   
        
        $configClass = '\App\Models\\'.$appIdConfig.'Configuration';
        $configData = [];
        
        if (class_exists($configClass)){
            $configData = app($configClass)::where('shop', $shop)->first();    
        }

        if($configData){
          $configData->toArray();    
        }
        
        if($appPrice > 0) {
            try {
                $accessToken = "";
                if ($data) {
                    $accessToken = $data->access_token;
                    $chargeId = $data->charge_id;
                }
                $this->initializeAPI($appId);
                $shopify = new \Shopify\Clients\Rest($shop, $accessToken);
                $response = $shopify->get("recurring_application_charges/{$chargeId}");

                $body = $response->getDecodedBody();
                $status = isset($body['recurring_application_charge']['status']) ? $body['recurring_application_charge']['status'] : "inactive";
                if ($status != "active") {
                    $data->isApp_Enable = 0;
                    $data->save();
                    $apiVsnNo = env("API_VERSION_NUMBER");
                    $response = $shopify->post('/admin/api/' . $apiVsnNo . '/metafields.json', [
                        'metafield' => [
                            'namespace' => strtolower($appId),
                            'key' => 'enabled',
                            'value' => 0,
                            'type' => 'integer'
                        ]
                    ]);
                    return redirect()->route('subscriptionfailed', ['shop' => $shop, 'host' => $host, 'appId' => $appId]);
                }
            } catch (\Exception $e) {
                return redirect()->route('subscriptionfailed', ['shop' => $shop, 'host' => $host, 'appId' => $appId]);
            }
        }
        $response = ['message' => 'Settings Updated Successfully', 'shop' => $shop,'appId'=>$appId,'isAppEnable'=>$data->isApp_Enable];
  		return view(strtolower($appId).'_configuration', compact('response'));
    }

    
     
    public function subscribeApp(Request $request)
    {	
    
        $shop = $request->query('shop');
      	$host = $request->query('host');
      	$appId = $request->query('appId');

	if (!$shop || !$appId) {
	return response()->json(['error' => 'Missing parameters'], 400);
	}
	try
	{
		$accessToken="";
		$shopModel = \App\Models\Shop::where('shop', $shop)->where('app_id', $appId)->first();
		if ($shopModel)
		{
		 $accessToken = $shopModel->access_token;
		}
		$appUrl = env("SHOPIFY_APP_URL");
		$apiVersion = env("API_VERSION_NUMBER");
		$this->initializeAPI($appId);
		$shopify = new \Shopify\Clients\Rest($shop, $accessToken);		
		
		$response = $shopify->post('/admin/api/'.$apiVersion.'/recurring_application_charges.json', [
			'recurring_application_charge' => [
			'name' => 'Business Plan',
			'price' => env($appId."_APPPRICE"),
			'trial_days' => 7,
			'return_url' => $appUrl.'/confirm_billing?shop=' . $shop.'&appId='.$appId,
			'test' => true
			]
		]);
		$body = $response->getDecodedBody();
		$confirmationUrl = $body['recurring_application_charge']['confirmation_url'];
	}
	catch(\Exception $e)
 	{
	  $appName = env($appId."_APPNAME");
    	  $appPrice = env($appId."_APPPRICE");  
    	  return redirect()->route('subscriptionfailed',['shop'=>$shop,'host'=>$host,'appId'=>$appId,'appName'=>$appName,'appPrice'=>$appPrice]);
 	}  
 	$response = ['confirmationUrl' => $confirmationUrl,'appId'=>$appId];
 	
    	return view('subscribe', compact('response'));
    }
    public function subscriptionFailed(Request $request)
    {
    	$shop = $request->query('shop');
    	$host = $request->query('host');
    	$appId = $request->query('appId');
    	if (!$shop && !$appId) {
     	   return response()->json(['error' => 'Missing parameters'], 400);
  	}
    	$appName = $request->query('appName');
    	$appPrice = $request->query('appPrice');
   	$response = ['message' => 'App Installed Successfully', 'shop' => $shop,"host"=>$host,"appId"=>$appId,'appName'=>$appName,'appPrice'=>$appPrice];
    	return view('subscriptionfailed', compact('response'));
    }
    public function appBillingConfirmation(Request $request)
    {
        $shop = $request->query('shop');
	$host = $request->query('host');
	$appId = $request->query('appId');
 	$chargeId = $request->query('charge_id');
	if (!$shop || !$chargeId || !$appId) 
	{
	   return response()->json(['error' => 'Subscription Failed'], 400);
	}
	$accessToken="";
	$shopModel = \App\Models\Shop::where('shop', $shop)->where('app_id', $appId)->first();

	if ($shopModel)
	{
	   $accessToken = $shopModel->access_token;
	}
        $this->initializeAPI($appId);
 	$shopify = new \Shopify\Clients\Rest($shop, $accessToken);
 	$response = $shopify->get("recurring_application_charges/{$chargeId}");

 	$body = $response->getDecodedBody();
 	$status = isset($body['recurring_application_charge']['status'])? $body['recurring_application_charge']['status']:"inactive";
 	if($status=="active")
 	{  
 	 $shopModel->charge_id = $chargeId;
 	 $shopModel->save();
  	return redirect()->route('welcome',['shop'=>$shop,'host'=>$host,'appId'=>$appId]);
 	}
 	else
 	{
	  $shopModel->isApp_Enable = 0;
	  $shopModel->save();
	  
	  $appName = env($appId."_APPNAME");
    	  $appPrice = env($appId."_APPPRICE");
    	  
    	  return redirect()->route('subscriptionfailed',['shop'=>$shop,'host'=>$host,'appId'=>$appId,'appName'=>$appName,'appPrice'=>$appPrice]);
    
 	}
    }
}

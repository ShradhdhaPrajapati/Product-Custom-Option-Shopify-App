<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
class ShopifyController extends Controller 
{
    public function getAllProducts(Request $request)
    {
    $shop = Auth::user(); // Get authenticated shop
 
       
    $sessionStoragee= new FileSessionStorage('/tmp/php_sessions');
   // $accessToken = \DB::table('shops')->where('shop', $shop->name)->value('access_token');

        Context::initialize(
    apiKey: $_ENV['SHOPIFY_API_KEY'],
    apiSecretKey: $_ENV['SHOPIFY_API_SECRET'],
    scopes: $_ENV['SHOPIFY_APP_SCOPES'],
    hostName: $_ENV['SHOPIFY_APP_HOST_NAME'],
    sessionStorage: new FileSessionStorage('/tmp/php_sessions'),
    apiVersion: '2025-01',
    isEmbeddedApp: true,
    isPrivateApp: false,
);

    	$token = $request->query('token');
      	$shopName = $request->query('shop');
        $headers=['Authorization'=>"Bearer $token"];
$shop = Auth::user(); // Get authenticated shop
$shopdd =$shop;
               // echo "HI<pre>";print_r($shopdd->); die();

	//$session = \Shopify\Utils::loadCurrentSession([],[],true);
	
	                                  echo "HI1<pre>";print_r($request->query());die();
	// Create GraphQL client
	$client = new \Shopify\Clients\Graphql($shopName, $token);
	// Use `query` method and pass your query as `data`
	$queryString = <<<QUERY
	    {
		products (first: 10) {
		    edges {
		        node {
		            id
		            title
		            descriptionHtml
		        }
		    }
		}
	    }
	QUERY;
	$response = $client->query(data: $queryString);


          echo "HI<pre>";print_r($response);die();
    }
}

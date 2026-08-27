<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthenticateShopify

{
   
    public function handle($request, Closure $next):Response
    {
            
	 $response = $next($request);
        $shop = $request->query('shop');
         if ($shop) {
            $response->headers->set(
                'Content-Security-Policy',
                "frame-ancestors https://$shop https://admin.shopify.com;"
            );
        }
                return $response;

        /*$shop = session('shopify_domain');
        $accessToken = session('shopify_token');
        
    	if (!$shop) {
            return response()->json(['error' => Session::get('shopify_token')], 401);
        }
        return $next($request);*/
    }
}

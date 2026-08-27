<html lang="en">
<head>
    <meta name="shopify-api-key" content="{{ env($response['state'].'_API_KEY') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <script src="{{ asset('js/app-bridge.js') }}"></script>
    <script src="{{ asset('js/app-bridge-utils.js') }}"></script>	
    <script src="{{ asset('js/jquery.min.js') }}"></script>
</head>
<script>
  (async function () {
        const params = new URLSearchParams(window.location.search);
        const shop = params.get("shop");
        const authurl = "https://{{$response['shop']}}/admin/oauth/authorize?client_id={{$response['accesskey']}}&scope={{$response['scope']}}&redirect_uri={{$response['redirectUri']}}&state={{$response['state']}}";
        if (!shop) {
            console.error("Missing shop or host parameters.");
            return;
        }
        
        // Initialize App Bridge
        var AppBridge = window['app-bridge'];

	const config = {
	    apiKey: "{{ env($response['state'].'_API_KEY') }}",
	    host: new URLSearchParams(location.search).get("host"),
	    forceRedirect: true,
	    shopOrigin: shop
	};
	if (window.top !== window.self) {
        const app = AppBridge.createApp(config);
        const Redirect = AppBridge.actions.Redirect;
        Redirect.create(app).dispatch(Redirect.Action.REMOTE,authurl);
        
}
        
    })();
</script>


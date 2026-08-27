<html lang="en">
<head>
    <meta name="shopify-api-key" content="{{ env($response['appId'].'_API_KEY') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <script src="{{ asset('js/app-bridge.js') }}"></script>
    <script src="{{ asset('js/app-bridge-utils.js') }}"></script>
</head>
<!--<script src="https://unpkg.com/@shopify/app-bridge@3.7.10/umd/index.js"></script>
<script src="https://unpkg.com/@shopify/app-bridge-utils@3.5.1/umd/index.js"></script>-->
<script>
  (async function () {
        const params = new URLSearchParams(window.location.search);
        const shop = params.get("shop");
        const authurl = "{{ $response['confirmationUrl'] }}";
        if (!shop) {
            console.error("Missing shop or host parameters.");
            return;
        }
        // Initialize App Bridge
        var AppBridge = window['app-bridge'];

	const config = {
	    apiKey: "{{ env($response['appId'].'_API_KEY') }}",
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

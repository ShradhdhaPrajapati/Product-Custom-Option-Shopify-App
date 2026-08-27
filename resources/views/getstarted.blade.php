<html lang="en">
<head>
    <meta name="shopify-api-key" content="{{ env($response['appId'].'_API_KEY') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <script src="{{ asset('js/app-bridge.js') }}"></script>
    <script src="{{ asset('js/app-bridge-utils.js') }}"></script>
</head>
<div class="container">
        <h1>Welcome to Magecurious Shopify App</h1>
        <p>Enhance your store with powerful features and seamless integration.</p>
</div>
  <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Arial", sans-serif;
        }
        body {
            background-color: #f8f8f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #555;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #090a09;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: 0.3s;
        }
        ul {
            margin-top: 10px;
            padding-left: 20px;
        }
        ul li {
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
<!--<script src="https://unpkg.com/@shopify/app-bridge@3.7.10/umd/index.js"></script>
<script src="https://unpkg.com/@shopify/app-bridge-utils@3.5.1/umd/index.js"></script>-->
<script>
  (async function () {
        const params = new URLSearchParams(window.location.search);
        const shop = params.get("shop");

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
        const app = AppBridge.createApp(config);
        const Redirect = AppBridge.actions.Redirect;
        Redirect.create(app).dispatch(Redirect.Action.APP, "/welcome?appId={{ $response['appId'] }}");          
    })();
</script>

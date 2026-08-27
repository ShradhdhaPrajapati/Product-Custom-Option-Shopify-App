<html lang="en">
<head>
    <meta name="shopify-api-key" content="{{ env($response['appId'].'_API_KEY') }}" />
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
</head>
<div class="container">
        <h1>Welcome to Magecurious Shopify App</h1>
        <p><strong>{{ env($response['appId'].'_APPNAME') }}</strong></p>
        <p>Enhance your store with powerful features and seamless integration.</p>
        <a id="get-started-btn" href="auth/configuration?embedded=1&host={{$response['host']}}&shop={{ $response['shop'] }}&appId={{ $response['appId'] }}" class="btn" style="display:none">Manage App</a>
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
<script>
  (async function () {
        const params = new URLSearchParams(window.location.search);
        const shop = params.get("shop");

        if (!shop) {
            console.error("Missing shop or host parameters.");
            return;
        }
       
        document.getElementById("get-started-btn").style.display = "block";
        // Send token to Laravel backend
        var url = "<?php echo env('SHOPIFY_APP_URL')."/api/auth/verify?appId=".$response['appId'];?>";
   	fetch(url, {
    	method: "POST",
        headers: {
		"Content-Type": "application/json"
	      }
    	}).then(response => response.json())
    	  .then(data => console.log("Token Verified:", data))
     	 .catch(error => console.log("Error:", error));
      
    })();
</script>

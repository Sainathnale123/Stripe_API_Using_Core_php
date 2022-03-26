<?php 
// Product Details  
// Minimum amount is $0.50 US  
$productName = "Codex Demo Product";  
$productID = "DP12345";  
$productPrice = 55; 
$currency = "usd";  
/* 
 * Stripe API configuration 
 * Remember to switch to your live publishable and secret key in production! 
 * See your keys here: https://dashboard.stripe.com/account/apikeys 
 */ 
define('STRIPE_API_KEY', 'Private_Key'); 
define('STRIPE_PUBLISHABLE_KEY', 'Public_key'); 
define('STRIPE_SUCCESS_URL', 'payment-success.php'); //Payment success URL 
define('STRIPE_CANCEL_URL', 'payment-cancel.php'); //Payment cancel URL 
// Database configuration    
define('DB_HOST', 'localhost');   
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');   
define('DB_NAME', 'stripcheckout'); 
?>
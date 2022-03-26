<?php 
// Include configuration file  
require_once 'config.php'; 
 
// Include database connection file  
include_once 'dbConnect.php'; 
 
$payment_id = $statusMsg = ''; 
$status = 'error'; 
 
// Check whether stripe checkout session is not empty 
if(!empty($_GET['session_id'])){ 
    $session_id = $_GET['session_id']; 
     
    // Fetch transaction data from the database if already exists 
    $sqlQ = "SELECT * FROM transactions WHERE stripe_checkout_session_id = ?"; 
    $stmt = $db->prepare($sqlQ);  
    $stmt->bind_param("s", $db_session_id); 
    $db_session_id = $session_id; 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
 
    if($result->num_rows > 0){ 
        // Transaction details 
        $transData = $result->fetch_assoc(); 
        $payment_id = $transData['id']; 
        $transactionID = $transData['txn_id']; 
        $paidAmount = $transData['paid_amount']; 
        $paidCurrency = $transData['paid_amount_currency']; 
        $payment_status = $transData['payment_status']; 
         
        $customer_name = $transData['customer_name']; 
        $customer_email = $transData['customer_email']; 
         
        $status = 'success'; 
        $statusMsg = 'Your Payment has been Successful!'; 
    }else{ 
        // Include the Stripe PHP library 
        require_once 'stripe-php/init.php'; 
         
        // Set API key 
        \Stripe\Stripe::setApiKey(STRIPE_API_KEY); 
         
        // Fetch the Checkout Session to display the JSON result on the success page 
        try { 
            $checkout_session = \Stripe\Checkout\Session::retrieve($session_id); 
        } catch(Exception $e) {  
            $api_error = $e->getMessage();  
        } 
         
        if(empty($api_error) && $checkout_session){ 
            // Retrieve the details of a PaymentIntent 
            try { 
                $paymentIntent = \Stripe\PaymentIntent::retrieve($checkout_session->payment_intent); 
            } catch (\Stripe\Exception\ApiErrorException $e) { 
                $api_error = $e->getMessage(); 
            } 
             
            // Retrieves the details of customer 
            try { 
                $customer = \Stripe\Customer::retrieve($checkout_session->customer); 
            } catch (\Stripe\Exception\ApiErrorException $e) { 
                $api_error = $e->getMessage(); 
            } 
             
            if(empty($api_error) && $paymentIntent){  
                // Check whether the payment was successful 
                if(!empty($paymentIntent) && $paymentIntent->status == 'succeeded'){ 
                    // Transaction details  
                    $transactionID = $paymentIntent->id; 
                    $paidAmount = $paymentIntent->amount; 
                    $paidAmount = ($paidAmount/100); 
                    $paidCurrency = $paymentIntent->currency; 
                    $payment_status = $paymentIntent->status; 
                     
                    // Customer details 
                    $customer_name = $customer_email = ''; 
                    if(!empty($customer)){ 
                        $customer_name = !empty($customer->name)?$customer->name:''; 
                        $customer_email = !empty($customer->email)?$customer->email:''; 
                    } 
                     
                    // Check if any transaction data is exists already with the same TXN ID 
                    $sqlQ = "SELECT id FROM transactions WHERE txn_id = ?"; 
                    $stmt = $db->prepare($sqlQ);  
                    $stmt->bind_param("s", $db_txn_id); 
                    $db_txn_id = $transactionID; 
                    $stmt->execute(); 
                    $result = $stmt->get_result(); 
                    $prevRow = $result->fetch_assoc(); 
                     
                    if(!empty($prevRow)){ 
                        $payment_id = $prevRow['id']; 
                    }else{ 
                        // Insert transaction data into the database 
                        $sqlQ = "INSERT INTO transactions (customer_name,customer_email,item_name,item_number,item_price,item_price_currency,paid_amount,paid_amount_currency,txn_id,payment_status,stripe_checkout_session_id,created,modified) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())"; 
                        $stmt = $db->prepare($sqlQ); 
                        $stmt->bind_param("ssssdsdssss", $db_customer_name, $db_customer_email, $db_item_name, $db_item_number, $db_item_price, $db_item_price_currency, $db_paid_amount, $db_paid_amount_currency, $db_txn_id, $db_payment_status, $db_checkout_session_id); 
                        $db_customer_name = $customer_name; 
                        $db_customer_email = $customer_email; 
                        $db_item_name = $productName; 
                        $db_item_number = $productID; 
                        $db_item_price = $productPrice; 
                        $db_item_price_currency = $currency; 
                        $db_paid_amount = $paidAmount; 
                        $db_paid_amount_currency = $paidCurrency; 
                        $db_txn_id = $transactionID; 
                        $db_payment_status = $payment_status; 
                        $db_checkout_session_id = $session_id; 
                        $insert = $stmt->execute(); 
                         
                        if($insert){ 
                            $payment_id = $stmt->insert_id; 
                        } 
                    } 
                     
                    $status = 'success'; 
                    $statusMsg = 'Your Payment has been Successful!'; 
                }else{ 
                    $statusMsg = "Transaction has been failed!"; 
                } 
            }else{ 
                $statusMsg = "Unable to fetch the transaction details! $api_error";  
            } 
        }else{ 
            $statusMsg = "Invalid Transaction! $api_error";  
        } 
    } 
}else{ 
    $statusMsg = "Invalid Request!"; 
} 
?>

<?php if(!empty($payment_id)){ ?>
    <h1 class="<?php echo $status; ?>"><?php echo $statusMsg; ?></h1>
	
    <h4>Payment Information</h4>
    <p><b>Reference Number:</b> <?php echo $payment_id; ?></p>
    <p><b>Transaction ID:</b> <?php echo $transactionID; ?></p>
    <p><b>Paid Amount:</b> <?php echo $paidAmount.' '.$paidCurrency; ?></p>
    <p><b>Payment Status:</b> <?php echo $payment_status; ?></p>
	
    <h4>Customer Information</h4>
    <p><b>Name:</b> <?php echo $customer_name; ?></p>
    <p><b>Email:</b> <?php echo $customer_email; ?></p>
	
    <h4>Product Information</h4>
    <p><b>Name:</b> <?php echo $productName; ?></p>
    <p><b>Price:</b> <?php echo $productPrice.' '.$currency; ?></p>
<?php }else{ ?>
    <h1 class="error">Your Payment been failed!</h1>
    <p class="error"><?php echo $statusMsg; ?></p>
<?php } ?>
<?php
/**
 * Digiflazz API Integration
 */

class DigiflazzAPI {
    private $username;
    private $api_key;
    
    public function __construct($username, $api_key) {
        $this->username = $username;
        $this->api_key = $api_key;
    }
    
    // Get price list from Digiflazz
    public function getPriceList() {
        $sign_string = $this->username . $this->api_key . 'pricelist';
        $sign = md5($sign_string);
        
        $data = [
            'cmd' => 'prepaid',
            'username' => $this->username,
            'sign' => $sign
        ];
        
        $response = $this->makeRequest('https://api.digiflazz.com/v1/price-list', $data);
        
        if ($response && isset($response['data'])) {
            return [true, $response['data']];
        } else {
            return [false, 'Failed to get price list'];
        }
    }
    
    // Process transaction
    public function processTransaction($product_code, $target_id, $ref_id) {
        $sign_string = $this->username . $this->api_key . $ref_id;
        $sign = md5($sign_string);
        
        $data = [
            'username' => $this->username,
            'buyer_sku_code' => $product_code,
            'customer_no' => $target_id,
            'ref_id' => $ref_id,
            'sign' => $sign
        ];
        
        $response = $this->makeRequest('https://api.digiflazz.com/v1/transaction', $data);
        
        if ($response && isset($response['data'])) {
            return [true, $response['data']];
        } else {
            return [false, $response['message'] ?? 'Transaction failed'];
        }
    }
    
    // Check transaction status
    public function checkTransactionStatus($ref_id) {
        $sign_string = $this->username . $this->api_key . $ref_id;
        $sign = md5($sign_string);
        
        $data = [
            'username' => $this->username,
            'buyer_sku_code' => '',
            'customer_no' => '',
            'ref_id' => $ref_id,
            'sign' => $sign
        ];
        
        $response = $this->makeRequest('https://api.digiflazz.com/v1/transaction', $data);
        
        if ($response && isset($response['data'])) {
            return [true, $response['data']];
        } else {
            return [false, 'Status check failed'];
        }
    }
    
    // Make HTTP request to Digiflazz API
    private function makeRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $decoded = json_decode($result, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            } else {
                error_log("Digiflazz JSON decode error: " . json_last_error_msg());
                return false;
            }
        } else {
            error_log("Digiflazz API Error: HTTP $http_code - $result");
            return false;
        }
    }
    
    // Update products in database
    public function updateProductsToDatabase($db) {
        $result = $this->getPriceList();
        
        if (!$result[0]) {
            return [false, $result[1]];
        }
        
        $products = $result[1];
        $pdo = $db->getConnection();
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Clear existing products
            $pdo->exec("TRUNCATE TABLE products");
            
            // Insert new products
            $stmt = $pdo->prepare("
                INSERT INTO products (name, price, digiflazz_code, description, brand, type, seller) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $count = 0;
            foreach ($products as $product) {
                if ($product['product_status'] && $product['seller_product_status']) {
                    $stmt->execute([
                        $product['product_name'],
                        $product['price'],
                        $product['buyer_sku_code'],
                        $product['desc'] ?? '',
                        $product['brand'],
                        $product['category'],
                        $product['seller_name']
                    ]);
                    $count++;
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            return [true, "Successfully updated $count products"];
            
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
            error_log("Product update error: " . $e->getMessage());
            return [false, "Database error: " . $e->getMessage()];
        }
    }
}
?>
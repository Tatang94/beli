import requests
import hashlib
import json
import logging
from datetime import datetime
from config import DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY, DIGIFLAZZ_PRICELIST_URL, DIGIFLAZZ_TRANSACTION_URL

logger = logging.getLogger(__name__)

def generate_signature(username, api_key, ref_id):
    """Generate MD5 signature for Digiflazz API"""
    sign_string = f"{username}{api_key}{ref_id}"
    return hashlib.md5(sign_string.encode()).hexdigest()

def get_products_from_api():
    """Fetch products from Digiflazz API"""
    try:
        # Generate signature for price list request
        ref_id = f"pricelist_{int(datetime.now().timestamp())}"
        signature = generate_signature(DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY, ref_id)
        
        payload = {
            "cmd": "prepaid",
            "username": DIGIFLAZZ_USERNAME,
            "sign": signature
        }
        
        headers = {
            'Content-Type': 'application/json',
        }
        
        response = requests.post(DIGIFLAZZ_PRICELIST_URL, 
                               data=json.dumps(payload), 
                               headers=headers, 
                               timeout=30)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('data'):
                return True, data['data']
            else:
                return False, "No data received from API"
        else:
            return False, f"API returned status code: {response.status_code}"
            
    except requests.exceptions.Timeout:
        logger.error("Timeout error when fetching products from Digiflazz")
        return False, "Request timeout"
    except requests.exceptions.RequestException as e:
        logger.error(f"Request error when fetching products: {e}")
        return False, f"Request error: {str(e)}"
    except Exception as e:
        logger.error(f"Unexpected error when fetching products: {e}")
        return False, f"Unexpected error: {str(e)}"

def process_transaction(product_code, target_id, ref_id):
    """Process transaction through Digiflazz API"""
    try:
        # Generate signature for transaction
        signature = generate_signature(DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY, ref_id)
        
        payload = {
            "username": DIGIFLAZZ_USERNAME,
            "buyer_sku_code": product_code,
            "customer_no": target_id,
            "ref_id": ref_id,
            "sign": signature
        }
        
        headers = {
            'Content-Type': 'application/json',
        }
        
        response = requests.post(DIGIFLAZZ_TRANSACTION_URL,
                               data=json.dumps(payload),
                               headers=headers,
                               timeout=60)
        
        if response.status_code == 200:
            data = response.json()
            return True, data
        else:
            return False, f"API returned status code: {response.status_code}"
            
    except requests.exceptions.Timeout:
        logger.error("Timeout error when processing transaction")
        return False, "Transaction timeout"
    except requests.exceptions.RequestException as e:
        logger.error(f"Request error when processing transaction: {e}")
        return False, f"Request error: {str(e)}"
    except Exception as e:
        logger.error(f"Unexpected error when processing transaction: {e}")
        return False, f"Unexpected error: {str(e)}"

def update_products_in_database():
    """Update products in database from Digiflazz API"""
    from database import get_db_connection
    
    success, products_data = get_products_from_api()
    
    if not success:
        return False, products_data
    
    try:
        conn = get_db_connection()
        
        # Clear existing products
        conn.execute('DELETE FROM products')
        
        # Insert new products
        inserted_count = 0
        for product in products_data:
            try:
                conn.execute('''
                INSERT INTO products (name, price, digiflazz_code, description, brand, type, seller)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ''', (
                    product.get('product_name', ''),
                    int(float(product.get('price', 0))),
                    product.get('buyer_sku_code', ''),
                    product.get('desc', ''),
                    product.get('brand', ''),
                    product.get('category', ''),
                    product.get('seller_name', '')
                ))
                inserted_count += 1
            except Exception as e:
                logger.error(f"Error inserting product {product.get('buyer_sku_code', 'unknown')}: {e}")
                continue
        
        conn.commit()
        conn.close()
        
        return True, f"Successfully updated {inserted_count} products"
        
    except Exception as e:
        logger.error(f"Error updating products in database: {e}")
        return False, f"Database error: {str(e)}"

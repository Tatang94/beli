import sqlite3
import logging
from datetime import datetime
from config import DATABASE_FILE, ADMIN_IDS

logger = logging.getLogger(__name__)

def init_db():
    """Initialize the database with all required tables"""
    conn = sqlite3.connect(DATABASE_FILE)
    cursor = conn.cursor()

    try:
        # Tabel users
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY,
            username TEXT,
            first_name TEXT,
            last_name TEXT,
            balance INTEGER DEFAULT 0,
            is_admin INTEGER DEFAULT 0,
            join_date TEXT
        )
        ''')

        # Tambahkan admin awal jika belum ada
        for admin_id in ADMIN_IDS:
            cursor.execute('SELECT 1 FROM users WHERE user_id = ?', (admin_id,))
            if not cursor.fetchone():
                cursor.execute('''
                INSERT INTO users (user_id, username, first_name, is_admin, join_date)
                VALUES (?, ?, ?, ?, ?)
                ''', (admin_id, 'admin', 'Admin', 1, datetime.now().strftime('%Y-%m-%d %H:%M:%S')))

        # Tabel products
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS products (
            product_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price INTEGER NOT NULL,
            digiflazz_code TEXT UNIQUE NOT NULL,
            description TEXT,
            brand TEXT,
            type TEXT,
            seller TEXT,
            status TEXT DEFAULT 'active'
        )
        ''')

        # Tabel transactions
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS transactions (
            transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER,
            target_id TEXT NOT NULL,
            amount INTEGER NOT NULL,
            digiflazz_refid TEXT,
            status TEXT DEFAULT 'pending',
            date TEXT NOT NULL,
            message TEXT,
            FOREIGN KEY(user_id) REFERENCES users(user_id),
            FOREIGN KEY(product_id) REFERENCES products(product_id)
        )
        ''')

        # Tabel deposits
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS deposits (
            deposit_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount INTEGER NOT NULL,
            method TEXT DEFAULT 'manual',
            status TEXT DEFAULT 'pending',
            proof TEXT,
            date TEXT NOT NULL,
            confirmed_by INTEGER,
            confirmed_date TEXT,
            FOREIGN KEY(user_id) REFERENCES users(user_id),
            FOREIGN KEY(confirmed_by) REFERENCES users(user_id)
        )
        ''')

        conn.commit()
        logger.info("Database initialized successfully")
    except Exception as e:
        logger.error(f"Error initializing database: {e}")
        conn.rollback()
    finally:
        conn.close()

def get_db_connection():
    """Get database connection with row factory"""
    conn = sqlite3.connect(DATABASE_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def is_admin(user_id):
    """Check if user is admin"""
    try:
        conn = get_db_connection()
        user = conn.execute('SELECT is_admin FROM users WHERE user_id = ?', (user_id,)).fetchone()
        conn.close()
        return user and user['is_admin'] == 1
    except Exception as e:
        logger.error(f"Error checking admin status: {e}")
        return False

def get_user_balance(user_id):
    """Get user balance"""
    try:
        conn = get_db_connection()
        user = conn.execute('SELECT balance FROM users WHERE user_id = ?', (user_id,)).fetchone()
        conn.close()
        return user['balance'] if user else 0
    except Exception as e:
        logger.error(f"Error getting user balance: {e}")
        return 0

def register_user(user):
    """Register new user or update existing user info"""
    try:
        conn = get_db_connection()
        existing_user = conn.execute('SELECT 1 FROM users WHERE user_id = ?', (user.id,)).fetchone()
        
        if not existing_user:
            conn.execute('''
            INSERT INTO users (user_id, username, first_name, last_name, join_date)
            VALUES (?, ?, ?, ?, ?)
            ''', (user.id, user.username or '', user.first_name or '', 
                  user.last_name or '', datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
        else:
            # Update user info
            conn.execute('''
            UPDATE users SET username = ?, first_name = ?, last_name = ?
            WHERE user_id = ?
            ''', (user.username or '', user.first_name or '', user.last_name or '', user.id))
        
        conn.commit()
        conn.close()
        return True
    except Exception as e:
        logger.error(f"Error registering user: {e}")
        return False

def update_user_balance(user_id, amount, operation='add'):
    """Update user balance"""
    try:
        conn = get_db_connection()
        current_balance = get_user_balance(user_id)
        
        if operation == 'add':
            new_balance = current_balance + amount
        elif operation == 'subtract':
            if current_balance < amount:
                conn.close()
                return False, "Insufficient balance"
            new_balance = current_balance - amount
        else:
            new_balance = amount
            
        conn.execute('UPDATE users SET balance = ? WHERE user_id = ?', (new_balance, user_id))
        conn.commit()
        conn.close()
        return True, new_balance
    except Exception as e:
        logger.error(f"Error updating user balance: {e}")
        return False, str(e)

def create_deposit(user_id, amount, method='manual'):
    """Create new deposit record"""
    try:
        conn = get_db_connection()
        cursor = conn.execute('''
        INSERT INTO deposits (user_id, amount, method, date)
        VALUES (?, ?, ?, ?)
        ''', (user_id, amount, method, datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
        
        deposit_id = cursor.lastrowid
        conn.commit()
        conn.close()
        return deposit_id
    except Exception as e:
        logger.error(f"Error creating deposit: {e}")
        return None

def get_pending_deposits():
    """Get all pending deposits"""
    try:
        conn = get_db_connection()
        deposits = conn.execute('''
        SELECT d.*, u.first_name, u.username 
        FROM deposits d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE d.status = 'pending' 
        ORDER BY d.date DESC
        ''').fetchall()
        conn.close()
        return deposits
    except Exception as e:
        logger.error(f"Error getting pending deposits: {e}")
        return []

def confirm_deposit(deposit_id, admin_id):
    """Confirm deposit and update user balance"""
    try:
        conn = get_db_connection()
        deposit = conn.execute('SELECT * FROM deposits WHERE deposit_id = ? AND status = "pending"', 
                             (deposit_id,)).fetchone()
        
        if not deposit:
            conn.close()
            return False, "Deposit not found or already processed"
        
        # Update deposit status
        conn.execute('''
        UPDATE deposits SET status = 'confirmed', confirmed_by = ?, confirmed_date = ?
        WHERE deposit_id = ?
        ''', (admin_id, datetime.now().strftime('%Y-%m-%d %H:%M:%S'), deposit_id))
        
        # Update user balance
        success, result = update_user_balance(deposit['user_id'], deposit['amount'], 'add')
        
        if success:
            conn.commit()
            conn.close()
            return True, result
        else:
            conn.rollback()
            conn.close()
            return False, result
            
    except Exception as e:
        logger.error(f"Error confirming deposit: {e}")
        return False, str(e)

def create_transaction(user_id, product_id, target_id, amount, digiflazz_refid=None):
    """Create new transaction record"""
    try:
        conn = get_db_connection()
        cursor = conn.execute('''
        INSERT INTO transactions (user_id, product_id, target_id, amount, digiflazz_refid, date)
        VALUES (?, ?, ?, ?, ?, ?)
        ''', (user_id, product_id, target_id, amount, digiflazz_refid, 
              datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
        
        transaction_id = cursor.lastrowid
        conn.commit()
        conn.close()
        return transaction_id
    except Exception as e:
        logger.error(f"Error creating transaction: {e}")
        return None

def update_transaction_status(transaction_id, status, message=None):
    """Update transaction status"""
    try:
        conn = get_db_connection()
        if message:
            conn.execute('UPDATE transactions SET status = ?, message = ? WHERE transaction_id = ?',
                        (status, message, transaction_id))
        else:
            conn.execute('UPDATE transactions SET status = ? WHERE transaction_id = ?',
                        (status, transaction_id))
        conn.commit()
        conn.close()
        return True
    except Exception as e:
        logger.error(f"Error updating transaction status: {e}")
        return False

def get_bot_stats():
    """Get bot statistics"""
    try:
        conn = get_db_connection()
        
        total_users = conn.execute('SELECT COUNT(*) as count FROM users').fetchone()['count']
        total_transactions = conn.execute('SELECT COUNT(*) as count FROM transactions').fetchone()['count']
        total_deposits = conn.execute('SELECT COUNT(*) as count FROM deposits WHERE status = "confirmed"').fetchone()['count']
        pending_deposits = conn.execute('SELECT COUNT(*) as count FROM deposits WHERE status = "pending"').fetchone()['count']
        total_products = conn.execute('SELECT COUNT(*) as count FROM products WHERE status = "active"').fetchone()['count']
        
        conn.close()
        
        return {
            'total_users': total_users,
            'total_transactions': total_transactions,
            'total_deposits': total_deposits,
            'pending_deposits': pending_deposits,
            'total_products': total_products
        }
    except Exception as e:
        logger.error(f"Error getting bot stats: {e}")
        return {}

import os
import sqlite3
import logging
from datetime import datetime
import requests
import hashlib
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import (
    Application,
    CommandHandler,
    MessageHandler,
    filters,
    ContextTypes,
    CallbackQueryHandler,
    ConversationHandler,
)

# Konfigurasi Bot dan API
TOKEN = os.getenv("TELEGRAM_BOT_TOKEN", "8216106872:AAEQ_DxjYtZL0t6vD-y4Pfj90c94wHgXDcc")
DIGIFLAZZ_USERNAME = os.getenv("DIGIFLAZZ_USERNAME", "miwewogwOZ2g")
DIGIFLAZZ_KEY = os.getenv("DIGIFLAZZ_KEY", "8c2f1f52-6e36-56de-a1cd-3662bd5eb375")
ADMIN_IDS = [7044289974]  # Ganti dengan ID admin Anda

# Setup logging
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

# States untuk ConversationHandler
MENU_UTAMA, ADMIN_MENU, DEPOSIT_FLOW, CONFIRM_DEPOSIT_FLOW, BUY_FLOW, TRANSACTION_ID_FLOW = range(6)
ADMIN_DELETE_PRODUCT_FLOW, ADMIN_MANAGE_FLOW, WAITING_TARGET_ID, CONFIRMING_PURCHASE = range(6, 10)
WAITING_DEPOSIT_AMOUNT, WAITING_DEPOSIT_PROOF, ADMIN_CONFIRM_DEPOSIT = range(10, 13)
SHOW_CATEGORY, SHOW_BRAND, SHOW_PRODUCT, ADMIN_MARGIN_SETTING = range(13, 17)

# Inisialisasi database
def init_db():
    conn = sqlite3.connect('bot_database.db')
    cursor = conn.cursor()

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
            ''', (admin_id, 'admin_username', 'Admin', 1, datetime.now().strftime('%Y-%m-%d %H:%M:%S')))

    # Tabel products (diperbarui untuk data dari Digiflazz)
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS products (
        product_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        price INTEGER,
        digiflazz_code TEXT,
        description TEXT,
        brand TEXT,
        type TEXT,
        seller TEXT
    )
    ''')

    # Tabel transactions
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS transactions (
        transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        product_id INTEGER,
        amount INTEGER,
        digiflazz_refid TEXT,
        status TEXT,
        date TEXT,
        target_id TEXT,
        FOREIGN KEY(user_id) REFERENCES users(user_id),
        FOREIGN KEY(product_id) REFERENCES products(product_id)
    )
    ''')

    # Tabel deposits
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS deposits (
        deposit_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        amount INTEGER,
        method TEXT,
        status TEXT DEFAULT 'pending',
        proof TEXT,
        date TEXT,
        FOREIGN KEY(user_id) REFERENCES users(user_id)
    )
    ''')

    # Tabel settings untuk margin
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS settings (
        setting_id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_name TEXT UNIQUE,
        setting_value TEXT,
        updated_date TEXT
    )
    ''')
    
    # Set default margin jika belum ada
    cursor.execute('''
    INSERT OR IGNORE INTO settings (setting_name, setting_value, updated_date)
    VALUES ('margin_percentage', '10', ?)
    ''', (datetime.now().strftime('%Y-%m-%d %H:%M:%S'),))

    conn.commit()
    conn.close()

# Fungsi bantu database
def get_db_connection():
    conn = sqlite3.connect('bot_database.db')
    conn.row_factory = sqlite3.Row
    return conn

def is_admin(user_id):
    conn = get_db_connection()
    user = conn.execute('SELECT is_admin FROM users WHERE user_id = ?', (user_id,)).fetchone()
    conn.close()
    return user and user['is_admin'] == 1

def get_user_balance(user_id):
    conn = get_db_connection()
    user = conn.execute('SELECT balance FROM users WHERE user_id = ?', (user_id,)).fetchone()
    conn.close()
    return user['balance'] if user else 0

def get_margin_percentage():
    """Get current margin percentage from settings"""
    conn = get_db_connection()
    setting = conn.execute('SELECT setting_value FROM settings WHERE setting_name = ?', ('margin_percentage',)).fetchone()
    conn.close()
    return float(setting['setting_value']) if setting else 10.0

def set_margin_percentage(percentage):
    """Set margin percentage in settings"""
    conn = get_db_connection()
    conn.execute('''
    INSERT OR REPLACE INTO settings (setting_name, setting_value, updated_date)
    VALUES ('margin_percentage', ?, ?)
    ''', (str(percentage), datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
    conn.commit()
    conn.close()

def register_user(user):
    conn = get_db_connection()
    user_exists = conn.execute('SELECT 1 FROM users WHERE user_id = ?', (user.id,)).fetchone()
    if not user_exists:
        conn.execute('''
        INSERT INTO users (user_id, username, first_name, last_name, join_date)
        VALUES (?, ?, ?, ?, ?)
        ''', (user.id, user.username, user.first_name, user.last_name if user.last_name else '', datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
        conn.commit()
    conn.close()

async def safe_edit_message(query, text, reply_markup=None):
    """Safely edit message with fallback to new message"""
    try:
        await query.edit_message_text(text=text, reply_markup=reply_markup)
        return True
    except Exception as e:
        logger.warning(f"Failed to edit message: {e}")
        try:
            await query.message.reply_text(text=text, reply_markup=reply_markup)
            return True
        except Exception as e2:
            logger.error(f"Failed to send new message: {e2}")
            return False

# --- Handlers Umum (Main Menu & Admin Menu) ---
async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user = update.effective_user
    register_user(user)

    welcome_message = (
        f"👋 Halo {user.first_name}!\n\n"
        "Selamat datang di Bot Pulsa & PPOB Digital!\n"
        "Silakan pilih menu di bawah:"
    )

    keyboard = [[InlineKeyboardButton("🛍 Beli Produk", callback_data='buy_product')],
                [InlineKeyboardButton("💰 Deposit Saldo", callback_data='deposit')],
                [InlineKeyboardButton("💼 Cek Saldo", callback_data='check_balance')]]

    if is_admin(user.id):
        keyboard.append([InlineKeyboardButton("👑 Admin Menu", callback_data='admin_menu')])

    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text(welcome_message, reply_markup=reply_markup)
    return MENU_UTAMA

async def main_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    user = update.effective_user
    keyboard = [[InlineKeyboardButton("🛍 Beli Produk", callback_data='buy_product')],
                [InlineKeyboardButton("💰 Deposit Saldo", callback_data='deposit')],
                [InlineKeyboardButton("💼 Cek Saldo", callback_data='check_balance')]]

    if is_admin(user.id):
        keyboard.append([InlineKeyboardButton("👑 Admin Menu", callback_data='admin_menu')])

    reply_markup = InlineKeyboardMarkup(keyboard)
    await safe_edit_message(query, "📱 Menu Utama\n\nSilakan pilih menu di bawah:", reply_markup)
    return MENU_UTAMA

async def admin_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    keyboard = [[InlineKeyboardButton("➕ Update Produk", callback_data='update_products_from_api')],
                [InlineKeyboardButton("⚙️ Setting Margin", callback_data='margin_setting')],
                [InlineKeyboardButton("👥 Kelola Admin", callback_data='manage_admin_start')],
                [InlineKeyboardButton("📊 Statistik Bot", callback_data='bot_stats')],
                [InlineKeyboardButton("💵 Konfirmasi Deposit", callback_data='confirm_deposit_list')],
                [InlineKeyboardButton("🏠 Menu Utama", callback_data='main_menu')]]

    reply_markup = InlineKeyboardMarkup(keyboard)
    await safe_edit_message(query, "👑 Menu Admin\n\nSilakan pilih menu admin:", reply_markup)
    return ADMIN_MENU

async def check_balance(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    user_id = update.effective_user.id
    balance = get_user_balance(user_id)

    keyboard = [[InlineKeyboardButton("💰 Deposit", callback_data='deposit')],
                [InlineKeyboardButton("🏠 Menu Utama", callback_data='main_menu')]]

    reply_markup = InlineKeyboardMarkup(keyboard)
    await safe_edit_message(query, f"💼 Saldo Anda\n\n💰 Saldo: Rp {balance:,}\n\nSilakan pilih menu di bawah:", reply_markup)
    return MENU_UTAMA

# --- Handler Beli Produk (BUY_FLOW) ---
async def buy_product_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer("Mengambil daftar kategori produk...")

    conn = get_db_connection()
    categories = conn.execute('SELECT DISTINCT type FROM products ORDER BY type').fetchall()
    conn.close()

    if not categories:
        await safe_edit_message(query, "❌ Maaf, saat ini tidak ada produk yang tersedia.")
        return MENU_UTAMA

    keyboard = [[InlineKeyboardButton(cat['type'].title(), callback_data=f"show_category_{cat['type']}")] for cat in categories]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await safe_edit_message(query, "📱 Pilih Kategori Produk\n\nSilakan pilih kategori produk yang ingin Anda beli:\n\n💡 Gunakan /start untuk kembali ke menu utama", reply_markup)
    return SHOW_CATEGORY

async def show_brands_by_category(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer("Mengambil daftar brand...")
    category = query.data.split('_', 2)[2]
    context.user_data['selected_category'] = category

    conn = get_db_connection()
    brands = conn.execute('SELECT DISTINCT brand FROM products WHERE type = ? ORDER BY brand', (category,)).fetchall()
    conn.close()

    if not brands:
        await safe_edit_message(query, f"❌ Tidak ada brand tersedia dalam kategori {category}.")
        return SHOW_CATEGORY

    keyboard = [[InlineKeyboardButton(brand['brand'], callback_data=f"show_brand_{brand['brand']}")] for brand in brands]
    reply_markup = InlineKeyboardMarkup(keyboard)

    await safe_edit_message(query, f"🏪 Brand {category.title()}\n\nSilakan pilih brand yang ingin Anda beli:\n\n💡 Gunakan /start untuk kembali ke menu utama", reply_markup)
    return SHOW_BRAND

async def show_products_by_brand(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer("Mengambil daftar produk...")
    brand = query.data.split('_', 2)[2]
    category = context.user_data.get('selected_category')
    context.user_data['selected_brand'] = brand

    conn = get_db_connection()
    products = conn.execute('SELECT * FROM products WHERE brand = ? AND type = ? ORDER BY price', (brand, category)).fetchall()
    conn.close()

    if not products:
        await safe_edit_message(query, f"❌ Tidak ada produk tersedia untuk brand {brand}.")
        return SHOW_BRAND

    # Apply margin to prices
    margin_percentage = get_margin_percentage()
    keyboard = []
    for p in products:
        final_price = int(p['price'] * (1 + margin_percentage / 100))
        keyboard.append([InlineKeyboardButton(f"{p['name']} - Rp {final_price:,}", callback_data=f"select_product_{p['product_id']}")])
    
    reply_markup = InlineKeyboardMarkup(keyboard)

    await safe_edit_message(query, f"📋 Produk {brand} - {category.title()}\n\nSilakan pilih produk yang ingin Anda beli:\n\n💡 Gunakan /start untuk kembali ke menu utama", reply_markup)
    return SHOW_PRODUCT

async def select_product(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()
    product_id = int(query.data.split('_')[-1])

    conn = get_db_connection()
    product = conn.execute('SELECT * FROM products WHERE product_id = ?', (product_id,)).fetchone()
    conn.close()

    if not product:
        await safe_edit_message(query, "❌ Produk tidak ditemukan.")
        return SHOW_PRODUCT

    # Calculate final price with margin
    margin_percentage = get_margin_percentage()
    final_price = int(product['price'] * (1 + margin_percentage / 100))
    
    context.user_data['selected_product'] = product_id
    context.user_data['final_price'] = final_price
    
    await safe_edit_message(query, 
        f"📋 Detail Produk\n\n🏷 Nama: {product['name']}\n💰 Harga: Rp {final_price:,}\n📝 Deskripsi: {product['description']}\n\nSilakan kirimkan ID tujuan (misal: nomor HP, nomor token PLN, dll):"
    )
    return WAITING_TARGET_ID

async def get_target_id(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    target_id = update.message.text
    context.user_data['target_id'] = target_id

    product_id = context.user_data.get('selected_product')
    conn = get_db_connection()
    product = conn.execute('SELECT * FROM products WHERE product_id = ?', (product_id,)).fetchone()
    conn.close()

    if not product:
        await update.message.reply_text("❌ Terjadi kesalahan. Silakan mulai pembelian dari awal.")
        return await start(update, context)

    user_id = update.effective_user.id
    balance = get_user_balance(user_id)
    final_price = context.user_data.get('final_price', product['price'])

    if balance < final_price:
        keyboard = [[InlineKeyboardButton("💰 Deposit Sekarang", callback_data='deposit')],
                    [InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await update.message.reply_text(
            f"❌ Saldo tidak mencukupi!\n\n💰 Saldo Anda: Rp {balance:,}\n💳 Harga Produk: Rp {final_price:,}\n⚠️ Kurang: Rp {final_price - balance:,}",
            reply_markup=reply_markup
        )
        return MENU_UTAMA

    keyboard = [[InlineKeyboardButton("✅ Konfirmasi Pembelian", callback_data='confirm_purchase')],
                [InlineKeyboardButton("❌ Batal", callback_data='main_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await update.message.reply_text(
        f"📋 Konfirmasi Pembelian\n\n🏷 Produk: {product['name']}\n🎯 Target: {target_id}\n💰 Harga: Rp {final_price:,}\n💼 Saldo Anda: Rp {balance:,}\n💳 Sisa Saldo: Rp {balance - final_price:,}\n\nApakah Anda yakin ingin melakukan pembelian?",
        reply_markup=reply_markup
    )
    return CONFIRMING_PURCHASE

def process_digiflazz_transaction(product_code, target_id, ref_id):
    """Process transaction through Digiflazz API"""
    try:
        username = DIGIFLAZZ_USERNAME
        api_key = DIGIFLAZZ_KEY
        
        # Create signature
        sign_string = f"{username}{api_key}{ref_id}"
        sign = hashlib.md5(sign_string.encode()).hexdigest()
        
        payload = {
            "username": username,
            "buyer_sku_code": product_code,
            "customer_no": target_id,
            "ref_id": ref_id,
            "sign": sign
        }
        
        response = requests.post("https://api.digiflazz.com/v1/transaction", json=payload, timeout=30)
        
        if response.status_code == 200:
            data = response.json()
            return True, data
        else:
            return False, f"API returned status: {response.status_code}"
            
    except Exception as e:
        logger.error(f"Error processing Digiflazz transaction: {e}")
        return False, str(e)

async def confirm_purchase(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer("Memproses pembelian...")

    user_id = update.effective_user.id
    product_id = context.user_data.get('selected_product')
    target_id = context.user_data.get('target_id')
    final_price = context.user_data.get('final_price')

    conn = get_db_connection()
    product = conn.execute('SELECT * FROM products WHERE product_id = ?', (product_id,)).fetchone()
    balance = get_user_balance(user_id)

    # Create transaction reference
    ref_id = f"TRX{int(datetime.now().timestamp())}{user_id}"
    
    # Process with Digiflazz API
    success, result = process_digiflazz_transaction(product['digiflazz_code'], target_id, ref_id)
    
    if success:
        # Deduct balance using final price with margin
        new_balance = balance - final_price
        conn.execute('UPDATE users SET balance = ? WHERE user_id = ?', (new_balance, user_id))

        # Create transaction record
        conn.execute('''
        INSERT INTO transactions (user_id, product_id, amount, digiflazz_refid, status, date, target_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', (user_id, product_id, final_price, ref_id, 'success', datetime.now().strftime('%Y-%m-%d %H:%M:%S'), target_id))
        
        conn.commit()
        
        keyboard = [[InlineKeyboardButton("🔙 Kembali ke Menu", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        
        await safe_edit_message(query, 
            f"✅ Pembelian Berhasil!\n\n🏷 Produk: {product['name']}\n🎯 Target: {target_id}\n💰 Harga: Rp {final_price:,}\n📋 Ref ID: {ref_id}\n💼 Sisa Saldo: Rp {new_balance:,}\n\n✨ Terima kasih telah menggunakan layanan kami!",
            reply_markup
        )
    else:
        # Transaction failed, don't deduct balance
        conn.execute('''
        INSERT INTO transactions (user_id, product_id, amount, digiflazz_refid, status, date, target_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', (user_id, product_id, final_price, ref_id, 'failed', datetime.now().strftime('%Y-%m-%d %H:%M:%S'), target_id))
        
        conn.commit()
        
        keyboard = [[InlineKeyboardButton("🔄 Coba Lagi", callback_data='buy_product')],
                    [InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        
        await safe_edit_message(query, 
            f"❌ Pembelian Gagal!\n\n🏷 Produk: {product['name']}\n🎯 Target: {target_id}\n📋 Ref ID: {ref_id}\n⚠️ Error: {result}\n\n💰 Saldo Anda tidak dikurangi.",
            reply_markup
        )
    
    conn.close()
    context.user_data.clear()
    return MENU_UTAMA

# --- Handler Deposit (DEPOSIT_FLOW) ---
async def deposit_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    await safe_edit_message(query, 
        "💰 Deposit Saldo\n\nSilakan masukkan jumlah deposit yang ingin Anda lakukan:\n(Minimal Rp 10.000)"
    )
    return WAITING_DEPOSIT_AMOUNT

async def get_deposit_amount(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    try:
        amount = int(update.message.text.replace('.', '').replace(',', '').replace('Rp', '').strip())
        
        if amount < 10000:
            await update.message.reply_text("❌ Minimal deposit adalah Rp 10.000. Silakan masukkan jumlah yang valid.")
            return WAITING_DEPOSIT_AMOUNT
        
        context.user_data['deposit_amount'] = amount
        
        keyboard = [[InlineKeyboardButton("📷 Upload Bukti Transfer", callback_data='upload_proof')],
                    [InlineKeyboardButton("❌ Batal", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        
        await update.message.reply_text(
            f"💰 Deposit: Rp {amount:,}\n\n📋 Instruksi Pembayaran:\n\n🏦 Bank BCA: 0542219716\n   a.n Tatang Taria Edi\n\n💳 E-Wallet DANA: 089663596711\n   a.n Tatang Taria Edi\n\n📝 Setelah transfer, klik tombol di bawah untuk upload bukti transfer\n\n⚠️ Pastikan jumlah transfer sesuai dengan yang tertera!",
            reply_markup=reply_markup
        )
        return WAITING_DEPOSIT_PROOF
        
    except ValueError:
        await update.message.reply_text("❌ Format tidak valid. Silakan masukkan angka saja (contoh: 50000)")
        return WAITING_DEPOSIT_AMOUNT

async def upload_proof(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()
    
    await safe_edit_message(query, 
        "📷 Upload Bukti Transfer\n\nSilakan kirimkan foto bukti transfer Anda.\nPastikan foto jelas dan dapat dibaca."
    )
    return WAITING_DEPOSIT_PROOF

async def get_deposit_proof(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    if update.message.photo:
        photo = update.message.photo[-1]
        amount = context.user_data.get('deposit_amount')
        user_id = update.effective_user.id
        user_name = update.effective_user.first_name or update.effective_user.username or f"User {user_id}"
        
        # Create deposit record
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute('''
        INSERT INTO deposits (user_id, amount, method, proof, date)
        VALUES (?, ?, ?, ?, ?)
        ''', (user_id, amount, 'manual', photo.file_id, datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
        
        deposit_id = cursor.lastrowid
        
        # Get all admin user IDs
        admins = conn.execute('SELECT user_id FROM users WHERE is_admin = 1').fetchall()
        conn.commit()
        conn.close()

        # Send notification to all admins
        for admin in admins:
            try:
                admin_message = (
                    f"🔔 DEPOSIT BARU!\n\n"
                    f"👤 User: {user_name}\n"
                    f"💰 Jumlah: Rp {amount:,}\n"
                    f"📋 ID Deposit: {deposit_id}\n"
                    f"📅 Waktu: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n"
                    f"⚠️ Silakan konfirmasi deposit melalui menu admin."
                )
                
                # Send text notification to admin
                context.bot.send_message(
                    chat_id=admin['user_id'],
                    text=admin_message
                )
                
                # Forward photo to admin
                context.bot.send_photo(
                    chat_id=admin['user_id'],
                    photo=photo.file_id,
                    caption=f"📷 Bukti transfer dari {user_name}\nID Deposit: {deposit_id}"
                )
                    
            except Exception as e:
                logger.error(f"Failed to send notification to admin {admin['user_id']}: {e}")
        
        keyboard = [[InlineKeyboardButton("🔙 Kembali ke Menu", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        
        update.message.reply_text(
            f"✅ Bukti transfer berhasil dikirim!\n\n💰 Jumlah: Rp {amount:,}\n📋 ID Deposit: {deposit_id}\n\n⏳ Deposit sedang diproses oleh admin.\nAnda akan mendapat notifikasi setelah deposit dikonfirmasi.",
            reply_markup=reply_markup
        )
        
        context.user_data.clear()
        return MENU_UTAMA
    else:
        update.message.reply_text("📷 Silakan kirimkan foto bukti transfer.")
        return WAITING_DEPOSIT_PROOF

# --- Admin Handlers ---
def get_digiflazz_products():
    """Fetch products from Digiflazz API"""
    try:
        import hashlib
        import time
        
        username = DIGIFLAZZ_USERNAME
        api_key = DIGIFLAZZ_KEY
        
        # Create signature
        sign_string = f"{username}{api_key}pricelist"
        sign = hashlib.md5(sign_string.encode()).hexdigest()
        
        payload = {
            "cmd": "prepaid",
            "username": username,
            "sign": sign
        }
        
        response = requests.post("https://api.digiflazz.com/v1/price-list", json=payload, timeout=30)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('data'):
                return data['data']
        
        return None
        
    except Exception as e:
        logger.error(f"Error fetching Digiflazz products: {e}")
        return None

async def update_products_from_api(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer("Mengupdate produk dari API Digiflazz...")

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    # Get products from Digiflazz API
    products_data = get_digiflazz_products()
    
    if not products_data:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await safe_edit_message(query, "❌ Gagal mengambil data dari API Digiflazz. Silakan coba lagi nanti.", reply_markup)
        return ADMIN_MENU

    conn = get_db_connection()
    
    # Clear existing products
    conn.execute('DELETE FROM products')
    
    # Insert new products from API
    products_added = 0
    for item in products_data:
        try:
            # Only add active products with reasonable prices
            if (item.get('product_name') and 
                item.get('price') and 
                item.get('buyer_sku_code') and
                item.get('brand') and
                float(item.get('price', 0)) > 0 and
                float(item.get('price', 0)) < 1000000):  # Max 1 million
                
                conn.execute('''
                INSERT INTO products (name, price, digiflazz_code, description, brand, type, seller)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ''', (
                    item['product_name'],
                    int(float(item['price'])),
                    item['buyer_sku_code'],
                    item.get('desc', item['product_name']),
                    item['brand'],
                    item.get('category', 'prepaid'),
                    'digiflazz'
                ))
                products_added += 1
        except Exception as e:
            logger.warning(f"Error adding product {item.get('product_name', 'unknown')}: {e}")
            continue
    
    conn.commit()
    conn.close()
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await safe_edit_message(query, f"✅ Berhasil mengupdate {products_added} produk dari API Digiflazz!", reply_markup)
    return ADMIN_MENU

async def bot_stats(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    conn = get_db_connection()
    total_users = conn.execute('SELECT COUNT(*) as count FROM users').fetchone()['count']
    total_transactions = conn.execute('SELECT COUNT(*) as count FROM transactions').fetchone()['count']
    total_deposits = conn.execute('SELECT COUNT(*) as count FROM deposits WHERE status = "confirmed"').fetchone()['count']
    pending_deposits = conn.execute('SELECT COUNT(*) as count FROM deposits WHERE status = "pending"').fetchone()['count']
    total_products = conn.execute('SELECT COUNT(*) as count FROM products').fetchone()['count']
    conn.close()
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    stats_text = (
        f"📊 Statistik Bot\n\n"
        f"👥 Total Users: {total_users}\n"
        f"🛒 Total Transaksi: {total_transactions}\n"
        f"💰 Total Deposit: {total_deposits}\n"
        f"⏳ Deposit Pending: {pending_deposits}\n"
        f"📦 Total Produk: {total_products}"
    )
    
    await safe_edit_message(query, stats_text, reply_markup)
    return ADMIN_MENU

async def confirm_deposit_list(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    conn = get_db_connection()
    deposits = conn.execute('''
    SELECT d.*, u.first_name, u.username 
    FROM deposits d 
    JOIN users u ON d.user_id = u.user_id 
    WHERE d.status = 'pending' 
    ORDER BY d.date DESC
    ''').fetchall()
    conn.close()
    
    if not deposits:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        await safe_edit_message(query, "💰 Tidak ada deposit yang perlu dikonfirmasi.", reply_markup)
        return ADMIN_MENU

    keyboard = []
    for deposit in deposits:
        user_name = deposit['first_name'] or deposit['username'] or f"User {deposit['user_id']}"
        keyboard.append([InlineKeyboardButton(
            f"{user_name} - Rp {deposit['amount']:,}", 
            callback_data=f"confirm_deposit_{deposit['deposit_id']}"
        )])
    
    keyboard.append([InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')])
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await safe_edit_message(query, "💰 Deposit Pending Konfirmasi\n\nPilih deposit untuk dikonfirmasi:", reply_markup)
    return ADMIN_CONFIRM_DEPOSIT

async def confirm_deposit_action(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    deposit_id = int(query.data.split('_')[-1])
    
    conn = get_db_connection()
    deposit = conn.execute('''
    SELECT d.*, u.first_name, u.username 
    FROM deposits d 
    JOIN users u ON d.user_id = u.user_id 
    WHERE d.deposit_id = ? AND d.status = "pending"
    ''', (deposit_id,)).fetchone()
    
    if deposit:
        # Update deposit status
        conn.execute('UPDATE deposits SET status = "confirmed" WHERE deposit_id = ?', (deposit_id,))
        
        # Update user balance
        current_balance = get_user_balance(deposit['user_id'])
        new_balance = current_balance + deposit['amount']
        conn.execute('UPDATE users SET balance = ? WHERE user_id = ?', (new_balance, deposit['user_id']))
        
        conn.commit()
        
        # Send confirmation notification to user
        try:
            user_name = deposit['first_name'] or deposit['username'] or f"User {deposit['user_id']}"
            user_message = (
                f"✅ DEPOSIT DIKONFIRMASI!\n\n"
                f"💰 Jumlah: Rp {deposit['amount']:,}\n"
                f"📋 ID Deposit: {deposit_id}\n"
                f"💼 Saldo baru: Rp {new_balance:,}\n\n"
                f"✨ Deposit Anda telah berhasil dikonfirmasi dan saldo telah ditambahkan!"
            )
            
            context.application.bot.send_message(
                chat_id=deposit['user_id'],
                text=user_message
            )
            
        except Exception as e:
            logger.error(f"Failed to send confirmation to user {deposit['user_id']}: {e}")
            
    conn.close()
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='confirm_deposit_list')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    if deposit:
        user_name = deposit['first_name'] or deposit['username'] or f"User {deposit['user_id']}"
        await safe_edit_message(query, f"✅ Deposit berhasil dikonfirmasi!\n\n👤 User: {user_name}\n💰 Jumlah: Rp {deposit['amount']:,}\n\nSaldo user telah ditambahkan dan notifikasi terkirim.", reply_markup)
    else:
        await safe_edit_message(query, f"❌ Deposit tidak ditemukan atau sudah diproses.", reply_markup)
    
    return ADMIN_CONFIRM_DEPOSIT

async def manage_admin_start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    keyboard = [[InlineKeyboardButton("📋 Daftar Admin", callback_data='list_admin')],
                [InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await safe_edit_message(query, "👥 Kelola Admin\n\nSilakan pilih aksi:", reply_markup)
    return ADMIN_MANAGE_FLOW

# --- Margin Setting Handler ---
async def margin_setting(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    current_margin = get_margin_percentage()
    
    keyboard = [
        [InlineKeyboardButton("5%", callback_data='set_margin_5'),
         InlineKeyboardButton("10%", callback_data='set_margin_10'),
         InlineKeyboardButton("15%", callback_data='set_margin_15')],
        [InlineKeyboardButton("20%", callback_data='set_margin_20'),
         InlineKeyboardButton("25%", callback_data='set_margin_25'),
         InlineKeyboardButton("30%", callback_data='set_margin_30')],
        [InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await safe_edit_message(query, f"⚙️ Setting Margin\n\nMargin saat ini: {current_margin}%\n\nPilih persentase margin yang ingin digunakan:", reply_markup)
    return ADMIN_MARGIN_SETTING

async def set_margin(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    if not is_admin(update.effective_user.id):
        await safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    margin_value = int(query.data.split('_')[-1])
    set_margin_percentage(margin_value)
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await safe_edit_message(query, f"✅ Margin berhasil diubah menjadi {margin_value}%\n\nSemua harga produk akan otomatis menggunakan margin ini.", reply_markup)
    return ADMIN_MENU

async def list_admin(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    await query.answer()

    conn = get_db_connection()
    admins = conn.execute('SELECT * FROM users WHERE is_admin = 1').fetchall()
    conn.close()

    admin_list = "👑 Daftar Admin:\n\n"
    for admin in admins:
        admin_list += f"• {admin['first_name']} (@{admin['username'] or 'no_username'}) - ID: {admin['user_id']}\n"

    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='manage_admin_start')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await safe_edit_message(query, admin_list, reply_markup)
    return ADMIN_MANAGE_FLOW

async def cancel(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    await update.message.reply_text("❌ Operasi dibatalkan. Kembali ke menu utama.")
    context.user_data.clear()
    return ConversationHandler.END

async def error_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle errors"""
    logger.error(f"Update {update} caused error {context.error}")

def main():
    """Start the bot"""
    # Initialize database
    init_db()
    
    # Create the Updater with conflict resolution
    try:
        application = Application.builder().token(TOKEN).build()
        
        logger.info("Bot application created successfully")
        
    except Exception as e:
        logger.error(f"Failed to create updater: {e}")
        return

    # Add conversation handler
    conv_handler = ConversationHandler(
        entry_points=[CommandHandler('start', start)],
        states={
            MENU_UTAMA: [
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
                CallbackQueryHandler(check_balance, pattern='^check_balance$'),
                CallbackQueryHandler(buy_product_menu, pattern='^buy_product$'),
                CallbackQueryHandler(deposit_menu, pattern='^deposit$'),
                CallbackQueryHandler(admin_menu, pattern='^admin_menu$'),
            ],
            SHOW_CATEGORY: [
                CallbackQueryHandler(show_brands_by_category, pattern='^show_category_'),
                CallbackQueryHandler(buy_product_menu, pattern='^buy_product$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
            SHOW_BRAND: [
                CallbackQueryHandler(show_products_by_brand, pattern='^show_brand_'),
                CallbackQueryHandler(show_brands_by_category, pattern='^show_category_'),
                CallbackQueryHandler(buy_product_menu, pattern='^buy_product$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
            SHOW_PRODUCT: [
                CallbackQueryHandler(select_product, pattern='^select_product_'),
                CallbackQueryHandler(show_products_by_brand, pattern='^show_brand_'),
                CallbackQueryHandler(show_brands_by_category, pattern='^show_category_'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
            WAITING_TARGET_ID: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, get_target_id),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
                CallbackQueryHandler(buy_product_menu, pattern='^buy_product$'),
            ],
            CONFIRMING_PURCHASE: [
                CallbackQueryHandler(confirm_purchase, pattern='^confirm_purchase$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
                CallbackQueryHandler(buy_product_menu, pattern='^buy_product$'),
            ],
            WAITING_DEPOSIT_AMOUNT: [
                MessageHandler(filters.TEXT & ~filters.COMMAND, get_deposit_amount),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
                CallbackQueryHandler(deposit_menu, pattern='^deposit$'),
            ],
            WAITING_DEPOSIT_PROOF: [
                CallbackQueryHandler(upload_proof, pattern='^upload_proof$'),
                MessageHandler(filters.PHOTO, get_deposit_proof),
                MessageHandler(filters.TEXT & ~filters.COMMAND, get_deposit_proof),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
                CallbackQueryHandler(deposit_menu, pattern='^deposit$'),
            ],
            ADMIN_MENU: [
                CallbackQueryHandler(update_products_from_api, pattern='^update_products_from_api$'),
                CallbackQueryHandler(margin_setting, pattern='^margin_setting$'),
                CallbackQueryHandler(bot_stats, pattern='^bot_stats$'),
                CallbackQueryHandler(confirm_deposit_list, pattern='^confirm_deposit_list$'),
                CallbackQueryHandler(manage_admin_start, pattern='^manage_admin_start$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
            ADMIN_MARGIN_SETTING: [
                CallbackQueryHandler(set_margin, pattern='^set_margin_'),
                CallbackQueryHandler(admin_menu, pattern='^admin_menu$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
            ADMIN_CONFIRM_DEPOSIT: [
                CallbackQueryHandler(confirm_deposit_action, pattern='^confirm_deposit_'),
                CallbackQueryHandler(confirm_deposit_list, pattern='^confirm_deposit_list$'),
                CallbackQueryHandler(admin_menu, pattern='^admin_menu$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
            ADMIN_MANAGE_FLOW: [
                CallbackQueryHandler(list_admin, pattern='^list_admin$'),
                CallbackQueryHandler(manage_admin_start, pattern='^manage_admin_start$'),
                CallbackQueryHandler(admin_menu, pattern='^admin_menu$'),
                CallbackQueryHandler(main_menu, pattern='^main_menu$'),
            ],
        },
        fallbacks=[
            CommandHandler('cancel', cancel),
            CommandHandler('start', start),
            CallbackQueryHandler(main_menu, pattern='^main_menu$'),
        ],
        allow_reentry=True,
        per_message=False
    )

    # Add handlers
    application.add_handler(conv_handler)
    application.add_error_handler(error_handler)

    # Start the bot
    logger.info("Starting bot...")
    try:
        application.run_polling(
            poll_interval=2.0,
            timeout=10,
            close_loop=False,
            stop_signals=None
        )
        logger.info("Bot started successfully")
    except Exception as e:
        logger.error(f"Error starting bot: {e}")
        logger.info("If you see 'Conflict' error, make sure no other bot instance is running")

if __name__ == '__main__':
    main()

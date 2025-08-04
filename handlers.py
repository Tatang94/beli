import logging
from datetime import datetime
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import CallbackContext, ConversationHandler
from telegram.error import BadRequest, TimedOut, NetworkError

from config import *
from database import *
from digiflazz_api import update_products_in_database, process_transaction

logger = logging.getLogger(__name__)

def safe_edit_message(query, text, reply_markup=None, parse_mode=None):
    """Safely edit message with fallback to new message"""
    try:
        query.edit_message_text(text=text, reply_markup=reply_markup, parse_mode=parse_mode)
        return True
    except BadRequest as e:
        if "message is not modified" in str(e).lower():
            return True
        logger.warning(f"Failed to edit message: {e}")
        try:
            query.message.reply_text(text=text, reply_markup=reply_markup, parse_mode=parse_mode)
            return True
        except Exception as e2:
            logger.error(f"Failed to send new message: {e2}")
            return False
    except Exception as e:
        logger.error(f"Unexpected error editing message: {e}")
        return False

def start(update: Update, context: CallbackContext) -> int:
    """Start command handler"""
    user = update.effective_user
    register_user(user)

    welcome_message = (
        f"👋 Halo {user.first_name}!\n\n"
        "Selamat datang di Bot Pulsa & PPOB Digital!\n"
        "Silakan pilih menu di bawah:"
    )

    keyboard = [
        [InlineKeyboardButton("🛍 Beli Produk", callback_data='buy_product')],
        [InlineKeyboardButton("💰 Deposit Saldo", callback_data='deposit')],
        [InlineKeyboardButton("💼 Cek Saldo", callback_data='check_balance')]
    ]

    if is_admin(user.id):
        keyboard.append([InlineKeyboardButton("👑 Admin Menu", callback_data='admin_menu')])

    reply_markup = InlineKeyboardMarkup(keyboard)
    update.message.reply_text(welcome_message, reply_markup=reply_markup)
    return MENU_UTAMA

def main_menu(update: Update, context: CallbackContext) -> int:
    """Main menu handler"""
    query = update.callback_query
    query.answer()

    user = update.effective_user
    keyboard = [
        [InlineKeyboardButton("🛍 Beli Produk", callback_data='buy_product')],
        [InlineKeyboardButton("💰 Deposit Saldo", callback_data='deposit')],
        [InlineKeyboardButton("💼 Cek Saldo", callback_data='check_balance')]
    ]

    if is_admin(user.id):
        keyboard.append([InlineKeyboardButton("👑 Admin Menu", callback_data='admin_menu')])

    reply_markup = InlineKeyboardMarkup(keyboard)
    safe_edit_message(query, "📱 Menu Utama\n\nSilakan pilih menu di bawah:", reply_markup)
    return MENU_UTAMA

def check_balance(update: Update, context: CallbackContext) -> int:
    """Check balance handler"""
    query = update.callback_query
    query.answer()

    user_id = update.effective_user.id
    balance = get_user_balance(user_id)

    keyboard = [
        [InlineKeyboardButton("💰 Deposit", callback_data='deposit')],
        [InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]
    ]

    reply_markup = InlineKeyboardMarkup(keyboard)
    safe_edit_message(query, f"💼 Saldo Anda\n\n💰 Saldo: Rp {balance:,}\n\nSilakan pilih menu di bawah:", reply_markup)
    return MENU_UTAMA

# === BUY FLOW HANDLERS ===

def buy_product_menu(update: Update, context: CallbackContext) -> int:
    """Buy product menu handler"""
    query = update.callback_query
    query.answer("Mengambil daftar kategori produk...")

    conn = get_db_connection()
    categories = conn.execute('SELECT DISTINCT brand FROM products WHERE status = "active" ORDER BY brand').fetchall()
    conn.close()

    if not categories:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        safe_edit_message(query, "❌ Maaf, saat ini tidak ada produk yang tersedia.", reply_markup)
        return MENU_UTAMA

    keyboard = [[InlineKeyboardButton(cat['brand'], callback_data=f"category_{cat['brand']}")] for cat in categories]
    keyboard.append([InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')])
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    safe_edit_message(query, "🛍 Pilih Kategori Produk\n\nSilakan pilih kategori produk yang ingin Anda beli:", reply_markup)
    return BUY_FLOW

def show_products(update: Update, context: CallbackContext) -> int:
    """Show products in category handler"""
    query = update.callback_query
    query.answer("Mengambil daftar produk...")
    
    category = query.data.split('_', 1)[1]
    context.user_data['selected_category'] = category

    conn = get_db_connection()
    products = conn.execute('SELECT * FROM products WHERE brand = ? AND status = "active" ORDER BY price', (category,)).fetchall()
    conn.close()

    if not products:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='buy_product')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        safe_edit_message(query, f"❌ Tidak ada produk tersedia dalam kategori {category}.", reply_markup)
        return BUY_FLOW

    keyboard = []
    for p in products:
        keyboard.append([InlineKeyboardButton(f"{p['name']} - Rp {p['price']:,}", callback_data=f"select_product_{p['product_id']}")])
    
    keyboard.append([InlineKeyboardButton("🔙 Kembali", callback_data='buy_product')])
    reply_markup = InlineKeyboardMarkup(keyboard)

    safe_edit_message(query, f"📋 Produk {category}\n\nSilakan pilih produk yang ingin Anda beli:", reply_markup)
    return BUY_FLOW

def select_product(update: Update, context: CallbackContext) -> int:
    """Select product handler"""
    query = update.callback_query
    query.answer()
    
    product_id = int(query.data.split('_')[-1])

    conn = get_db_connection()
    product = conn.execute('SELECT * FROM products WHERE product_id = ?', (product_id,)).fetchone()
    conn.close()

    if not product:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='buy_product')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        safe_edit_message(query, "❌ Produk tidak ditemukan.", reply_markup)
        return BUY_FLOW

    context.user_data['selected_product'] = product_id
    
    safe_edit_message(query, 
        f"📋 Detail Produk\n\n"
        f"🏷 Nama: {product['name']}\n"
        f"💰 Harga: Rp {product['price']:,}\n"
        f"📝 Deskripsi: {product['description']}\n\n"
        f"Silakan kirimkan ID tujuan (misal: nomor HP, nomor token PLN, dll):"
    )
    return WAITING_TARGET_ID

def get_target_id(update: Update, context: CallbackContext) -> int:
    """Get target ID from user input"""
    target_id = update.message.text.strip()
    context.user_data['target_id'] = target_id

    product_id = context.user_data.get('selected_product')
    if not product_id:
        update.message.reply_text("❌ Terjadi kesalahan. Silakan mulai pembelian dari awal.")
        return start(update, context)

    conn = get_db_connection()
    product = conn.execute('SELECT * FROM products WHERE product_id = ?', (product_id,)).fetchone()
    conn.close()

    if not product:
        update.message.reply_text("❌ Produk tidak ditemukan.")
        return start(update, context)

    user_id = update.effective_user.id
    balance = get_user_balance(user_id)

    if balance < product['price']:
        keyboard = [
            [InlineKeyboardButton("💰 Deposit Sekarang", callback_data='deposit')],
            [InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        update.message.reply_text(
            f"❌ Saldo tidak mencukupi!\n\n"
            f"💰 Saldo Anda: Rp {balance:,}\n"
            f"💳 Harga Produk: Rp {product['price']:,}\n"
            f"⚠️ Kurang: Rp {product['price'] - balance:,}",
            reply_markup=reply_markup
        )
        return MENU_UTAMA

    keyboard = [
        [InlineKeyboardButton("✅ Konfirmasi Pembelian", callback_data='confirm_purchase')],
        [InlineKeyboardButton("❌ Batal", callback_data='main_menu')]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    update.message.reply_text(
        f"📋 Konfirmasi Pembelian\n\n"
        f"🏷 Produk: {product['name']}\n"
        f"🎯 Target: {target_id}\n"
        f"💰 Harga: Rp {product['price']:,}\n"
        f"💼 Saldo Anda: Rp {balance:,}\n"
        f"💳 Sisa Saldo: Rp {balance - product['price']:,}\n\n"
        f"Apakah Anda yakin ingin melakukan pembelian?",
        reply_markup=reply_markup
    )
    return CONFIRMING_PURCHASE

def confirm_purchase(update: Update, context: CallbackContext) -> int:
    """Confirm and process purchase"""
    query = update.callback_query
    query.answer("Memproses pembelian...")

    user_id = update.effective_user.id
    product_id = context.user_data.get('selected_product')
    target_id = context.user_data.get('target_id')

    if not product_id or not target_id:
        safe_edit_message(query, "❌ Data pembelian tidak lengkap. Silakan mulai dari awal.")
        return start(update, context)

    conn = get_db_connection()
    product = conn.execute('SELECT * FROM products WHERE product_id = ?', (product_id,)).fetchone()
    conn.close()

    if not product:
        safe_edit_message(query, "❌ Produk tidak ditemukan.")
        return start(update, context)

    # Check balance again
    balance = get_user_balance(user_id)
    if balance < product['price']:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        safe_edit_message(query, "❌ Saldo tidak mencukupi untuk pembelian ini.", reply_markup)
        return MENU_UTAMA

    # Deduct balance
    success, new_balance = update_user_balance(user_id, product['price'], 'subtract')
    if not success:
        safe_edit_message(query, f"❌ Gagal memproses pembayaran: {new_balance}")
        return MENU_UTAMA

    # Create transaction record
    ref_id = f"TRX{int(datetime.now().timestamp())}{user_id}"
    transaction_id = create_transaction(user_id, product_id, target_id, product['price'], ref_id)

    if not transaction_id:
        # Refund balance if transaction creation failed
        update_user_balance(user_id, product['price'], 'add')
        safe_edit_message(query, "❌ Gagal membuat transaksi. Saldo telah dikembalikan.")
        return MENU_UTAMA

    # Process transaction via Digiflazz API
    api_success, api_result = process_transaction(product['digiflazz_code'], target_id, ref_id)

    if api_success:
        if api_result.get('data', {}).get('status') == 'Sukses':
            update_transaction_status(transaction_id, 'success', 'Transaction successful')
            safe_edit_message(query, 
                f"✅ Pembelian Berhasil!\n\n"
                f"🏷 Produk: {product['name']}\n"
                f"🎯 Target: {target_id}\n"
                f"💰 Harga: Rp {product['price']:,}\n"
                f"📋 Ref ID: {ref_id}\n"
                f"💼 Sisa Saldo: Rp {new_balance:,}\n\n"
                f"✨ Terima kasih telah menggunakan layanan kami!"
            )
        else:
            # Transaction failed, refund balance
            update_user_balance(user_id, product['price'], 'add')
            update_transaction_status(transaction_id, 'failed', api_result.get('data', {}).get('message', 'Unknown error'))
            safe_edit_message(query, 
                f"❌ Transaksi Gagal\n\n"
                f"💳 Saldo telah dikembalikan: Rp {product['price']:,}\n"
                f"📝 Pesan: {api_result.get('data', {}).get('message', 'Unknown error')}"
            )
    else:
        # API call failed, refund balance
        update_user_balance(user_id, product['price'], 'add')
        update_transaction_status(transaction_id, 'failed', api_result)
        safe_edit_message(query, 
            f"❌ Transaksi Gagal\n\n"
            f"💳 Saldo telah dikembalikan: Rp {product['price']:,}\n"
            f"📝 Pesan: {api_result}"
        )

    # Clear user data
    context.user_data.clear()
    return MENU_UTAMA

# === DEPOSIT FLOW HANDLERS ===

def deposit_menu(update: Update, context: CallbackContext) -> int:
    """Deposit menu handler"""
    query = update.callback_query
    query.answer()

    safe_edit_message(query, 
        "💰 Deposit Saldo\n\n"
        "Silakan masukkan jumlah deposit yang ingin Anda lakukan:\n"
        "(Minimal Rp 10.000)"
    )
    return WAITING_DEPOSIT_AMOUNT

def get_deposit_amount(update: Update, context: CallbackContext) -> int:
    """Get deposit amount from user"""
    try:
        amount = int(update.message.text.replace('.', '').replace(',', '').replace('Rp', '').strip())
        
        if amount < 10000:
            update.message.reply_text("❌ Minimal deposit adalah Rp 10.000. Silakan masukkan jumlah yang valid.")
            return WAITING_DEPOSIT_AMOUNT
        
        context.user_data['deposit_amount'] = amount
        
        keyboard = [
            [InlineKeyboardButton("📷 Upload Bukti Transfer", callback_data='upload_proof')],
            [InlineKeyboardButton("❌ Batal", callback_data='main_menu')]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        
        update.message.reply_text(
            f"💰 Deposit: Rp {amount:,}\n\n"
            f"📋 Instruksi Pembayaran:\n"
            f"1. Transfer ke rekening berikut:\n"
            f"   Bank BCA: 1234567890\n"
            f"   a.n Bot Store\n\n"
            f"2. Setelah transfer, klik tombol di bawah untuk upload bukti transfer\n\n"
            f"⚠️ Pastikan jumlah transfer sesuai dengan yang tertera!",
            reply_markup=reply_markup
        )
        return WAITING_DEPOSIT_PROOF
        
    except ValueError:
        update.message.reply_text("❌ Format tidak valid. Silakan masukkan angka saja (contoh: 50000)")
        return WAITING_DEPOSIT_AMOUNT

def upload_proof(update: Update, context: CallbackContext) -> int:
    """Handler for upload proof button"""
    query = update.callback_query
    query.answer()
    
    safe_edit_message(query, 
        "📷 Upload Bukti Transfer\n\n"
        "Silakan kirimkan foto bukti transfer Anda.\n"
        "Pastikan foto jelas dan dapat dibaca."
    )
    return WAITING_DEPOSIT_PROOF

def get_deposit_proof(update: Update, context: CallbackContext) -> int:
    """Get deposit proof from user"""
    if update.message.photo:
        photo = update.message.photo[-1]  # Get highest resolution
        amount = context.user_data.get('deposit_amount')
        
        if not amount:
            update.message.reply_text("❌ Data deposit tidak ditemukan. Silakan mulai dari awal.")
            return start(update, context)
        
        # Create deposit record
        deposit_id = create_deposit(update.effective_user.id, amount)
        
        if deposit_id:
            # Save photo file_id as proof
            conn = get_db_connection()
            conn.execute('UPDATE deposits SET proof = ? WHERE deposit_id = ?', (photo.file_id, deposit_id))
            conn.commit()
            conn.close()
            
            keyboard = [[InlineKeyboardButton("🔙 Kembali ke Menu", callback_data='main_menu')]]
            reply_markup = InlineKeyboardMarkup(keyboard)
            
            update.message.reply_text(
                f"✅ Bukti transfer berhasil dikirim!\n\n"
                f"💰 Jumlah: Rp {amount:,}\n"
                f"📋 ID Deposit: {deposit_id}\n\n"
                f"⏳ Deposit sedang diproses oleh admin.\n"
                f"Anda akan mendapat notifikasi setelah deposit dikonfirmasi.",
                reply_markup=reply_markup
            )
            
            # Clear user data
            context.user_data.clear()
            return MENU_UTAMA
        else:
            update.message.reply_text("❌ Gagal menyimpan deposit. Silakan coba lagi.")
            return WAITING_DEPOSIT_PROOF
    else:
        update.message.reply_text("📷 Silakan kirimkan foto bukti transfer.")
        return WAITING_DEPOSIT_PROOF

# === ADMIN HANDLERS ===

def admin_menu(update: Update, context: CallbackContext) -> int:
    """Admin menu handler"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    keyboard = [
        [InlineKeyboardButton("➕ Update Produk", callback_data='update_products_from_api')],
        [InlineKeyboardButton("👥 Kelola Admin", callback_data='manage_admin_start')],
        [InlineKeyboardButton("📊 Statistik Bot", callback_data='bot_stats')],
        [InlineKeyboardButton("💵 Konfirmasi Deposit", callback_data='confirm_deposit_list')],
        [InlineKeyboardButton("🔙 Kembali", callback_data='main_menu')]
    ]

    reply_markup = InlineKeyboardMarkup(keyboard)
    safe_edit_message(query, "👑 Menu Admin\n\nSilakan pilih menu admin:", reply_markup)
    return ADMIN_MENU

def update_products_from_api(update: Update, context: CallbackContext) -> int:
    """Update products from Digiflazz API"""
    query = update.callback_query
    query.answer("Mengupdate produk dari API...")

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    success, message = update_products_in_database()
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    if success:
        safe_edit_message(query, f"✅ {message}", reply_markup)
    else:
        safe_edit_message(query, f"❌ Gagal mengupdate produk: {message}", reply_markup)
    
    return ADMIN_MENU

def bot_stats(update: Update, context: CallbackContext) -> int:
    """Show bot statistics"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    stats = get_bot_stats()
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    stats_text = (
        f"📊 Statistik Bot\n\n"
        f"👥 Total Users: {stats.get('total_users', 0)}\n"
        f"🛒 Total Transaksi: {stats.get('total_transactions', 0)}\n"
        f"💰 Total Deposit: {stats.get('total_deposits', 0)}\n"
        f"⏳ Deposit Pending: {stats.get('pending_deposits', 0)}\n"
        f"📦 Total Produk: {stats.get('total_products', 0)}"
    )
    
    safe_edit_message(query, stats_text, reply_markup)
    return ADMIN_MENU

def confirm_deposit_list(update: Update, context: CallbackContext) -> int:
    """Show pending deposits for confirmation"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    deposits = get_pending_deposits()
    
    if not deposits:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        safe_edit_message(query, "💰 Tidak ada deposit yang perlu dikonfirmasi.", reply_markup)
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
    
    safe_edit_message(query, "💰 Deposit Pending Konfirmasi\n\nPilih deposit untuk dikonfirmasi:", reply_markup)
    return ADMIN_CONFIRM_DEPOSIT

def confirm_deposit_action(update: Update, context: CallbackContext) -> int:
    """Confirm specific deposit"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    deposit_id = int(query.data.split('_')[-1])
    admin_id = update.effective_user.id
    
    success, result = confirm_deposit(deposit_id, admin_id)
    
    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='confirm_deposit_list')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    if success:
        safe_edit_message(query, f"✅ Deposit berhasil dikonfirmasi!\n\nSaldo user telah ditambahkan: Rp {result:,}", reply_markup)
    else:
        safe_edit_message(query, f"❌ Gagal mengkonfirmasi deposit: {result}", reply_markup)
    
    return ADMIN_CONFIRM_DEPOSIT

def manage_admin_start(update: Update, context: CallbackContext) -> int:
    """Start admin management"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    keyboard = [
        [InlineKeyboardButton("➕ Tambah Admin", callback_data='add_admin')],
        [InlineKeyboardButton("➖ Hapus Admin", callback_data='remove_admin')],
        [InlineKeyboardButton("📋 Daftar Admin", callback_data='list_admin')],
        [InlineKeyboardButton("🔙 Kembali", callback_data='admin_menu')]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    safe_edit_message(query, "👥 Kelola Admin\n\nSilakan pilih aksi:", reply_markup)
    return ADMIN_MANAGE_FLOW

def list_admin(update: Update, context: CallbackContext) -> int:
    """List all admins"""
    query = update.callback_query
    query.answer()

    conn = get_db_connection()
    admins = conn.execute('SELECT * FROM users WHERE is_admin = 1').fetchall()
    conn.close()

    admin_list = "👑 Daftar Admin:\n\n"
    for admin in admins:
        admin_list += f"• {admin['first_name']} (@{admin['username'] or 'no_username'}) - ID: {admin['user_id']}\n"

    keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='manage_admin_start')]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    safe_edit_message(query, admin_list, reply_markup)
    return ADMIN_MANAGE_FLOW

def cancel(update: Update, context: CallbackContext) -> int:
    """Cancel current operation"""
    update.message.reply_text("❌ Operasi dibatalkan. Kembali ke menu utama.")
    context.user_data.clear()
    return ConversationHandler.END

def add_admin(update: Update, context: CallbackContext) -> int:
    """Add new admin handler"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    safe_edit_message(query, 
        "👑 Tambah Admin Baru\n\n"
        "Silakan kirimkan User ID yang ingin dijadikan admin.\n"
        "Anda bisa mendapatkan User ID dengan cara:\n"
        "1. Minta user mengirim /start ke bot ini\n"
        "2. Atau gunakan @userinfobot di Telegram"
    )
    return ADMIN_MANAGE_FLOW

def remove_admin(update: Update, context: CallbackContext) -> int:
    """Remove admin handler"""
    query = update.callback_query
    query.answer()

    if not is_admin(update.effective_user.id):
        safe_edit_message(query, "❌ Anda tidak memiliki akses admin.")
        return MENU_UTAMA

    conn = get_db_connection()
    admins = conn.execute('SELECT * FROM users WHERE is_admin = 1 AND user_id != ?', 
                         (update.effective_user.id,)).fetchall()
    conn.close()

    if not admins:
        keyboard = [[InlineKeyboardButton("🔙 Kembali", callback_data='manage_admin_start')]]
        reply_markup = InlineKeyboardMarkup(keyboard)
        safe_edit_message(query, "❌ Tidak ada admin lain yang bisa dihapus.", reply_markup)
        return ADMIN_MANAGE_FLOW

    keyboard = []
    for admin in admins:
        keyboard.append([InlineKeyboardButton(
            f"{admin['first_name']} - {admin['user_id']}", 
            callback_data=f"remove_admin_{admin['user_id']}"
        )])
    
    keyboard.append([InlineKeyboardButton("🔙 Kembali", callback_data='manage_admin_start')])
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    safe_edit_message(query, "👑 Hapus Admin\n\nPilih admin yang ingin dihapus:", reply_markup)
    return ADMIN_MANAGE_FLOW

def error_handler(update: Update, context: CallbackContext):
    """Handle errors"""
    logger.error(f"Update {update} caused error {context.error}")
    
    if update and update.effective_chat:
        try:
            update.effective_chat.send_message(
                "❌ Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin."
            )
        except Exception as e:
            logger.error(f"Failed to send error message: {e}")

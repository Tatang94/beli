#!/usr/bin/env python3

import os
import logging
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import Application, CommandHandler, CallbackQueryHandler, ContextTypes

# Bot configuration
TOKEN = os.getenv("TELEGRAM_BOT_TOKEN") or "8216106872:AAEQ_DxjYtZL0t6vD-y4Pfj90c94wHgXDcc"

# Setup logging
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Send welcome message with inline keyboard"""
    keyboard = [
        [InlineKeyboardButton("🛍 Beli Produk", callback_data='buy_product')],
        [InlineKeyboardButton("💰 Deposit Saldo", callback_data='deposit')],
        [InlineKeyboardButton("💼 Cek Saldo", callback_data='check_balance')]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await update.message.reply_text(
        '👋 Selamat datang di Bot Pulsa & PPOB Digital!\n\nSilakan pilih menu:',
        reply_markup=reply_markup
    )

async def button_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle button presses"""
    query = update.callback_query
    await query.answer()
    
    if query.data == 'buy_product':
        await query.edit_message_text("🛍 Fitur pembelian produk dalam pengembangan!")
    elif query.data == 'deposit':
        await query.edit_message_text("💰 Fitur deposit dalam pengembangan!")
    elif query.data == 'check_balance':
        await query.edit_message_text("💼 Saldo Anda: Rp 0")

def main():
    """Start the bot"""
    try:
        application = Application.builder().token(TOKEN).build()
        
        # Add handlers
        application.add_handler(CommandHandler("start", start))
        application.add_handler(CallbackQueryHandler(button_handler))
        
        logger.info("Bot started successfully!")
        
        # Run the bot
        application.run_polling(allowed_updates=Update.ALL_TYPES)
        
    except Exception as e:
        logger.error(f"Error starting bot: {e}")

if __name__ == '__main__':
    main()
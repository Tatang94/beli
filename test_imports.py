#!/usr/bin/env python3

print("Testing telegram imports...")
import sys

try:
    sys.path.insert(0, '.pythonlibs/lib/python3.11/site-packages')
    
    print("1. Importing telegram module...")
    import telegram
    print(f"telegram module loaded: {telegram}")
    
    print("2. Testing telegram contents...")
    print(f"Contents: {dir(telegram)}")
    
    print("3. Importing Update...")
    from telegram import Update
    print("Update imported successfully!")
    
    print("4. Importing bot components...")
    from telegram import InlineKeyboardButton, InlineKeyboardMarkup
    print("Keyboard components imported successfully!")
    
    print("5. Importing ext components...")
    from telegram.ext import Application, CommandHandler
    print("Ext components imported successfully!")
    
    print("All imports successful!")
    
except Exception as e:
    print(f"Import error: {e}")
    import traceback
    traceback.print_exc()
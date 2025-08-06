#!/usr/bin/env python3
"""Test script to debug telegram package import issues"""

import sys
import os

print("Python path:")
for path in sys.path:
    print(f"  {path}")

print("\nTrying imports:")

try:
    import telegram
    print(f"✓ telegram module imported from: {telegram.__file__}")
    print(f"  telegram module contents: {dir(telegram)}")
    
    try:
        from telegram import Update
        print("✓ telegram.Update imported successfully")
    except ImportError as e:
        print(f"✗ Failed to import Update: {e}")
    
    try:
        from telegram.ext import Application
        print("✓ telegram.ext.Application imported successfully")
    except ImportError as e:
        print(f"✗ Failed to import Application: {e}")
        
except ImportError as e:
    print(f"✗ Failed to import telegram module: {e}")

print("\nChecking installed packages:")
import subprocess
try:
    result = subprocess.run(['python3', '-m', 'pip', 'list'], capture_output=True, text=True)
    print(result.stdout)
except Exception as e:
    print(f"Error checking packages: {e}")
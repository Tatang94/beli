#!/usr/bin/env python3
"""
Test script for the Telegram bot to ensure it works properly
"""
import subprocess
import sys
import time
import os
import signal

def check_process():
    """Check if bot process is already running"""
    try:
        result = subprocess.run(['pgrep', '-f', 'python.*bot.py'], 
                              capture_output=True, text=True)
        if result.stdout.strip():
            print("🔍 Found existing bot process(es):")
            print(result.stdout.strip())
            return True
    except:
        pass
    return False

def kill_existing_processes():
    """Kill existing bot processes"""
    try:
        subprocess.run(['pkill', '-f', 'python.*bot.py'], 
                      capture_output=True)
        time.sleep(2)
        print("✅ Stopped existing bot processes")
    except:
        pass

def test_imports():
    """Test if all required modules can be imported"""
    print("🔧 Testing imports...")
    try:
        import telegram
        import sqlite3
        import requests
        import hashlib
        print("✅ All required modules available")
        return True
    except ImportError as e:
        print(f"❌ Import error: {e}")
        return False

def main():
    print("🤖 Bot Test Script")
    print("=" * 40)
    
    # Test imports first
    if not test_imports():
        sys.exit(1)
    
    # Check for existing processes
    if check_process():
        print("⚠️  Existing bot process found. Stopping it...")
        kill_existing_processes()
    
    print("🚀 Starting bot...")
    try:
        # Run the bot
        process = subprocess.Popen([sys.executable, 'bot.py'])
        print(f"✅ Bot started with PID: {process.pid}")
        print("📝 Bot logs will appear below:")
        print("-" * 40)
        
        # Wait for a bit to see if it starts successfully
        time.sleep(5)
        
        # Check if process is still running
        if process.poll() is None:
            print("✅ Bot is running successfully!")
            print("💡 Press Ctrl+C to stop the bot")
            
            try:
                process.wait()
            except KeyboardInterrupt:
                print("\n🛑 Stopping bot...")
                process.terminate()
                time.sleep(2)
                if process.poll() is None:
                    process.kill()
                print("✅ Bot stopped")
        else:
            print("❌ Bot process terminated unexpectedly")
            return 1
            
    except Exception as e:
        print(f"❌ Error starting bot: {e}")
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())
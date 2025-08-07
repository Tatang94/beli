# Overview

This is a fully functional Telegram bot for selling digital products (mobile credits and PPOB services). The bot includes complete conversation flows for user registration, balance management, product purchases, deposit system, and admin management. All inline buttons are properly connected with working navigation flows.

## Recent Changes (August 2025)
- Fixed all conversation handler flows and inline button navigation
- Consolidated codebase into single working bot.py file
- Implemented complete purchase flow with target ID input and confirmation
- Added working deposit system with photo upload support
- Fixed admin menu with product updates, statistics, and deposit confirmation
- All inline buttons now function correctly with proper state management
- Implemented structured product navigation: Category → Brand → Product → Price
- Added admin margin setting feature (5%-30%) with database persistence
- Added automatic admin notifications when users upload deposit proof
- Added automatic user notifications when deposits are confirmed
- **MAJOR UPDATE**: Created complete PHP version for cPanel hosting compatibility
- Generated full PHP implementation with webhook support, database schema, and setup scripts
- Added comprehensive documentation and installation guide for hosting deployment
- Both Python and PHP versions now available with identical functionality
- **PHP STRUCTURE IMPROVEMENT**: Separated PHP files into modular structure with organized folders
- Created includes/ folder for core libraries (database, telegram, digiflazz, handlers)
- Added setup/ folder with database import guide and webhook configuration tools
- Added admin/ folder for product management and bot administration
- Comprehensive installation documentation with step-by-step database import guide
- **MIGRATION COMPLETED (August 7, 2025)**: Successfully migrated from Replit Agent to standard Replit environment
- Secured configuration by removing hardcoded API keys from source code  
- Updated pyproject.toml for proper dependency management
- All required packages (python-telegram-bot, requests) properly installed and working
- Created working bot implementation with proper environment variable configuration
- Bot workflows successfully running with enhanced security practices
- Migration validation completed with all systems operational
- **CHAT INTERFACE OPTIMIZATION (August 7, 2025)**: Major improvements to PHP web interface
- Replaced "/start" command with user-friendly "beli pulsa mas" greeting
- Completely redesigned chat flow with structured navigation and professional appearance
- Added comprehensive deposit system with multiple bank options (BCA, Mandiri, BRI)
- Implemented E-Wallet and QRIS payment integration with detailed information display
- Enhanced saldo display with transaction history and professional card-style layout
- Added complete help system with FAQ, support contacts, and comprehensive assistance
- Upgraded admin panel with advanced system management and broadcasting features
- Implemented file upload functionality for payment proof with automatic processing
- All menu interactions now provide detailed information and smooth user experience
- **WEB INTERFACE DEVELOPMENT**: Created complete PHP web interface compatible with InfinityFree hosting
- Built chat-like interface mimicking Telegram bot appearance
- Implemented full product browsing, purchase flow, and admin panel
- All API keys preserved and configured for production deployment
- Interface designed for mobile-first responsive experience
- **ADMIN CENTER SEPARATION (August 7, 2025)**: Created dedicated admin panel at /admincenter
- Separated admin functions from main chat interface for better security
- Added authentication system with admin access key protection
- Built comprehensive admin dashboard with statistics and management tools
- Modern gradient design with secure login and session management
- **CLEAN INTERFACE (August 7, 2025)**: Removed all mock data from main chat interface
- Simplified chat to show only welcome message initially
- Admin can now manage products through existing system (categories, brands, types, prices)
- Chat interactions now purely dynamic based on user input and admin-managed product data

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Bot Architecture
- **Framework**: Python Telegram Bot library with conversation-based flow management
- **Design Pattern**: State machine using ConversationHandler for managing user interactions
- **Error Handling**: Comprehensive error handling with safe message editing and fallback mechanisms
- **Logging**: Structured logging throughout the application for debugging and monitoring

## Database Design
- **Storage**: SQLite database for local data persistence
- **Tables**: 
  - Users table for user registration, balance tracking, and admin privileges
  - Products table for caching Digiflazz product catalog
  - Additional tables for transactions and deposits (implied by handlers)
- **Admin System**: Role-based access control with predefined admin user IDs

## API Integration
- **External Service**: Digiflazz API for digital product transactions
- **Authentication**: MD5 signature-based authentication for API requests
- **Product Sync**: Automated product catalog synchronization from Digiflazz
- **Transaction Processing**: Real-time transaction processing with reference ID generation

## User Interface
- **Interaction Model**: Inline keyboard-based menu system
- **Navigation**: Hierarchical menu structure with back/cancel options
- **User Flow**: Multi-step conversation flows for complex operations (purchases, deposits)
- **Admin Interface**: Separate admin menu for product and user management

## Key Features
- **Balance Management**: User wallet system with deposit and spending tracking
- **Product Categories**: Organized product browsing by categories
- **Transaction History**: Complete transaction logging and status tracking
- **Multi-language Support**: Indonesian language interface
- **Security**: Input validation and admin verification

# External Dependencies

## Third-party Services
- **Digiflazz API**: Primary service provider for digital products and transactions
  - Price list endpoint for product catalog
  - Transaction endpoint for processing purchases
  - Signature-based authentication system

## Python Libraries
- **python-telegram-bot**: Core Telegram bot functionality and conversation handling
- **requests**: HTTP client for external API communications
- **sqlite3**: Built-in database connectivity
- **logging**: Application logging and error tracking
- **hashlib**: MD5 signature generation for API authentication
- **datetime**: Timestamp management for transactions and user activity

## Infrastructure Requirements
- **Environment Variables**: Configuration management for sensitive credentials
- **File System**: SQLite database file storage
- **Network Access**: HTTPS connectivity for Telegram Bot API and Digiflazz API
# Overview

This project delivers a comprehensive Telegram bot and a PHP web interface for selling digital products, specifically mobile credits and PPOB (Payment Point Online Bank) services. The platform features a complete mobile-first Android UI/UX with Material Design components, automatic product synchronization, and real-time admin monitoring. The core vision is to offer a highly functional, user-friendly, and secure platform for digital product sales, accessible via both a Telegram bot and a responsive mobile web interface that feels like a native Android application.

## Recent Updates (August 2025)
- ✅ Complete mobile Android UI/UX implementation with Material Design
- ✅ Auto-update system successfully syncing 1,178 products every 30 minutes
- ✅ Mobile-first responsive interface with status bar, ripple effects, and smooth animations
- ✅ Full transaction flow: product selection → purchase → confirmation → success
- ✅ Real-time admin dashboard with comprehensive monitoring capabilities
- ✅ Working Telegram bot integration with command handlers
- ✅ **Migration Completed**: Project successfully migrated from Replit Agent to Replit environment
- ✅ **Simplified PHP Interface**: Created simple.php - streamlined interface focusing on API products
- ✅ **Dependencies Fixed**: Python telegram bot libraries properly installed and configured
- ✅ **Simple Interface Created**: New simple.php with clean category-based navigation and product display
- ✅ **Migration Finalized**: All workflows running, ready for API configuration
- ✅ **26 Category System**: Complete category system implemented with proper icons and brand organization  
- ✅ **Functional Purchase Flow**: Buy button and purchase flow fully implemented and connected to mobile interface
- ✅ **Category Separation**: Fixed categorization to properly separate Pulsa, Data, SMS Telpon, and Bundling products
- ✅ **Smart Product Classification**: Enhanced categorization engine with precise filtering for each product type
- ✅ **Code Cleanup**: Removed old PHP files and mock data, keeping only essential functional files
- ✅ **Simple Admin Panel**: Replaced complex admin center with clean, modern admin dashboard
- ✅ **Real API Categories**: Eliminated "Lainnya" category, all products now properly categorized based on Digiflazz API
- ✅ **Full PPOB System**: Complete PPOB Indonesia implementation with 18 service categories, deposit system, and professional interface

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Bot Architecture
- **Framework**: Python Telegram Bot library for conversation-based flow management.
- **Design Pattern**: State machine utilizing `ConversationHandler` for managing user interactions.
- **Error Handling**: Comprehensive error handling with safe message editing and fallback mechanisms.
- **Logging**: Structured logging for debugging and monitoring.

## Web Interface Architecture (PHP)
- **Design**: Mobile-first Android native UI/UX with Material Design components, status bar simulation, and responsive design optimized for mobile devices.
- **Mobile Interface**: Complete Android-style interface with `mobile_interface.php`, `mobile_products.php`, `mobile_purchase.php`, `mobile_confirmation.php`, and `mobile_success.php`.
- **UX Features**: Ripple effects, smooth animations, countdown timers, loading states, and native-like navigation patterns.
- **Admin Panel**: Dedicated, authenticated admin panel at `/admincenter` with modern gradient design and comprehensive management tools.
- **Auto Update Dashboard**: Complete monitoring system (`dashboard_products.php`) for product synchronization with real-time statistics and automated 30-minute update cycles.
- **Transaction Flow**: Complete end-to-end mobile transaction experience with secure payment confirmation and success animations.
- **Responsive Components**: Category filters, search functionality, floating action buttons, and mobile-optimized forms.

## Database Design
- **Storage**: SQLite database for local data persistence.
- **Tables**: Users, Products, Transactions, and Deposits.
- **Admin System**: Role-based access control with predefined admin user IDs; automatic table creation for missing structures.

## API Integration
- **Core Service**: Digiflazz API for digital product transactions, utilizing MD5 signature-based authentication.
- **Product Sync**: Automated synchronization of over 1,000 real products (1178 products as of latest update), including both prepaid and postpaid categories, with comprehensive categorization engine ensuring high accuracy.
- **Auto Update System**: Implemented automatic product update every 30 minutes with complete monitoring dashboard, logging system, and manual trigger capabilities.
- **Transaction Processing**: Real-time transaction processing with reference ID generation.

## User Interface (General)
- **Interaction Model**: Primarily inline keyboard-based menu system for the bot; web interface features structured navigation.
- **Navigation**: Hierarchical menu structure with back/cancel options and category-based brand navigation (Category → Brand → Product → Price).
- **User Flow**: Multi-step conversation flows for complex operations (purchases, deposits, payment proof uploads).
- **Key Features**: Balance management, organized product browsing by categories, transaction history, Indonesian language support, input validation, and admin verification.

# External Dependencies

## Third-party Services
- **Digiflazz API**: Primary service provider for digital product catalog (price list endpoint) and transaction processing (transaction endpoint), utilizing a signature-based authentication system.

## Python Libraries
- **python-telegram-bot**: Core Telegram bot functionality and conversation handling.
- **requests**: HTTP client for external API communications.
- **sqlite3**: Built-in database connectivity.
- **logging**: Application logging and error tracking.
- **hashlib**: MD5 signature generation for API authentication.
- **datetime**: Timestamp management for transactions and user activity.

## Infrastructure Requirements
- **Environment Variables**: For secure configuration management of sensitive credentials.
- **File System**: For SQLite database file storage.
- **Network Access**: HTTPS connectivity for Telegram Bot API and Digiflazz API.
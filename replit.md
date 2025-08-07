# Overview

This project delivers a comprehensive Telegram bot and a PHP web interface for selling digital products, specifically mobile credits and PPOB (Payment Point Online Bank) services. It provides complete conversation flows for user registration, balance management, product purchases, and a robust deposit system. The platform includes full admin management capabilities, enabling product updates, margin settings, statistics viewing, and deposit confirmations. The core vision is to offer a highly functional, user-friendly, and secure platform for digital product sales, accessible via both a Telegram bot and a responsive web interface. The project boasts extensive product categorization and real-time API integration for authentic product data.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Bot Architecture
- **Framework**: Python Telegram Bot library for conversation-based flow management.
- **Design Pattern**: State machine utilizing `ConversationHandler` for managing user interactions.
- **Error Handling**: Comprehensive error handling with safe message editing and fallback mechanisms.
- **Logging**: Structured logging for debugging and monitoring.

## Web Interface Architecture (PHP)
- **Design**: Chat-like interface mimicking Telegram bot appearance, mobile-first responsive.
- **Modularity**: PHP files are organized into modular structures (e.g., `includes/`, `setup/`, `admin/`).
- **Admin Panel**: Dedicated, authenticated admin panel at `/admincenter` with a modern gradient design, offering comprehensive management tools and broadcasting features. Features include product listing, margin adjustment, statistics, and buyer analysis.
- **Auto Update Dashboard**: Complete monitoring system for product synchronization with real-time statistics, log viewing, manual triggers, and automated 30-minute update cycles.
- **Dynamic Content**: Removal of mock data; chat interactions are purely dynamic based on user input and admin-managed product data.
- **Help System**: Integrated FAQ, terms and conditions, and customer support contact systems (WhatsApp, email).

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
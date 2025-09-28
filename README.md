# Laravel Online Game Project

## 🎮 **Project Overview**

This is a comprehensive Laravel-based online game project featuring a complete game engine with real-time multiplayer capabilities, geographic features, and advanced debugging tools.

## 🚀 **Key Features**

### **Game Engine**
- **Real-time Multiplayer** - Live game updates and interactions
- **Geographic System** - Real-world coordinate mapping and distance calculations
- **Battle System** - Comprehensive combat mechanics with troop management
- **Task System** - Mission-based gameplay with rewards
- **Movement System** - Travel mechanics with geographic calculations
- **Resource Management** - Village resources and production systems
- **Alliance System** - Player cooperation and warfare

### **Technical Features**
- **Laravel 12** - Latest Laravel framework
- **Livewire 3** - Real-time reactive components
- **Flux UI** - Modern component library
- **Geographic Service** - Real-world coordinate integration
- **Smart Caching** - Performance optimization
- **Analytics Integration** - Fathom tracking
- **Comprehensive Debugging** - Laradumps integration

## 🛠️ **Technology Stack**

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Livewire 3, Flux UI, Alpine.js
- **Database:** MySQL/MariaDB with geographic extensions
- **Caching:** Redis, SmartCache
- **Queue:** Laravel Horizon, RabbitMQ
- **Debugging:** Laradumps, Laravel Telescope
- **Analytics:** Fathom Analytics
- **Testing:** PHPUnit, Laravel Dusk

## 📦 **Installation**

### **Requirements**
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL/MariaDB
- Redis (optional)

### **Setup**
```bash
# Clone the repository
git clone <repository-url>
cd onlinegame.prus.dev

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database
php artisan db:seed

# Build assets
npm run build
```

## 🧪 **Debugging & Development**

### **Laradumps Integration** 🔍

This project includes comprehensive Laradumps integration for advanced debugging capabilities.

#### **Quick Start**
```bash
# Install Laradumps Desktop App from https://laradumps.dev

# Test Laradumps integration
php artisan laradumps:test

# Test specific components
php artisan laradumps:test --component=battle
php artisan laradumps:test --component=task

# Test specific services
php artisan laradumps:test --service=game-tick
```

#### **Features**
- ✅ **Component Debugging** - All Livewire components integrated
- ✅ **Service Debugging** - Game services with performance monitoring
- ✅ **Model Debugging** - Battle model with debugging methods
- ✅ **Performance Monitoring** - Execution time and memory tracking
- ✅ **Query Analysis** - SQL query monitoring with explanations
- ✅ **Error Tracking** - Comprehensive exception handling
- ✅ **Geographic Data** - Real-world coordinates and distances
- ✅ **Analytics Integration** - Fathom tracking integration
- ✅ **Production Safety** - Secure production configuration

#### **Documentation**
Complete Laradumps documentation is available in [`docs/laradumps/`](docs/laradumps/):
- [Integration Guide](docs/laradumps/LARADUMPS_INTEGRATION.md)
- [Usage Guide](docs/laradumps/LARADUMPS_USAGE_GUIDE.md)
- [Production Guide](docs/laradumps/LARADUMPS_PRODUCTION_GUIDE.md)
- [Deployment Guide](docs/laradumps/LARADUMPS_DEPLOYMENT_GUIDE.md)

### **Other Debugging Tools**
- **Laravel Telescope** - Application debugging
- **Laravel Debugbar** - Development toolbar
- **Laravel Horizon** - Queue monitoring
- **Laravel Pulse** - Application monitoring

## 🎯 **Game Components**

### **Livewire Components**
- **EnhancedGameDashboard** - Main game interface with real-time updates
- **BattleManager** - Combat system with geographic calculations
- **TaskManager** - Mission system with progress tracking
- **MovementManager** - Travel mechanics with distance calculations

### **Services**
- **GameTickService** - Core game loop processing
- **GameMechanicsService** - World mechanics and updates
- **GameIntegrationService** - Real-time features and user initialization
- **GeographicService** - Real-world coordinate calculations
- **LaradumpsHelperService** - Debugging utilities

### **Models**
- **Battle** - Combat system with debugging methods
- **Village** - Player settlements with geographic data
- **Player** - User accounts and game progress
- **Movement** - Travel and troop movements
- **Task** - Missions and objectives

## 🔧 **Development Commands**

```bash
# Start development server
php artisan serve

# Run with queue worker and file watcher
composer run dev

# Run tests
php artisan test

# Run Laradumps tests
php artisan laradumps:test

# Clear caches
php artisan optimize:clear

# Generate assets
npm run dev
npm run build

# Code quality checks
composer run code-quality
composer run lint
```

## 📊 **Performance Monitoring**

### **Development**
- **Laradumps** - Real-time debugging and performance monitoring
- **Laravel Telescope** - Application performance insights
- **Query Monitoring** - SQL query analysis with slow query detection

### **Production**
- **Laravel Pulse** - Application monitoring
- **Laravel Horizon** - Queue monitoring
- **Smart Caching** - Performance optimization

## 🔒 **Security**

- **Authentication** - Laravel Sanctum
- **Authorization** - Role-based permissions
- **Data Protection** - Sensitive data masking in debugging
- **Environment Security** - Production-safe configurations
- **Input Validation** - Comprehensive validation rules

## 📚 **Documentation**

- **Game Documentation** - [`documentation/`](documentation/)
- **Laradumps Documentation** - [`docs/laradumps/`](docs/laradumps/)
- **API Documentation** - Generated with Scramble
- **Database Schema** - Migrations and seeders

## 🚀 **Deployment**

### **Environment Configurations**
- **Development** - Full debugging enabled
- **Staging** - Selective debugging
- **Production** - Optimized for performance

### **Deployment Commands**
```bash
# Production deployment
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configure for production
cp laradumps.production.yaml laradumps.yaml
```

## 🧪 **Testing**

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run browser tests
php artisan dusk

# Test Laradumps integration
php artisan laradumps:test
```

## 📈 **Analytics**

- **Fathom Analytics** - User behavior tracking
- **Custom Events** - Game action tracking
- **Performance Metrics** - Application performance monitoring

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and quality checks
5. Submit a pull request

## 📄 **License**

This project is licensed under the MIT License.

## 🆘 **Support**

- **Documentation** - Check the [`docs/`](docs/) directory
- **Laradumps** - See [`docs/laradumps/`](docs/laradumps/) for debugging
- **Issues** - Report issues in the repository

---

**🎮 Happy Gaming!**

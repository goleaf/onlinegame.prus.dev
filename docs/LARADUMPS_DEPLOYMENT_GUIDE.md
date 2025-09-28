# Laradumps Deployment Guide

## 🚀 **DEPLOYMENT OVERVIEW**

This guide covers the deployment of your Laravel online game project with Laradumps integration across different environments.

## 📋 **PRE-DEPLOYMENT CHECKLIST**

### **Development Environment**
- [x] Laradumps v4.5.2 installed
- [x] Development configuration active (`laradumps.yaml`)
- [x] All debugging features enabled
- [x] Desktop app installed and connected
- [x] Test suite passing (`php artisan laradumps:test`)

### **Production Environment**
- [x] Production configuration ready (`laradumps.production.yaml`)
- [x] All debugging features disabled
- [x] Security measures implemented
- [x] Performance optimized
- [x] Documentation complete

## 🔧 **ENVIRONMENT CONFIGURATIONS**

### **Development Environment**

**Configuration File:** `laradumps.yaml`
```yaml
observers:
    auto_invoke_app: true
    dump: true
    queries: true
    slow_queries: true
    logs: true
    # ... all observers enabled

slow_queries:
    threshold_in_ms: 100

queries:
    explain: true
```

**Features Active:**
- ✅ All debugging observers enabled
- ✅ Query monitoring with explanations
- ✅ Performance monitoring
- ✅ Error tracking
- ✅ Geographic data debugging
- ✅ Analytics integration
- ✅ Cache monitoring

### **Production Environment**

**Configuration File:** `laradumps.production.yaml`
```yaml
observers:
    auto_invoke_app: false
    dump: false
    queries: false
    logs: false
    # ... all observers disabled

slow_queries:
    threshold_in_ms: 500

queries:
    explain: false
```

**Features Active:**
- ❌ All debugging observers disabled
- ❌ Query monitoring disabled
- ❌ Performance monitoring disabled
- ❌ Error tracking disabled
- ❌ Geographic data debugging disabled
- ❌ Analytics integration disabled
- ❌ Cache monitoring disabled

### **Staging Environment**

**Recommended Configuration:**
```yaml
observers:
    auto_invoke_app: false
    dump: true          # Allow debugging in staging
    queries: false      # Disable query monitoring
    logs: true          # Allow log monitoring
    slow_queries: false
    # ... selective observers enabled
```

## 🚀 **DEPLOYMENT STEPS**

### **1. Development Deployment**

```bash
# Ensure Laradumps is installed
composer install

# Use development configuration
cp laradumps.yaml laradumps.active.yaml

# Start Laravel application
php artisan serve

# Install and start Laradumps desktop app
# Download from: https://laradumps.dev

# Test integration
php artisan laradumps:test
```

### **2. Staging Deployment**

```bash
# Deploy to staging server
git checkout staging
composer install --no-dev --optimize-autoloader

# Use staging configuration
cp laradumps.staging.yaml laradumps.yaml

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Test staging deployment
php artisan laradumps:test
```

### **3. Production Deployment**

```bash
# Deploy to production server
git checkout main
composer install --no-dev --optimize-autoloader

# Use production configuration
cp laradumps.production.yaml laradumps.yaml

# Set production environment
export APP_ENV=production
export APP_DEBUG=false

# Clear and cache configurations
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify production configuration
php artisan config:show laradumps
```

## 🔒 **SECURITY CONSIDERATIONS**

### **Production Security**

1. **Disable All Debugging**
   ```yaml
   observers:
       auto_invoke_app: false
       dump: false
       queries: false
       logs: false
   ```

2. **Environment Variables**
   ```bash
   export LARADUMPS_ENABLED=false
   export APP_DEBUG=false
   export APP_ENV=production
   ```

3. **Network Security**
   ```yaml
   app:
       primary_host: 127.0.0.1    # Localhost only
       secondary_host: host.docker.internal
       port: 9191                 # Standard port
   ```

### **Data Protection**

1. **Never Debug Sensitive Data**
   ```php
   // ❌ Never do this
   ds('User data', [
       'password' => $user->password,
       'api_key' => $user->api_key
   ]);

   // ✅ Safe approach
   ds('User data', [
       'user_id' => $user->id,
       'email' => '***@example.com'
   ]);
   ```

2. **Environment Checks**
   ```php
   // Only debug in development
   if (app()->environment('local', 'development')) {
       ds('Debug data', $data);
   }
   ```

## 📊 **MONITORING AND LOGGING**

### **Production Monitoring**

1. **Application Monitoring**
   ```php
   // Use Laravel's built-in logging
   Log::info('User action', [
       'user_id' => $user->id,
       'action' => 'attack_launched',
       'timestamp' => now()
   ]);
   ```

2. **Performance Monitoring**
   ```php
   // Monitor without Laradumps in production
   if (app()->environment('production')) {
       // Use production monitoring tools
       $this->monitorPerformance($operation);
   }
   ```

3. **Error Monitoring**
   ```php
   // Use Laravel's error handling
   try {
       // Your code
   } catch (\Exception $e) {
       Log::error('Error occurred', [
           'error' => $e->getMessage(),
           'file' => basename($e->getFile()),
           'line' => $e->getLine()
       ]);
   }
   ```

### **Development Monitoring**

1. **Laradumps Debugging**
   ```php
   // Use Laradumps for development debugging
   ds('Debug info', $data)->label('Development Debug');
   ```

2. **Performance Tracking**
   ```php
   // Track performance in development
   $startTime = microtime(true);
   // ... your code ...
   LaradumpsHelperService::debugPerformance('Operation', $startTime);
   ```

## 🔄 **DEPLOYMENT AUTOMATION**

### **GitHub Actions / CI/CD**

```yaml
# .github/workflows/deploy.yml
name: Deploy Laravel App

on:
  push:
    branches: [main, staging]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Configure Laradumps
        run: |
          if [ "${{ github.ref }}" = "refs/heads/main" ]; then
            cp laradumps.production.yaml laradumps.yaml
          else
            cp laradumps.yaml laradumps.yaml
          fi
          
      - name: Deploy to server
        run: |
          # Your deployment commands
```

### **Docker Deployment**

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install Laradumps
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Configure Laradumps based on environment
ARG APP_ENV=production
COPY laradumps.${APP_ENV}.yaml laradumps.yaml

# Copy application code
COPY . .

# Set environment variables
ENV APP_ENV=${APP_ENV}
ENV APP_DEBUG=false

# Start application
CMD ["php-fpm"]
```

## 📈 **PERFORMANCE OPTIMIZATION**

### **Production Optimizations**

1. **Disable Debugging**
   ```yaml
   observers:
       auto_invoke_app: false
       dump: false
       queries: false
   ```

2. **Optimize Configuration**
   ```yaml
   config:
       sleep: 0
       macos_auto_launch: false
   ```

3. **Cache Laravel Configuration**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### **Development Optimizations**

1. **Enable Selective Debugging**
   ```yaml
   observers:
       dump: true
       queries: true
       slow_queries: true
       logs: true
   ```

2. **Performance Monitoring**
   ```php
   // Monitor performance in development
   LaradumpsHelperService::debugPerformance('Operation', $startTime);
   ```

## 🧪 **TESTING DEPLOYMENTS**

### **Pre-Deployment Testing**

```bash
# Test all integrations
php artisan laradumps:test

# Test specific components
php artisan laradumps:test --component=battle

# Test specific services
php artisan laradumps:test --service=game-tick

# Validate configuration
php artisan config:show laradumps
```

### **Post-Deployment Testing**

```bash
# Verify production configuration
php artisan config:show laradumps

# Check environment variables
php artisan env

# Test application functionality
php artisan route:list
php artisan queue:work --once
```

## 🔍 **TROUBLESHOOTING**

### **Common Deployment Issues**

1. **Laradumps Not Working**
   ```bash
   # Check if package is installed
   composer show laradumps/laradumps
   
   # Check configuration
   php artisan config:show laradumps
   
   # Clear caches
   php artisan config:clear
   ```

2. **Performance Issues**
   ```bash
   # Check configuration
   cat laradumps.yaml
   
   # Disable debugging if needed
   cp laradumps.production.yaml laradumps.yaml
   ```

3. **Security Concerns**
   ```bash
   # Verify production configuration
   grep -r "dump.*true" laradumps.yaml
   
   # Should return no results in production
   ```

## 📚 **DEPLOYMENT RESOURCES**

### **Documentation**
- [Laradumps Integration Guide](LARADUMPS_INTEGRATION.md)
- [Laradumps Usage Guide](LARADUMPS_USAGE_GUIDE.md)
- [Laradumps Production Guide](LARADUMPS_PRODUCTION_GUIDE.md)
- [Laradumps Validation Report](LARADUMPS_VALIDATION_REPORT.md)

### **Commands**
```bash
# Test integration
php artisan laradumps:test

# Check configuration
php artisan config:show laradumps

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### **Configuration Files**
- `laradumps.yaml` - Development configuration
- `laradumps.production.yaml` - Production configuration
- `laradumps.staging.yaml` - Staging configuration (create as needed)

---

**🎯 Laradumps is ready for deployment across all environments!**

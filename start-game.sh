#!/bin/bash

# Travian Online Game Launcher
# This script starts the complete Travian game

echo "🎮 Travian Online Game - Launcher"
echo "================================="

# Set environment
export APP_ENV=production
export APP_DEBUG=false

# Install dependencies
echo "📦 Installing dependencies..."
composer install --optimize-autoloader

# Run database migrations with seeds
echo "🗄️ Setting up database..."
php artisan migrate:fresh --seed --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the game server
echo "🚀 Starting Travian Online Game..."
echo "🌐 Game available at: http://localhost:8000"
echo "🎮 Enjoy playing Travian!"
echo ""

# Start Laravel development server
php artisan serve --host=0.0.0.0 --port=8000

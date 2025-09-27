#!/bin/bash

# Travian Game Test Runner
# This script runs all tests for the Travian Online Game

echo "🎮 Travian Online Game - Test Runner"
echo "===================================="

# Set environment
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate:fresh --seed --force

# Run unit tests
echo "🧪 Running unit tests..."
php artisan test --testsuite=Unit

# Run feature tests
echo "🔬 Running feature tests..."
php artisan test --testsuite=Feature

# Run browser tests (headless)
echo "🌐 Running browser tests..."
php artisan dusk --headless

# Run all tests
echo "🚀 Running all tests..."
php artisan test

# Generate code coverage report
echo "📊 Generating code coverage report..."
composer test-coverage

echo "✅ All tests completed!"
echo "📈 Code coverage report generated in storage/app/coverage/"
echo "🎮 Travian Online Game is ready to play!"

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
composer install --optimize-autoloader

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
if composer run-script --list 2>/dev/null | grep -Eq '^\s*test-coverage\b'; then
  composer test-coverage
  coverage_generated=1
else
  echo "⚠️ Skipping coverage generation; composer script 'test-coverage' is not defined."
  coverage_generated=0
fi

echo "✅ All tests completed!"
if [ "$coverage_generated" -eq 1 ]; then
  echo "📈 Code coverage report generated in storage/app/coverage/"
else
  echo "ℹ️ Code coverage report not generated."
fi
echo "🎮 Travian Online Game is ready to play!"

#!/bin/bash

# Pretty PHP Code Formatter Script for Laravel Game Project
# This script provides easy access to Pretty PHP formatting commands

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Pretty PHP executable
PRETTY_PHP="./pretty-php.phar"

# Check if Pretty PHP is available
if [ ! -f "$PRETTY_PHP" ]; then
    echo -e "${RED}Error: Pretty PHP not found at $PRETTY_PHP${NC}"
    echo "Please run: wget -O pretty-php.phar https://github.com/lkrms/pretty-php/releases/latest/download/pretty-php.phar"
    echo "Then: chmod +x pretty-php.phar"
    exit 1
fi

# Function to show usage
show_usage() {
    echo -e "${BLUE}Pretty PHP Code Formatter for Laravel Game Project${NC}"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  format          Format all PHP files (app, database, tests)"
    echo "  check           Check if files need formatting (dry run)"
    echo "  app             Format only app directory"
    echo "  database        Format only database directory"
    echo "  tests           Format only tests directory"
    echo "  help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 format       # Format all files"
    echo "  $0 check        # Check formatting without changes"
    echo "  $0 app          # Format only app directory"
    echo ""
}

# Function to format files
format_files() {
    local target="$1"
    local check_mode="$2"
    
    echo -e "${BLUE}Formatting PHP files in: $target${NC}"
    
    if [ "$check_mode" = "true" ]; then
        echo -e "${YELLOW}Running in check mode (no changes will be made)${NC}"
        $PRETTY_PHP "$target" --check
    else
        $PRETTY_PHP "$target"
    fi
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Formatting completed successfully${NC}"
    else
        echo -e "${RED}✗ Formatting failed${NC}"
        exit 1
    fi
}

# Main script logic
case "${1:-help}" in
    "format")
        echo -e "${BLUE}Formatting all PHP files...${NC}"
        format_files "app"
        format_files "database"
        format_files "tests"
        echo -e "${GREEN}✓ All files formatted successfully${NC}"
        ;;
    "check")
        echo -e "${BLUE}Checking formatting for all PHP files...${NC}"
        format_files "app" "true"
        format_files "database" "true"
        format_files "tests" "true"
        echo -e "${GREEN}✓ Format check completed${NC}"
        ;;
    "app")
        format_files "app"
        ;;
    "database")
        format_files "database"
        ;;
    "tests")
        format_files "tests"
        ;;
    "help"|"--help"|"-h")
        show_usage
        ;;
    *)
        echo -e "${RED}Unknown command: $1${NC}"
        echo ""
        show_usage
        exit 1
        ;;
esac


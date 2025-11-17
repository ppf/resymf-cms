#!/bin/bash

# ReSymf-CMS (Symfony 7) Deployment Script
#
# This script automates the deployment of the application.
# It should be run from the root of the `symfony7-skeleton` directory.
#
# Usage: ./deploy.sh
#

# --- Configuration ---
# Git branch to deploy
GIT_BRANCH="main"

# Maintenance flag file path
MAINTENANCE_FLAG="public/maintenance.flag"

# --- Colors for output ---
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# --- Helper Functions ---
function print_step {
    echo -e "\n${BLUE}==> $1${NC}"
}

function print_success {
    echo -e "${GREEN}✓ $1${NC}"
}

function print_warning {
    echo -e "${YELLOW}⚠ $1${NC}"
}

function print_error {
    echo -e "${RED}✗ $1${NC}"
    exit 1
}

function check_command {
    if ! command -v $1 &> /dev/null; then
        print_error "$1 command could not be found. Please install it."
    fi
}

# --- Main Script ---

# 1. Initial Checks
print_step "Starting Deployment Process for ReSymf-CMS"
check_command git
check_command composer
check_command php

if [ ! -f "composer.json" ] || [ ! -d "bin" ]; then
    print_error "This script must be run from the root of the symfony7-skeleton directory."
fi

# 2. Confirm Deployment
read -p "Are you sure you want to deploy the latest version of the '$GIT_BRANCH' branch? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_warning "Deployment cancelled."
    exit 0
fi

# 3. Maintenance Mode ON
print_step "Enabling Maintenance Mode"
touch $MAINTENANCE_FLAG
print_success "Maintenance mode enabled."

# 4. Fetch Latest Code
print_step "Fetching latest code from Git..."
git fetch origin
if ! git checkout $GIT_BRANCH; then
    print_error "Could not checkout branch '$GIT_BRANCH'."
fi
if ! git pull origin $GIT_BRANCH; then
    print_error "Could not pull latest code from origin."
fi
print_success "Code updated successfully."

# 5. Install Composer Dependencies
print_step "Installing Composer dependencies for production..."
composer install --no-dev --optimize-autoloader
if [ $? -ne 0 ]; then
    print_error "Composer install failed."
fi
print_success "Composer dependencies installed."

# 6. Run Database Migrations
print_step "Running Doctrine database migrations..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
if [ $? -ne 0 ]; then
    print_warning "Database migrations failed. Check the output above."
    # We don't exit here, as it might be a non-critical issue.
fi
print_success "Database migrations completed."

# 7. Clear and Warm Up Cache
print_step "Clearing and warming up the application cache for production..."
php bin/console cache:clear --env=prod
if [ $? -ne 0 ]; then
    print_error "Cache clear failed."
fi
print_success "Cache cleared and warmed up."

# 8. Compile Asset Map
print_step "Compiling the asset map..."
php bin/console asset-map:compile
if [ $? -ne 0 ]; then
    print_warning "Asset map compilation failed."
fi
print_success "Asset map compiled."

# 9. Maintenance Mode OFF
print_step "Disabling Maintenance Mode"
if [ -f "$MAINTENANCE_FLAG" ]; then
    rm $MAINTENANCE_FLAG
    print_success "Maintenance mode disabled."
else
    print_warning "Maintenance flag not found, skipping."
fi

# 10. Final Success Message
print_step "Deployment Complete!"
print_success "ReSymf-CMS has been successfully deployed."
echo -e "\n${YELLOW}Don't forget to check the application logs and test key functionality.${NC}"
echo -e "${YELLOW}Log file: tail -f var/log/prod.log${NC}"

exit 0

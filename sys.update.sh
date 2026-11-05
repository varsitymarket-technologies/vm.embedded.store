#!/bin/bash

#   TITLE   : Application Deployment & Update Script   
#   DESC    : Initializes git, configures remote origin, and updates from master. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.2.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/11/05

REPO_URL="https://github.com/varsitymarket-technologies/vm.embedded.store.git"

# 1. Initialize local git repository if missing
if [ ! -d ".git" ]; then
    echo "[INFO] Initializing local Git repository..."
    git init
fi

# 2. Add remote origin if not already configured
if ! git remote get-url origin >/dev/null 2>&1; then
    echo "[INFO] Configuring remote origin..."
    git remote add origin "$REPO_URL"
fi

# 3. Fetch and sync with master
echo "[INFO] Fetching latest changes from master..."
git fetch origin master 
git reset --hard origin/master

# 4. Ensure configuration environment exists
if [ ! -f ".env" ] && [ -f "copy.env" ]; then
    echo "[INFO] .env file not found. Generating from copy.env template..."
    cp copy.env .env
fi

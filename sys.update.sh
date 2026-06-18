#!/bin/bash

#   TITLE   : Application System Update Script   
#   DESC    : This script updates the application to the latest version from the master branch of the git repository. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/06/18

git fetch origin master 
git reset --hard origin/master
#cp copy.env .env
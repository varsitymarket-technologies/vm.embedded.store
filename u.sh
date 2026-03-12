#!/bin/bash

git fetch origin master 
git reset --hard origin/master
cp copy.env .env
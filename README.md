PHP VM Generator tool
=====================

[![Build Status](https://travis-ci.org/valentine94/vmgen-php.svg?branch=master)](https://travis-ci.org/valentine94/vmgen-php)

## Description:
A PHP tool that allows users to automatically prepare project 
directories for using it with a [DrupalVM](https://github.com/geerlingguy/drupal-vm) .

## Requirements:
# Requirements:
- Linux-based OS(tested on Ubuntu and macOS)
- Vagrant
- VirtualBox
- Ansible
- Vagrant plugins:
 hostsupdater (`vagrant plugin install vagrant-hostsupdater`)
 auto_network (`vagrant plugin install vagrant-auto_network`)
- PHP 5.6+
- Composer

## Installation:
### Install via Composer
`composer global require valentine94/vmgen-php`

## Usage:
From command line interface run the following command:
`vmgen-php --php=PHP_VERSION --project-name=PROJECT_NAME`
for example:
`vmgen-php --php=7 --project-name=my_new_project`
The tool will create your project directory at
 **$HOME/projects/PROJECT_NAME**
 Use `vmgen-php --help` to see more info about params and usage.

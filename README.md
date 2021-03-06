PHP VM Generator tool
=====================

[![Build Status](https://travis-ci.org/valentine94/vmgen-php.svg?branch=master)](https://travis-ci.org/valentine94/vmgen-php)[![Packagist](https://img.shields.io/packagist/v/valentine94/vmgen-php.svg)](https://packagist.org/packages/valentine94/vmgen-php)[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)[![GitHub forks](https://img.shields.io/github/forks/valentine94/vmgen-php.svg)](https://github.com/valentine94/vmgen-php/network)[![GitHub issues](https://img.shields.io/github/issues/valentine94/vmgen-php.svg)](https://github.com/valentine94/vmgen-php/issues)

## Description:
A PHP tool that allows users to automatically prepare project 
directories for using it with a [DrupalVM](https://github.com/geerlingguy/drupal-vm).

## Requirements:
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
`composer global require valentine94/vmgen-php`

## Usage:
From command line interface run the following command:
`vmgen-php --php=PHP_VERSION --project-name=PROJECT_NAME`
for example:
`vmgen-php --php=7 --project-name=my_new_project`
The tool will create your project directory at
 **$HOME/projects/PROJECT_NAME**
 Use `vmgen-php --help` to see more info about params and usage.

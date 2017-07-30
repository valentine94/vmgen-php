<?php
/**
 * @file
 * Main vmgen executable file.
 */

require __DIR__ . '/tools.php';

if (!empty($argv)) {
  $arguments    = parse_arguments_into_assoc_array($argv);
  $current_user = get_current_user();
  $platform     = (stripos(PHP_OS, 'darwin') === 0) ? 'Mac' : 'Linux';
  $projects_dir = ($platform === 'Mac' ? '/Users/' : '/home/') . $current_user . '/projects';
  // Ensure we have arguments.
  // Prepare Drupal VM.
  prepare_drupal_vm();
  // Prepare project directories structure.
  prepare_directories($projects_dir, $arguments['project_name']);
  // Preparing drupal vm for the project's directory.
  unzip_file('/tmp/drupal_vm.zip', $projects_dir . '/' . $arguments['project_name']);
  rmove($projects_dir . '/' . $arguments['project_name'] . '/drupal-vm-4.6.0', $projects_dir . '/' . $arguments['project_name'] . '/vm');
  chmod_r($projects_dir . '/' . $arguments['project_name']);
  // @TODO: Create config.yml for the project.
}

/**
 * Prepare project directories.
 *
 * @param string $projects_dir
 *   Projects directory path.
 * @param string $project_name
 *   Project name.
 */
function prepare_directories($projects_dir, $project_name) {
  if (!is_dir($projects_dir)) {
    mkdir($projects_dir);
  }
  if (!is_dir($projects_dir . DIRECTORY_SEPARATOR . $project_name)) {
    mkdir($projects_dir . DIRECTORY_SEPARATOR . $project_name);
    chmod($projects_dir . DIRECTORY_SEPARATOR . $project_name, 0777);
  }
  if (!is_dir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'docroot')) {
    mkdir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'docroot');
    chmod($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'docroot', 0777);
  }
  if (!is_dir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'vm')) {
    mkdir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'vm');
    chmod($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'vm', 0777);
  }
}

/**
 * Prepare Drupal VM.
 */
function prepare_drupal_vm() {
  // Ensure we don't have a downloaded drupal_vm, to prevent doing it twice.
  if (!is_file('/tmp/drupal_vm.zip')) {
    // Download drupal_vm to the temp directory.
    file_put_contents(
      '/tmp/drupal_vm.zip',
      fopen('https://github.com/geerlingguy/drupal-vm/archive/4.6.0.zip', 'r')
    );
    // Check if we've downloaded it successfully.
    if (!file_exists('/tmp/drupal_vm.zip')) {
      die('Drupal VM does not been downloaded');
    }
  }
}


/**
 * Parse the cli arguments into a correct array format.
 *
 * @param array $argv
 *   Cli arguments array.
 *
 * @return array
 *   Formatted cli arguments array.
 */
function parse_arguments_into_assoc_array($argv) {
  // Ignore the first arg as it is a command name.
  unset($argv[0]);

  // Parse the cli arguments into a correct array format.
  $arguments = [];
  foreach ($argv as $argument) {
    // Check for a php version argument.
    if (stripos($argument, '--php') !== FALSE) {
      $arguments['php'] = str_replace('--php=', '', $argument);
      continue;
    }
    // Check for a project name argument.
    if (stripos($argument, '--project-name') !== FALSE) {
      $arguments['project_name'] = str_replace('--project-name=', '', $argument);
      continue;
    }
  }
  return $arguments;
}

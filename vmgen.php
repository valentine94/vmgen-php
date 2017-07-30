<?php
/**
 * @file
 * Main vmgen executable file.
 */

if (!empty($argv)) {
  $arguments    = parse_arguments_into_assoc_array($argv);
  $current_user = get_current_user();
  $platform     = (stripos(PHP_OS, 'darwin') === 0) ? 'Mac' : 'Linux';
  $projects_dir = ($platform === 'Mac' ? '/Users/' : '/home/') . $current_user . '/projects';
  // Ensure we have arguments.
  if (!empty($arguments)) {
    // Prepare Drupal VM.
    prepare_drupal_vm();
    // Prepare project directories structure.
    prepare_directories($projects_dir, $arguments['project_name']);
  }
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
  }
  if (!is_dir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'docroot')) {
    mkdir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'docroot');
  }
  if (!is_dir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'vm')) {
    mkdir($projects_dir . DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR . 'vm');
  }
}

/**
 * Prepare Drupal VM.
 */
function prepare_drupal_vm() {
  // Ensure we don't have a downloaded drupal_vm, to prevent doing it twice.
  if (!is_dir('/tmp/drupal_vm.zip')) {
    // Download drupal_vm to the temp directory.
    file_put_contents(
      '/tmp/drupal_vm.zip',
      fopen('https://github.com/geerlingguy/drupal-vm/archive/4.6.0.zip', 'r')
    );
    // Check if we've downloaded it successfully.
    if (!file_exists('/tmp/drupal_vm.zip')) {
      die('Drupal VM does not been downloaded');
    }
    // Unzip the downloaded archive.
    if (unzip_file('/tmp/drupal_vm.zip', '/tmp/drupal_vm')) {
      echo 'Drupal VM zip archive extracted successfully';
    }
    else {
      die('Drupal VM zip archive extraction failed');
    }
    unlink('/tmp/drupal_vm.zip');
  }
}

/**
 * Unzip the file.
 *
 * @param string $file
 *   Filename.
 * @param string $destination
 *   Destination directory.
 *
 * @return bool
 *   TRUE - if file has been unzipped, otherwise FALSE.
 */
function unzip_file($file, $destination) {
  // Create object.
  $zip = new ZipArchive;
  // Open archive.
  if ($zip->open($file) !== TRUE) {
    return FALSE;
  }
  // Check if destination directory exist, otherwise create it.
  if (!is_dir($destination)) mkdir($destination);
  // Extract contents to destination directory.
  $zip->extractTo($destination);
  // Close archive.
  $zip->close();
  return TRUE;
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

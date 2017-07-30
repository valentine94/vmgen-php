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
  // @TODO: Add "echo-es" for the whole process.
}

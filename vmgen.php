<?php
/**
 * @file
 * Main vmgen executable file.
 */

require __DIR__ . '/tools.php';
if (!empty($argv)) {
  echo "Preparing DrupalVM for the project:\n";
  progress_bar(0, 15);
  $arguments    = parse_arguments_into_assoc_array($argv);
  progress_bar(1, 15);
  $current_user = get_current_user();
  progress_bar(2, 15);
  $platform     = (stripos(PHP_OS, 'darwin') === 0) ? 'Mac' : 'Linux';
  progress_bar(3, 15);
  $projects_dir = ($platform === 'Mac' ? '/Users/' : '/home/') . $current_user . '/projects';
  progress_bar(4, 15);
  prepare_drupal_vm();
  progress_bar(5, 15);
  prepare_directories($projects_dir, $arguments['project_name']);
  progress_bar(6, 15);
  unzip_file('/tmp/drupal_vm.zip', $projects_dir . '/' . $arguments['project_name']);
  progress_bar(7, 15);
  rmove($projects_dir . '/' . $arguments['project_name'] . '/drupal-vm-4.6.0', $projects_dir . '/' . $arguments['project_name'] . '/vm');
  progress_bar(8, 15);
  chmod_r($projects_dir . '/' . $arguments['project_name']);
  progress_bar(9, 15);
  $config_string = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'drupal_vm.config.yml');
  progress_bar(10, 15);
  $config_string = str_replace('{!project_name!}', $arguments['project_name'], $config_string);
  progress_bar(11, 15);
  $config_string = str_replace('{!php_version!}', $arguments['php'], $config_string);
  progress_bar(12, 15);
  file_put_contents($projects_dir . '/' . $arguments['project_name'] . '/vm/config.yml', $config_string);
  progress_bar(13, 15);
  chmod($projects_dir . '/' . $arguments['project_name'] . '/vm/config.yml', 0777);
  progress_bar(14, 15);

  $cmd = "vagrant box list | grep 'geerlingguy/ubuntu1604'";
  if (shell_exec("vagrant box list | grep 'geerlingguy/ubuntu1604'") == '') {
    shell_exec("vagrant box add --name geerlingguy/ubuntu1604 https://app.vagrantup.com/geerlingguy/boxes/ubuntu1604");
  }
  progress_bar(15, 15); echo PHP_EOL;

  echo "VM generation completed at " . $projects_dir . '/' . $arguments['project_name'] . " directory.\n";
}
else {
  echo "Missing arguments\n";
}

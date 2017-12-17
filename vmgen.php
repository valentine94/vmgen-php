<?php
/**
 * @file
 * Main vmgen executable file.
 */
// Include composer's auto-loader.
require_once __DIR__ . '/vendor/autoload.php';

use Valentine94\Vmgen\Vmgen;

// Handle the help message.
if ($argv[1] == '--help') {
  Vmgen::showHelpMessage();
}
elseif (!empty($argv)) {
  echo "Preparing DrupalVM for the project:\n";
  $vmgen = new Vmgen(__DIR__);
  $vmgen->prepareArguments($argv)
    ->prepareDrupalVm()
    ->prepareDirectories()
    ->unpackDrupalVm()
    ->moveDrupalVmFiles()
    ->fixProjectFilesPermissions()
    ->processConfigTokens()
    ->fixConfigFilePermissions()
    ->importVagrantBox();
  
  $vmgen->showCompleteMessage();
}
else {
  echo "Missing arguments\n";
}

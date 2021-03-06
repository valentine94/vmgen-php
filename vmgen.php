<?php

/**
 * @file
 * Main vmgen executable file.
 */

// Set up auto-loader.
if (file_exists($autoloadFile = __DIR__ . '/vendor/autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../autoload.php')
) {
  require_once $autoloadFile;
}
else {
  throw new Exception('Autoloader not found!');
}

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

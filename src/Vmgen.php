<?php

namespace Valentine94\Vmgen;

use DirectoryIterator;
use ErrorException;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Class Vmgen.
 *
 * @package Vmgen
 */
class Vmgen {
  /**
   * DrupalVM version.
   */
  const DRUPALVM_VERSION = '4.7.0';
  /**
   * Number of steps for progress bar.
   *
   * @var int
   */
  protected static $progressBarSteps = 15;
  /**
   * Arguments array.
   *
   * @var array
   */
  protected $arguments;
  /**
   * Current system's user name.
   *
   * @var string
   */
  protected $currentUser;
  /**
   * Platform name.
   *
   * @var string
   */
  protected $platform;
  /**
   * Projects directory path.
   *
   * @var string
   */
  protected $projectsDirectory;
  /**
   * VMGen directory path.
   *
   * @var string
   */
  protected $vmgenDirectory;

  /**
   * Vmgen constructor.
   *
   * @param string $vmgen_directory
   *   VMGen directory path.
   */
  public function __construct($vmgen_directory) {
    $this->vmgenDirectory = $vmgen_directory;
    $this->progressBar(0);
    $this->currentUser = get_current_user();
    $this->progressBar(1);
    $this->platform = (stripos(PHP_OS, 'darwin') === 0) ? 'Mac' : 'Linux';
    $this->progressBar(2);
    $this->projectsDirectory =
      (
        $this->platform === 'Mac'
          ? '/Users/'
          : '/home/'
      ) . $this->currentUser . '/projects';
    $this->progressBar(3);
  }

  /**
   * Show help message.
   */
  public static function showHelpMessage() {
    echo "Usage: vmgen-php --php=PHP_VERSION --project-name=PROJECT_NAME\n\n";
    echo "Arguments explanation:\n";
    echo "--php \t\t PHP version\n";
    echo "--project-name \t Project name version\n";
    exit;
  }

  /**
   * Prepare required arguments for VM generation.
   *
   * @param array $args
   *   Arguments array.
   *
   * @return $this
   */
  public function prepareArguments($args) {
    // Ignore the first arg as it is a command name.
    unset($args[0]);

    // Parse the cli arguments into a correct array format.
    $this->arguments = [];
    foreach ($args as $argument) {
      // Check for a php version argument.
      if (stripos($argument, '--php') !== FALSE) {
        $this->arguments['php'] = str_replace('--php=', '', $argument);
        if ($this->arguments['php'] == '5') {
          $this->arguments['php'] = "'5.6'";
        }
        elseif ($this->arguments['php'] == '7') {
          $this->arguments['php'] = "'7.0'";
        }
        continue;
      }
      // Check for a project name argument.
      if (stripos($argument, '--project-name') !== FALSE) {
        $this->arguments['project_name'] = str_replace('--project-name=', '', $argument);
        continue;
      }
    }
    $this->progressBar(4);
    return $this;
  }

  /**
   * Prepare DrupalVM.
   */
  public function prepareDrupalVm() {
    // Ensure we don't have a downloaded drupal_vm, to prevent doing it twice.
    if (!is_file('/tmp/drupal_vm.zip')) {
      // Download drupal_vm to the temp directory.
      file_put_contents(
        '/tmp/drupal_vm.zip',
        fopen('https://github.com/geerlingguy/drupal-vm/archive/' . self::DRUPALVM_VERSION . '.zip', 'r')
      );
      // Check if we've downloaded it successfully.
      if (!file_exists('/tmp/drupal_vm.zip')) {
        die('Drupal VM does not been downloaded');
      }
    }
    $this->progressBar(5);
    return $this;
  }

  /**
   * Prepare required directories.
   */
  public function prepareDirectories() {
    $project_name = $this->arguments['project_name'];
    if (!is_dir($this->projectsDirectory)) {
      mkdir($this->projectsDirectory);
    }
    $project_dir = $this->projectsDirectory . DIRECTORY_SEPARATOR . $project_name;
    if (!is_dir($project_dir)) {
      mkdir($project_dir);
      chmod($project_dir, 0777);
    }
    $docroot = $project_dir . DIRECTORY_SEPARATOR . 'docroot';
    if (!is_dir($docroot)) {
      mkdir($docroot);
      chmod($docroot, 0777);
    }
    $vm = $project_dir . DIRECTORY_SEPARATOR . 'vm';
    if (!is_dir($vm)) {
      mkdir($vm);
      chmod($vm, 0777);
    }
    $this->progressBar(6);
    return $this;
  }

  /**
   * Unpack the DrupalVM archive.
   */
  public function unpackDrupalVm() {
    // Create object.
    $zip = new ZipArchive();
    // Open archive.
    if ($zip->open('/tmp/drupal_vm.zip') !== TRUE) {
      return FALSE;
    }
    // Extract contents to destination directory.
    $zip->extractTo($this->projectsDirectory . DIRECTORY_SEPARATOR . $this->arguments['project_name']);
    // Close archive.
    $zip->close();
    $this->progressBar(7);
    return $this;
  }

  /**
   * Move DrupalVM files into an appropriate directory.
   *
   * @throws \ErrorException
   */
  public function moveDrupalVmFiles() {
    $src = $this->projectsDirectory . '/' . $this->arguments['project_name'] . '/drupal-vm-' . self::DRUPALVM_VERSION;
    $dest = $this->projectsDirectory . '/' . $this->arguments['project_name'] . '/vm';
    // If source is not a directory stop processing.
    if (!is_dir($src)) {
      throw new InvalidArgumentException('The source passed in does not appear to be a valid directory: [' . $src . ']', 1);
    }

    // If the destination directory does not exist create it.
    if (!is_dir($dest)) {
      if (!mkdir($dest, 0777, TRUE)) {
        throw new InvalidArgumentException('The destination does not exist, and I can not create it: [' . $dest . ']', 2);
      }
    }

    $emptied_dirs = array();

    // Open the source directory to read in files.
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
      // We need to get a path relative to where we are copying from.
      $relativePath = str_replace($src, '', $f->getRealPath());

      // And we can create a destination now.
      $destination = $dest . $relativePath;

      // If it is a file, lets just move that sucker over.
      if ($f->isFile()) {
        $path_parts = pathinfo($destination);

        // If we don't have a directory for this yet.
        if (!is_dir($path_parts['dirname'])) {
          // Lets create one!
          if (!mkdir($path_parts['dirname'], 0777, TRUE)) {
            throw new ErrorException("Failed to create the destination directory: [{$path_parts['dirname']}]", 5);
          }
        }

        if (!rename($f->getRealPath(), $destination)) {
          throw new ErrorException("Failed to rename file [{$f->getRealPath()}] to [$destination]", 6);
        }

        // If it is a directory, lets handle it.
      }
      elseif ($f->isDir()) {
        // Check to see if the destination directory already exists.
        if (!is_dir($destination)) {
          if (!mkdir($destination, 0777, TRUE)) {
            throw new ErrorException("Failed to create the destination directory: [$destination]", 7);
          }
        }

        // We will attempt deleting these after we have moved all the files.
        array_push($emptied_dirs, $f->getRealPath());

        // If it is something else, throw a fit.
        // Symlinks can potentially end up here.
        // I haven't tested them yet, but I think isFile() will typically
        // just pick them up and work.
      }
      else {
        throw new ErrorException("I found [{$f->getRealPath()}] yet it appears to be neither a directory nor a file. [{$f->isDot()}] I don't know what to do with that!", 8);
      }
    }

    foreach ($emptied_dirs as $empty_dir) {
      if (realpath($empty_dir) == realpath($src)) {
        continue;
      }
      if (!is_readable($empty_dir)) {
        throw new ErrorException("The source directory: [$empty_dir] is not Readable", 9);
      }

      // Delete the old directory.
      if (!rmdir($empty_dir)) {
        // The directory is empty, we should have successfully deleted it.
        if ((count(scandir($empty_dir)) == 2)) {
          throw new ErrorException("Failed to delete the source directory: [$empty_dir]", 10);
        }
      }
    }

    // Finally, delete the base of the source directory we just recurse through.
    if (!rmdir($src)) {
      throw new ErrorException("Failed to delete the base source directory: [$src]", 11);
    }
    $this->progressBar(8);
    return $this;
  }

  /**
   * Fix project's files permissions.
   *
   * @param string|null $path
   *   Project's root path.
   *
   * @return $this
   */
  public function fixProjectFilesPermissions($path = NULL) {
    if (empty($path)) {
      $path = $this->projectsDirectory . DIRECTORY_SEPARATOR . $this->arguments['project_name'];
    }
    $dir = new DirectoryIterator($path);
    foreach ($dir as $item) {
      chmod($item->getPathname(), 0777);
      if ($item->isDir() && !$item->isDot()) {
        $this->fixProjectFilesPermissions($item->getPathname());
      }
    }
    $this->progressBar(9);
    return $this;
  }

  /**
   * Process config's tokens.
   */
  public function processConfigTokens() {
    $config_string = file_get_contents($this->vmgenDirectory . DIRECTORY_SEPARATOR . 'drupal_vm.config.yml');
    $this->progressBar(10);
    $config_string = str_replace('{!project_name!}', $this->arguments['project_name'], $config_string);
    $this->progressBar(11);
    $config_string = str_replace('{!php_version!}', $this->arguments['php'], $config_string);
    $this->progressBar(12);
    file_put_contents($this->projectsDirectory . DIRECTORY_SEPARATOR . $this->arguments['project_name'] . '/vm/config.yml', $config_string);
    $this->progressBar(13);
    return $this;
  }

  /**
   * Fix config file's permissions.
   */
  public function fixConfigFilePermissions() {
    chmod($this->projectsDirectory . DIRECTORY_SEPARATOR . $this->arguments['project_name'] . '/vm/config.yml', 0777);
    $this->progressBar(14);
    return $this;
  }

  /**
   * Import vagrant box if it doesn't exist.
   */
  public function importVagrantBox() {
    if (shell_exec("vagrant box list | grep 'geerlingguy/ubuntu1604'") == '') {
      shell_exec("vagrant box add --name geerlingguy/ubuntu1604 https://app.vagrantup.com/geerlingguy/boxes/ubuntu1604");
    }
    $this->progressBar(15);
    echo PHP_EOL;
    return $this;
  }

  /**
   * Get path of newly created project directory.
   *
   * @return string
   *   Path of newly created project directory.
   */
  protected function getCreatedProjectDir() {
    return $this->projectsDirectory . DIRECTORY_SEPARATOR . $this->arguments['project_name'];
  }

  /**
   * Show message of process completion.
   */
  public function showCompleteMessage() {
    echo "VM generation completed at " . $this->getCreatedProjectDir() . " directory.\n";
  }

  /**
   * Write progress bar to the console output.
   *
   * @param int $done
   *   Steps are node.
   */
  protected function progressBar($done) {
    $perc = floor(($done / self::$progressBarSteps) * 100);
    $left = 100 - $perc;
    $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%%", "", "");
    fwrite(STDOUT, $write);
  }

}

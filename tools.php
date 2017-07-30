<?php
/**
 * @file
 * PHP tools functions.
 */

/**
 * A Recursive directory move that allows exclusions. The excluded items in the src will be deleted
 * rather than moved.
 *
 * @param string $src
 *   The fully qualified source directory to copy.
 * @param string $dest
 *   The fully qualified destination directory to copy to.
 * @param array $exclusions
 *   The fully qualified destination directory to copy to.
 *
 * @return bool
 *   Returns TRUE on success, throws an error otherwise.
 *
 * @throws \ErrorException
 */
function rmove($src, $dest, $exclusions = array()) {
  // If source is not a directory stop processing.
  if (!is_dir($src)) {
    throw new InvalidArgumentException('The source passed in does not appear to be a valid directory: ['.$src.']', 1);
  }

  // If the destination directory does not exist create it.
  if (!is_dir($dest)) {
    if (!mkdir($dest, 0777, TRUE)) {
      throw new InvalidArgumentException('The destination does not exist, and I can not create it: ['.$dest.']', 2);
    }
  }

  // Ensure exclusions parameter is an array.
  if (!is_array($exclusions)) {
    throw new InvalidArgumentException('The exclustion parameter is not an array, it MUST be an array.', 3);
  }

  $emptied_dirs = array();

  // Open the source directory to read in files.
  foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {

    // Check to see if we should ignore this file or directory.
    foreach ($exclusions as $pattern) {
      if (preg_match($pattern, $f->getRealPath())) {
        if ($f->isFile()) {
          if (!unlink($f->getRealPath())) {
            throw new ErrorException("Failed to delete file [{$f->getRealPath()}] ", 4);
          }
        }
        elseif ($f->isDir()) {
          // We will attempt deleting these after we have moved all the files.
          array_push($emptied_dirs, $f->getRealPath());
        }

        // Because we have to jump up two foreach levels.
        continue 2;
      }
    }
    // We need to get a path relative to where we are copying from.
    $relativePath = str_replace($src, '', $f->getRealPath());

    // And we can create a destination now.
    $destination = $dest . $relativePath;

    // If it is a file, lets just move that sucker over.
    if($f->isFile()) {
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
  return TRUE;
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
  // Extract contents to destination directory.
  $zip->extractTo($destination);
  // Close archive.
  $zip->close();
  return TRUE;
}

/**
 * Change file's and directories permissions recursively.
 *
 * @param string $path
 *   Path.
 */
function chmod_r($path) {
  $dir = new DirectoryIterator($path);
  foreach ($dir as $item) {
    chmod($item->getPathname(), 0777);
    if ($item->isDir() && !$item->isDot()) {
      chmod_r($item->getPathname());
    }
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

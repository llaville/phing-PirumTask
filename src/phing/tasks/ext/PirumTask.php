<?php
/**
 * Copyright (c) 2013, Laurent Laville <pear@laurent-laville.org>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  GIT: $Id$
 * @link     https://github.com/llaville/phing-PirumTask
 */

require_once 'phing/Task.php';
require_once 'phing/types/Mapping.php';

/**
 * PEAR Channel Server Manager
 *
 * PHP version 5
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  Release: $package_version@
 * @link     https://github.com/llaville/phing-PirumTask
 */
class PirumTask extends Task
{
    /**
     * Base server directory
     * @var PhingFile
     */
    protected $destDir;

    /**
     * Force overwrite configuration file if it already exist
     * @var boolean
     */
    protected $force = false;

    /**
     * Suppress Pirum chatter
     * @var boolean
     */
    protected $quiet = false;

    /**
     * Nested <mapping> types.
     * @var Mapping
     */
    protected $mappings = array();

    /**
     * PEAR Channel Server definition
     */
    private $name;
    private $summary;
    private $alias;
    private $url;

    /**
     * The init method check if Pirus or Pirum is available
     * (exists and can be loaded)
     *
     * @return void
     * @throws BuildException
     * @link   https://github.com/llaville/Pirus
     * @link   https://github.com/fabpot/Pirum
     */
    public function init()
    {
        @include_once 'pirus';
        if (!class_exists('Pirum_CLI', false)) {
            // fallback to standard pirum distribution, if pirus is not installed
            @include_once 'pirum';
            if (!class_exists('Pirum_CLI', false)) {
                throw new BuildException(
                    'PirumTask required at least Pirus or Pirum installed.'
                );
            }
        }
    }

    /**
     * Destination directory for output files.
     *
     * @param string $destDir Base server directory
     *
     * @return void
     * @throws BuildException
     */
    public function setDestDir($destDir)
    {
        if (!is_dir($destDir)) {
            throw new BuildException("You must specify a valid directory.");
        }
        $this->destDir = $destDir;
    }

    /**
     * Force overwrite configuration file if it already exist.
     *
     * @param boolean $overwrite Force overwrite of configuration file
     *
     * @return void
     */
    public function setForce($overwrite)
    {
        if (is_bool($overwrite)) {
            $this->force = $overwrite;
        }
    }

    /**
     * Suppress Pirum chatter.
     *
     * @param boolean $quiet Suppress Pirum chatter
     *
     * @return void
     */
    public function setQuiet($quiet)
    {
        if (is_bool($quiet)) {
            $this->quiet = $quiet;
        }
    }

    /**
     * The main entry point method
     *
     * @return void
     * @throws BuildException
     */
    public function main()
    {
        if ($this->destDir === null) {
            throw new BuildException(
                "You must specify a directory using 'destdir' attribute."
            );
        }

        if (count($this->mappings) === 0) {
            $this->runPirum('build');
            $this->log('PEAR server files was updated');
            return;
        }

        $items = array('name', 'summary', 'alias', 'url');

        foreach ($this->mappings as $map) {
            $key      = $map->getName($this->getProject());
            $elements = $map->getValue();

            switch ($key) {
            case 'server':
                // Build the channel configuration file pirum.xml
                foreach ($elements as $key => $value) {
                    if (in_array($key, $items)) {
                        $this->$key = $value;
                    }
                }
                foreach ($items as $key) {
                    if ($this->$key === null) {
                        throw new BuildException(
                            'PEAR Channel Server Definition is incomplete. ' .
                            'At least one of these arguments (' .
                            implode(',', $items) . ') is missing.'
                        );
                    }
                }

                $configFile = $this->destDir . '/pirum.xml';

                if (!$this->force && file_exists($configFile) ) {
                    continue;
                }

                $server = <<<EOS
<?xml version="1.0" encoding="UTF-8" ?>
<server>
  <name>{$this->name}</name>
  <summary>{$this->summary}</summary>
  <alias>{$this->alias}</alias>
  <url>{$this->url}</url>
</server>
EOS;
                $bytes = file_put_contents($configFile, $server);
                if ($bytes === false) {
                    $out = 'An error has occurred while writing the configuration file';
                } else {
                    $out = "PEAR Channel Server has been configured in '{$this->destDir}'";
                }
                $this->log($out);

                $this->runPirum('build');
                break;

            case 'releases':
                // Add or remove one or more release
                foreach ($elements as $action => $releases) {  
                    if ('add' === $action || 'remove' === $action) {
                        if (!is_array($releases)) {
                            // single transaction of same type
                            $releases = array($releases);
                        }
                        foreach($releases as $release) {
                            $this->runPirum($action, $release);
                        }
                    }
                }
                break;
            }
        }
    }

    /**
     * Handles nested generic <mapping> elements.
     *
     * @return object Mapping
     */
    public function createMapping()
    {
        $type = new Mapping();
        $type->setProject($this->project);
        $this->mappings[] = $type;
        return $type;
    }

    /**
     * Build a fresh copy of the PEAR Channel Server
     *
     * @param string $action  Either 'build', 'add' or 'remove'
     * @param string $release (optional) package file
     *
     * @return void
     * @throws BuildException
     */
    protected function runPirum($action, $release = '')
    {
        $options = array('pirum', $action, $this->destDir);

        if ($action !== 'build') {
            $options[] = $release;
        }

        $pirum = new Pirum_CLI($options);

        if ($this->quiet) {
            ob_start();
        }

        $exitCode = $pirum->run();

        if ($this->quiet) {
            ob_end_clean();
        }

        if ($exitCode > 0) {
            throw new BuildException(
                'Pirum could not build the PEAR Channel Server.'
            );
        }
        $this->log("PEAR Channel Server has been built in '{$this->destDir}'");
    }

}

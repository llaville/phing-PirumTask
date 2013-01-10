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
 * @category   Tasks
 * @package    Phing
 * @subpackage Pirum
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       https://github.com/llaville/phing-PirumTask
 */

/**
 * Tests for PirumTask
 *
 * @category   Tasks
 * @package    Phing
 * @subpackage Pirum
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       https://github.com/llaville/phing-PirumTask
 */
class PirumTaskTest extends BuildFileTest
{
    /**
     * Sets up the fixture.
     *
     * @return void
     */
    public function setUp()
    {
        $this->configureProject(PHING_TEST_BASE . '/build.xml');
    }

    /**
     * Test PEAR Channel directory is required
     *
     * @expectedException        BuildException
     * @expectedExceptionMessage You must specify a directory using 'destdir' attribute.
     * @return void
     */
    public function testRequiredDestDir()
    {
        $this->executeTarget(__FUNCTION__);
    }

    /**
     * Test invalid PEAR Channel directory
     *
     * @expectedException        BuildException
     * @expectedExceptionMessage You must specify a valid directory.
     * @return void
     */
    public function testInvalidDestDir()
    {
        $this->executeTarget(__FUNCTION__);
    }

    /**
     * Test invalid PEAR Channel definition
     *
     * @expectedException        BuildException
     * @expectedExceptionMessage PEAR Channel Server Definition is incomplete. At least one of these arguments (name,summary,alias,url) is missing.
     *
     * @return void
     */
    public function testIncompleteChannelDefinition()
    {
        $this->executeTarget(__FUNCTION__);
    }

    /**
     * Test setting up a new channel
     *
     * @return void
     */
    public function testBuildChannel()
    {
        $pirumXml = PHING_TEST_BASE . '/etc/channels/phingofficial/pirum.xml';
        // rather than clean-up all channel directory, just removed the channel definition file
        if (file_exists($pirumXml)) {
            $isDeleted = unlink($pirumXml);
            if (!$isDeleted) {
                $this->fail('Could not be deleted pirum.xml file');
            }
        }
        $this->executeTarget(__FUNCTION__);
        $this->assertFileExists($pirumXml);
    }

    /**
     * Test building server files
     *
     * @return void
     */
    public function testBuildServer()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('PEAR server files was updated');
    }

    /**
     * Test adding a new release
     *
     * @return void
     */
    public function testAddRelease()
    {
        $channelBasedir = $this->getProject()->getUserProperty('phing.dir')
            . '/etc/channels/bartlett';

        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("PEAR Channel Server has been built in '$channelBasedir'");
        $this->assertFileExists("$channelBasedir/get/Pirus-2.0.0.tgz");
    }

    /**
     * Test removing an old release
     *
     * @return void
     */
    public function testRemoveRelease()
    {
        $channelBasedir = $this->getProject()->getUserProperty('phing.dir')
            . '/etc/channels/bartlett';

        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("PEAR Channel Server has been built in '$channelBasedir'");
        $this->assertFileNotExists("$channelBasedir/get/Pirus-2.0.0.tgz");
    }

    /**
     * Test multiple transactions process at same time
     *
     * @return void
     */
    public function testMultipleTransactions()
    {
        $channelBasedir = $this->getProject()->getUserProperty('phing.dir')
            . '/etc/channels/bartlett';

        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs("PEAR Channel Server has been built in '$channelBasedir'");
        $this->assertFileExists("$channelBasedir/get/Pirus-1.0.0.tgz");
        $this->assertFileExists("$channelBasedir/get/Pirus-2.0.0.tgz");
    }

}

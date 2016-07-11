<?php

namespace Keboola\SSH\Keygen\Tests;

use Keboola\SSH\Keygen\Exception\UserException;

class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    public function testUserError()
    {
        $lastOutput = exec('php ./src/run.php --data=./tests/data/invalid 2>&1', $output, $returnCode);

        $this->assertEquals(1, $returnCode);
        $this->assertEquals(UserException::ERR_MISSING_CONFIG, $lastOutput);

        unset($output);
        unset($lastOutput);
        unset($returnCode);

        $lastOutput = exec('php ./src/run.php 2>&1', $output, $returnCode);

        $this->assertEquals(1, $returnCode);
        $this->assertEquals(UserException::ERR_DATA_PARAM, $lastOutput);
    }

    public function testRunAction()
    {
        $lastOutput = exec('php ./src/run.php --data=./tests/data/runAction 2>&1', $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertEmpty($lastOutput);
        $this->assertCount(0, $output);
    }

    public function testGenerateAction()
    {
        // first generation
        $lastOutput = exec('php ./src/run.php --data=./tests/data/generateAction 2>&1', $output, $returnCode);

        $this->assertEquals(0, $returnCode);

        $data = json_decode($lastOutput, true);

        $this->assertTrue(is_array($data));

        $this->assertArrayHasKey('private', $data);
        $this->assertNotEmpty($data['private']);

        $this->assertArrayHasKey('public', $data);
        $this->assertNotEmpty($data['public']);

        $privateKey = $data['private'];
        $publicKey = $data['public'];

        unset($output);
        unset($lastOutput);
        unset($returnCode);

        // second generation
        $lastOutput = exec('php ./src/run.php --data=./tests/data/generateAction 2>&1', $output, $returnCode);

        $this->assertEquals(0, $returnCode);

        $data = json_decode($lastOutput, true);

        $this->assertTrue(is_array($data));

        $this->assertArrayHasKey('private', $data);
        $this->assertNotEmpty($data['private']);

        $this->assertArrayHasKey('public', $data);
        $this->assertNotEmpty($data['public']);

        $this->assertNotEquals($privateKey, $data['private']);
        $this->assertNotEquals($publicKey, $data['public']);
    }
}

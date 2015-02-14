<?php
namespace tests\unit\writer;

/**
 * File log writer class tests
 *
 * @category logger
 * @package  unit
 */
class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Path to the test log files
     */
    const PATH = '/tmp/logger_tests';

    /**
     * Tests write
     *
     * @return void
     */
    public function testWrite()
    {
        $this->cleanUp();

        $writer = new \logger\writer\File();
        // no path
        $this->assertFalse($writer->write('test', 'File writer test', \Psr\Log\LogLevel::INFO));
        // with path
        $writer->config(['path' => self::PATH]);
        $this->assertTrue($writer->write('test', 'File writer test', \Psr\Log\LogLevel::INFO));

        $file = sprintf('%s/%s/%s.log', self::PATH, 'test', date('Y-m-d'));
        $this->assertFileExists($file);
        $this->assertStringMatchesFormat(
            "no_id-%d-%d-%dT%d:%d:%d+0000-[INFO]-File writer test\n",
            file_get_contents($file)
        );

        $this->cleanUp();
    }

    /**
     * Tests config
     *
     * @return void
     */
    public function testConfig()
    {
        $data = [
            'test1' => 'test value 1',
            'test2' => 'test value 2',
        ];
        $writer = new \logger\writer\Syslog();
        $this->assertEquals([], $writer->config());
        $this->assertEquals($data, $writer->config($data));
        $this->assertEquals($data, \PHPUnit_Framework_Assert::readAttribute($writer, 'config'));
    }

    /**
     * Tests message
     *
     * @return void
     */
    public function testMessage()
    {
        // expose private function
        $class = new \ReflectionClass('\logger\writer\File');

        $property = $class->getProperty('datetime');
        $property->setAccessible(true);

        $method = $class->getMethod('message');
        $method->setAccessible(true);
        $file = new \logger\writer\File();
        $property->setValue($file, new \DateTime('2014-07-15 17:16:23', new \DateTimeZone('UTC')));

        // without context
        $this->assertEquals(
            "no_id-2014-07-15T17:16:23+0000-[LEVEL]-test\n",
            $method->invokeArgs($file, ['test', 'level', []])
        );

        // with context and without placeholders
        $this->assertEquals(
            "id-2014-07-15T17:16:23+0000-[LEVEL]-test\n",
            $method->invokeArgs($file, ['test', 'level', ['reqid' => 'id', 'msisdn' => 123123, 'operator' => 10]])
        );

        // with context and placeholders
        $this->assertEquals(
            "id-2014-07-15T17:16:23+0000-[LEVEL]-test 1 {dva}\n",
            $method->invokeArgs($file, ['test {ena} {dva}', 'level', [
                'reqid'    => 'id',
                'msisdn'   => 123123,
                'operator' => 10,
                'ena'      => 1,
                'tri'      => 3
            ]])
        );

        // stack trace
        $trace = "Multi line 1" . PHP_EOL
                . "line 2" . PHP_EOL
                . "line 3";

        $this->assertEquals(
            "id-2014-07-15T17:16:23+0000-[LEVEL]-test\nMulti line 1\nline 2\nline 3\n",
            $method->invokeArgs($file, ['test', 'level', [
                'reqid'       => 'id',
                'msisdn'      => 1231,
                'operator'    => 10,
                'stack_trace' => $trace,
                'ena'         => 1,
                'tri'         => 3
            ]])
        );
    }

    /**
     * Remove dir and any content
     *
     * @param  void|string $type
     * @return void
     */
    private function cleanUp($type = 'test')
    {
        $dirPath = self::PATH . '/' . $type . '/';
        if (is_dir($dirPath)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $path) {
                $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
            }
            rmdir($dirPath);
        }
    }
}

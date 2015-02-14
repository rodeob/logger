<?php
namespace tests\unit\writer;

/**
 * Syslog log writer class tests
 *
 * @category logger
 * @package  unit
 */
class SyslogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests write
     *
     * @return void
     */
    public function testWrite()
    {
        $writer = new \logger\writer\Syslog();
        $this->assertTrue($writer->write('test', 'Syslog writer test', \Psr\Log\LogLevel::INFO));
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
        $class = new \ReflectionClass('\logger\writer\Syslog');
        $method = $class->getMethod('message');
        $method->setAccessible(true);
        $syslog = new \logger\writer\Syslog();

        // without context
        $this->assertEquals(
            '{"reqid":null,"stack_trace":null,"message":"test"}',
            $method->invokeArgs($syslog, ['test', []])
        );

        // with context and without placeholders
        $this->assertEquals(
            '{"reqid":"id","stack_trace":null,"message":"test"}',
            $method->invokeArgs($syslog, ['test', ['reqid' => 'id', 'msisdn' => 123123, 'operator' => 10]])
        );

        // with context and placeholders
        $this->assertEquals(
            '{"reqid":"id","stack_trace":null,"message":"test 1 {dva}"}',
            $method->invokeArgs($syslog, ['test {ena} {dva}', [
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
            '{"reqid":"id","stack_trace":"Multi line 1\nline 2\nline 3","message":"test"}',
            $method->invokeArgs($syslog, ['test', [
                'reqid'       => 'id',
                'msisdn'      => 1231,
                'operator'    => 10,
                'stack_trace' => $trace,
                'ena'         => 1,
                'tri'         => 3
            ]])
        );
    }
}

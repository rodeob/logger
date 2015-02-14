<?php
namespace tests\unit;

use \tests\helpers\WriterMock;

/**
 * Log class tests
 *
 * @category logger
 * @package  unit
 */
class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests creating of the logger and options
     *
     * @return void
     */
    public function testCreate()
    {
        // default options
        $log = new \logger\Log('test');

        $this->assertEquals('test', \PHPUnit_Framework_Assert::readAttribute($log, 'component'));
        // id should be strin gand the right length
        $this->assertInternalType('string', \PHPUnit_Framework_Assert::readAttribute($log, 'id'));
        $this->assertEquals(
            \logger\Log::ID_LENGTH,
            strlen(\PHPUnit_Framework_Assert::readAttribute($log, 'id'))
        );
        // config should not be changed
        $this->assertEquals([
            'log_levels' => [\logger\Log::OFF],
        ], \PHPUnit_Framework_Assert::readAttribute($log, 'config'));
        // default writer should be Syslog writer
        $this->assertInstanceOf(
            '\logger\writer\Syslog',
            \PHPUnit_Framework_Assert::readAttribute($log, 'writer')
        );

        // id pushed in
        $log = new \logger\Log('test', 'new_id');
        $this->assertEquals('new_id', \PHPUnit_Framework_Assert::readAttribute($log, 'id'));

        // config pushed in
        $log = new \logger\Log('test', 'new_id', ['test' => 'value']);
        $this->assertEquals([
            'log_levels' => [\logger\Log::OFF],
            'test' => 'value',
        ], \PHPUnit_Framework_Assert::readAttribute($log, 'config'));

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::ALERT]]);
        $this->assertEquals([
            'log_levels' => [\Psr\Log\LogLevel::ALERT],
        ], \PHPUnit_Framework_Assert::readAttribute($log, 'config'));

        // writer pushed in
        $log = new \logger\Log('test', 'new_id', [], new WriterMock());
        $this->assertInstanceOf(
            '\tests\helpers\WriterMock',
            \PHPUnit_Framework_Assert::readAttribute($log, 'writer')
        );
    }

    /**
     * Tests log method
     *
     * @return void
     */
    public function testLog()
    {
        $writer = new WriterMock();

        // simple info message
        $log = new \logger\Log('test', 'new_id', [], $writer);
        $log->log(\Psr\Log\LogLevel::INFO, 'Testing info');
        // by default all logging is off,
        $this->assertEquals([], $writer->values);

        // unsupported log level
        $log->log('something', 'Testing info');
        // by default all logging is off,
        $this->assertEquals([], $writer->values);

        // enable all levels and write message
        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\logger\Log::ALL]], $writer);
        $log->log(\Psr\Log\LogLevel::INFO, 'Testing info');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing info',
            'level'     => \Psr\Log\LogLevel::INFO,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);

        $log->log(\Psr\Log\LogLevel::WARNING, 'Testing warning {placeholder}', ['placeholder' => 'with context']);
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing warning {placeholder}',
            'level'     => \Psr\Log\LogLevel::WARNING,
            'context'   => [
                'reqid' => 'new_id',
                'placeholder' => 'with context',
            ],
        ], $writer->values);

        // test stack trace if exception is in context
        $log->log(\Psr\Log\LogLevel::ERROR, 'Testing error', [
            'placeholder' => 'with context',
            'exception'   => new \Exception('error'),
        ]);

        $this->assertEquals('test', $writer->values['component']);
        $this->assertEquals('Testing error', $writer->values['message']);
        $this->assertEquals(\Psr\Log\LogLevel::ERROR, $writer->values['level']);
        $this->assertEquals('new_id', $writer->values['context']['reqid']);
        // trace
        $this->assertRegExp('/\#\d{1,}\s?.*:\s?.*/', $writer->values['context']['stack_trace']);

        // debug is special, it includes stack trace
        $log->log(\Psr\Log\LogLevel::DEBUG, 'Testing debug');
        $this->assertEquals('test', $writer->values['component']);
        $this->assertEquals('Testing debug', $writer->values['message']);
        $this->assertEquals(\Psr\Log\LogLevel::DEBUG, $writer->values['level']);
        $this->assertEquals('new_id', $writer->values['context']['reqid']);
        // trace
        $this->assertRegExp('/\#\d{1,}\s?.*:\s?.*/', $writer->values['context']['stack_trace']);
    }

    /**
     * Tests emergency method
     *
     * @return void
     */
    public function testEmergency()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::ALERT]], $writer);
        $log->emergency('Testing emergency');
        // emergency logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::EMERGENCY]], $writer);
        $log->emergency('Testing emergency');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing emergency',
            'level'     => \Psr\Log\LogLevel::EMERGENCY,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests alert method
     *
     * @return void
     */
    public function testAlert()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::EMERGENCY]], $writer);
        $log->alert('Testing alert');
        // alert logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::ALERT]], $writer);
        $log->alert('Testing alert');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing alert',
            'level'     => \Psr\Log\LogLevel::ALERT,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests critical method
     *
     * @return void
     */
    public function testCritical()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::EMERGENCY]], $writer);
        $log->critical('Testing critical');
        // critical logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::CRITICAL]], $writer);
        $log->critical('Testing critical');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing critical',
            'level'     => \Psr\Log\LogLevel::CRITICAL,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests error method
     *
     * @return void
     */
    public function testError()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::EMERGENCY]], $writer);
        $log->error('Testing error');
        // error logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::ERROR]], $writer);
        $log->error('Testing error');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing error',
            'level'     => \Psr\Log\LogLevel::ERROR,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests warning method
     *
     * @return void
     */
    public function testWarning()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::EMERGENCY]], $writer);
        $log->warning('Testing warning');
        // warning logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::WARNING]], $writer);
        $log->warning('Testing warning');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing warning',
            'level'     => \Psr\Log\LogLevel::WARNING,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests notice method
     *
     * @return void
     */
    public function testNotice()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::EMERGENCY]], $writer);
        $log->notice('Testing notice');
        // notice logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::NOTICE]], $writer);
        $log->notice('Testing notice');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing notice',
            'level'     => \Psr\Log\LogLevel::NOTICE,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests info method
     *
     * @return void
     */
    public function testInfo()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::ALERT]], $writer);
        $log->info('Testing info');
        // info logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::INFO]], $writer);
        $log->info('Testing info');
        $this->assertEquals([
            'component' => 'test',
            'message'   => 'Testing info',
            'level'     => \Psr\Log\LogLevel::INFO,
            'context'   => [
                'reqid' => 'new_id',
            ],
        ], $writer->values);
    }

    /**
     * Tests debug method
     *
     * @return void
     */
    public function testDebug()
    {
        $writer = new WriterMock();

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::ALERT]], $writer);
        $log->debug('Testing debug');
        // debug logging is off,
        $this->assertEquals([], $writer->values);

        $log = new \logger\Log('test', 'new_id', ['log_levels' => [\Psr\Log\LogLevel::DEBUG]], $writer);
        $log->debug('Testing debug');
        $this->assertEquals('test', $writer->values['component']);
        $this->assertEquals('Testing debug', $writer->values['message']);
        $this->assertEquals(\Psr\Log\LogLevel::DEBUG, $writer->values['level']);
        $this->assertEquals('new_id', $writer->values['context']['reqid']);
        // trace
        $this->assertRegExp('/\#\d{1,}\s?.*:\s?.*/', $writer->values['context']['stack_trace']);
    }
}

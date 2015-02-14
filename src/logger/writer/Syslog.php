<?php

/**
 * Syslog logger writer
 *
 * @category writer
 * @package  logger
 */
namespace logger\writer;

class Syslog extends WriterAbstract
{
    /**
     * Maps psr log levels to syslog log levels
     *
     * @var array
     */
    private $levels = [
        \Psr\Log\LogLevel::EMERGENCY => LOG_EMERG,
        \Psr\Log\LogLevel::ALERT     => LOG_ALERT,
        \Psr\Log\LogLevel::CRITICAL  => LOG_CRIT,
        \Psr\Log\LogLevel::ERROR     => LOG_ERR,
        \Psr\Log\LogLevel::WARNING   => LOG_WARNING,
        \Psr\Log\LogLevel::NOTICE    => LOG_NOTICE,
        \Psr\Log\LogLevel::INFO      => LOG_INFO,
        \Psr\Log\LogLevel::DEBUG     => LOG_DEBUG,
    ];

    /**
     * Holds key for key/value pairs in message
     *
     * @var type
     */
    private $keys = [
        'reqid',
        'stack_trace',
    ];

    /**
     * Writes data to log
     *
     * @param  string      $component  who writes to log
     * @param  string      $message    message to log
     * @param  string      $level      psr-3 log level
     * @param  void|array  $context    array with aditional data
     * @return boolean
     */
    public function write($component, $message, $level, array $context = [])
    {
        $result = false;
        // open lazy connection to syslog
        if (openlog($component, LOG_ODELAY, LOG_USER)) {
            $result = syslog($this->levels[$level], $this->message($message, $context));
            closelog();
        }
        return $result;
    }

    /**
     * Formats message
     *
     * @param  string $message
     * @param  array  $context
     * @return string
     */
    private function message($message, array $context)
    {
        $msg = [];
        // build key/value pairs
        foreach ($this->keys as $key) {
            $msg[$key] = isset($context[$key]) ? $context[$key] : null;
        }

        // add message,replace placeholders,
        $msg['message'] = $this->interpolate($message, $context);

        return json_encode($msg);
    }
}

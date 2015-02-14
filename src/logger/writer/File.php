<?php

/**
 * File logger writer
 *
 * @category writer
 * @package  logger
 */
namespace logger\writer;

class File extends WriterAbstract
{
    /**
     * Holds current time
     *
     * @var null
     */
    protected $datetime = null;

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
        // do we got path in context
        if (!isset($this->config['path'])) {
            return false;
        }

        $this->datetime = new \DateTime(null, new \DateTimeZone('UTC'));

        $path = sprintf('%s/%s/', rtrim($this->config['path'], '/'), $component);
        $file = $path . $this->datetime->format('Y-m-d') . '.log';

        if (!$this->createPath($path)) {
            return false;
        }

        // save file with its content
        return (
            false === @file_put_contents($file, $this->message($message, $level, $context), LOCK_EX | FILE_APPEND)
        ) ? false : true;
    }

    /**
     * Formats message
     *
     * @param  string $message
     * @param  string $message
     * @param  array  $context
     * @return string
     */
    private function message($message, $level, array $context)
    {
        $message = sprintf(
            '%s-%s-[%s]-%s%s',
            isset($context['reqid']) ? $context['reqid'] : 'no_id',
            $this->datetime->format(\DateTime::ISO8601),
            strtoupper($level),
            $this->interpolate($message, $context),
            PHP_EOL
        );

        $message .= (isset($context['stack_trace'])) ? $context['stack_trace'] . PHP_EOL : '';

        return $message;
    }

    /**
     * Create directory structure if it does not exist
     *
     * @param  string  $path
     * @return boolean
     */
    private function createPath($path)
    {
        if (false === is_dir($path)) {
            if (false === mkdir($path, 0755, true)) {
                return false;
            }
        }
        return true;
    }
}

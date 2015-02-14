<?php

/**
 * Logger writer interface
 *
 * @category writer
 * @package  logger
 */
namespace logger\writer;

interface WriterInterface
{
    /**
     * Setter/getter for config options
     *
     * @param  void|array  $config
     * @return array
     */
    public function config(array $config = []);

    /**
     * Writes data to log
     *
     * @param  string      $component  who writes to log
     * @param  string      $message    message to log
     * @param  string      $level      psr-3 log level
     * @param  void|array  $context    array with aditional data
     * @return boolean
     */
    public function write($component, $message, $level, array $context = []);
}

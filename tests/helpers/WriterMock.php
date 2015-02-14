<?php

namespace tests\helpers;

/**
 * Mock logger writer
 *
 * @category logger
 * @package  unit
 */
class WriterMock extends \logger\writer\WriterAbstract
{
    /**
     * Holds inserted values
     *
     * @var array
     */
    public $values = [];

    /**
     * Writes data to log
     *
     * @param  string      $component  who writes to log
     * @param  string      $message    message to log
     * @param  string      $level      psr-3 log level
     * @param  void|array  $context    array with aditional data
     * @return boolean
     */
    public function write($component, $message, $level, array $context = array())
    {
        // just put everything in the property to check them later
        $this->values = [
            'component' => $component,
            'message'   => $message,
            'level'     => $level,
            'context'   => $context,
        ];

        return true;
    }
}

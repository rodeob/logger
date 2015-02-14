<?php
/**
 * Logger writer abstract class.
 *
 * @category writer
 * @package  logger
 */
namespace logger\writer;

abstract class WriterAbstract implements WriterInterface
{
    /**
     * Writer config options
     *
     * @var array
     */
    protected $config = [];

    /**
     * Setter/getter for config options
     *
     * @param  void|array  $config
     * @return array
     */
    public function config(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        return $this->config;
    }

    /**
     * Replace placeholders in message with values from context
     *
     * @param  string  $message
     * @param  void|array  $context
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}

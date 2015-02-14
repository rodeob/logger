<?php

/**
 * Logger.
 *
 * @category logger
 * @package  logger
 */
namespace logger;

class Log extends \Psr\Log\AbstractLogger
{
    /**
     * Log all
     *
     * @var string
     */
    const ALL = 'all';

    /**
     * Logging off
     *
     * @var string
     */
    const OFF = 'off';

    /**
     * Length of the id
     *
     * @var integer
     */
    const ID_LENGTH = 32;

    /**
     * Which component is logging
     *
     * @var string
     */
    protected $component;

    /**
     * Holds current id
     *
     * @var string
     */
    protected $id = '';

    /**
     * Holds log writer
     *
     * @var \logger\writer\WriterInterface
     */
    protected $writer;

    /**
     * Logger default config options
     *
     * log_levels - which log levels we want to write in the log
     * If you include any one of this two, logger ignores any other option.
     * If both are included, OFF has higher priority.
     * \logger\Log::OFF
     * \logger\Log::ALL
     * or select combination of log levels as specified in \Psr\Log\LogLevel.
     *
     * This will log EMERGENCY, ALERT and CRITICAL levels.
     * 'log_levels' => [\Psr\Log\LogLevel::EMERGENCY, \Psr\Log\LogLevel::ALERT, \Psr\Log\LogLevel::CRITICAL]
     *
     * @var array
     */
    protected $config = [
        'log_levels' => [self::OFF], // by default do not log anything
    ];

    /**
     * Constructor
     *
     * @param  string      $component
     * @param  void|string $id
     * @param  void|array  $config
     * @param  void|\logger\writer\WriterInterface $writer
     * @return void
     */
    public function __construct($component, $id = false, array $config = [], $writer = null)
    {
        $this->component = $component;
        if (!$id) {
            $id = $this->randomId();
        }
        $this->id = $id;

        // merge config options
        $this->config = array_merge($this->config, $config);

        // set writer (default is syslog writer)
        $this->writer = isset($writer) ? $writer : new \logger\writer\Syslog();
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  void|array  $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        // check if level is valid and if logging level is enabled
        if (!defined('\Psr\Log\LogLevel::' . strtoupper($level)) || !$this->isEnabled($level)) {
            return;
        }

        // add trace to message if is exception set
        if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
            $context['stack_trace'] = $context['exception']->getTraceAsString() . PHP_EOL;
        }

        // add debug info to message if log level debug
        // check if exception is not set, to prevent double backtrace output
        if ($level === \Psr\Log\LogLevel::DEBUG && !isset($context['exception'])) {
            $context['stack_trace'] = $this->debugStringBacktrace();
        }
        // add id to the context
        $context['reqid'] = $this->id;

        // push config to the writer
        $this->writer->config($this->config);
        // write to log
        $this->writer->write($this->component, $message, $level, $context);
    }

    /**
     * Is log level enabled?
     *
     * @param  string  $level
     * @return boolean
     */
    protected function isEnabled($level)
    {
        // logging off
        if (!isset($this->config['log_levels']) || in_array(self::OFF, $this->config['log_levels'])) {
            return false;
        }

        // log all or
        // check for specific log level
        return in_array(self::ALL, $this->config['log_levels']) || in_array($level, $this->config['log_levels']);
    }

    /**
     * Return backtrace in a string
     *
     * @return string
     */
    protected function debugStringBacktrace()
    {
        $trace = '';
        $count = 0;
        // format backtrace
        foreach (debug_backtrace() as $item) {
            // ignore current file form backtrace
            if (isset($item['file']) && $item['file'] === __FILE__) {
                continue;
            }

            // prepare args
            $args = '';

            // check args
            if (!isset($item['args'])) {
                $item['args'] = array();
            }

            // format arguments, if there is any
            if (0 < count($item['args'])) {
                foreach ($item['args'] as $a) {
                    switch (true) {
                        case is_array($a):
                            $args .= 'Array';
                            break;
                        case is_object($a):
                            $args .= get_class($a);
                            break;
                        case is_bool($a):
                            $args .= ( true === $a ? 'true' : 'false');
                            break;
                        case is_numeric($a):
                            $args .= $a;
                            break;
                        case is_null($a):
                            $args .= 'NULL';
                            break;
                        case is_string($a):
                            $args .= "'" . $a . "'";
                            break;

                        default:
                            $args .= gettype($a);
                            break;
                    }
                    $args .= ',';
                }
            }

            $trace .= sprintf(
                '#%d %s(%s): %s%s(%s)%s',
                $count,
                (isset($item['file']) ? $item['file'] : ''),
                (isset($item['line']) ? $item['line'] : ''),
                (isset($item['class']) ? $item['class'] . $item['type'] : ''),
                (isset($item['function']) ? $item['function'] : '' ),
                trim($args, ','),
                PHP_EOL
            );

            $count++;
        }

        return $trace;
    }

    /**
     * Generate random id.
     *
     * @return string
     */
    protected function randomId()
    {
        $charset = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $string  = '';
        $count   = strlen($charset) - 1;
        $length  = self::ID_LENGTH;

        while ($length--) {
            $string .= $charset[mt_rand(0, $count)];
        }

        return $string;
    }
}

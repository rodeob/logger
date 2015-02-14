# Logger for request based PHP applications 
## PSR-3 compatible

Curently this logger has two writers:

 * File writer - write data to file
 * Syslog writer - write data to system syslog

## Usage

To create new logger is as simple as instantiating it:

```php
$Log = new /logger/Log('test');
```

And then call one of the PSR-3 log methods.

```php
$Log->alert('Message with {placeholder}, ['placeholder' => 'context']);
```

If is selected File writer, this will create new file with line: 
```
request_id-2015-02-14T12:13:14-[ALERT]-Message with context
```


### Options

```php
$Log = new /logger/Log(
	'test',                                      // componenet
    'request_id',                                // request id
    ['log_levels' => [\Psr\Log\LogLevel::ALERT], // configuration
     new logger\writer\File()                    // which writer to use
);
```

#### Component

Which part of code is writeing to log.

#### Request id

Unique id for the request. If it is not defined (false) it is generated. In File and Syslog writer it is used as prefix in every line.

#### Configuration

Array with config options.

##### log_levels

Which log levels we want to write in the log. If you include any one of this two, logger ignores any other option. If both are included, OFF has higher priority.
 
 * \logger\Log::OFF
 * \logger\Log::ALL

Or select combination of log levels as specified in *\Psr\Log\LogLevel*. This will log EMERGENCY, ALERT and CRITICAL levels.

```php
'log_levels' => [
    \Psr\Log\LogLevel::EMERGENCY,
    \Psr\Log\LogLevel::ALERT,
    \Psr\Log\LogLevel::CRITICAL
]
```

##### path

Used in File writer to select where to write log files.

```php
'path' => '/var/log/myproject'
```

<?php

namespace Kevin\Payment\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Handler
 * @package Kevin\Payment\Logger
 */
class Handler extends Base
{
    /**
     * @var
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string 
     */
    protected $fileName = '/var/log/kevin.log';
}
<?php
namespace Concrete\Core\Error\Provider;

use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Error\Handling\ErrorHandler;
use Concrete\Core\Logging\Channels;
use Concrete\Core\Logging\LoggerFactory;

class ErrorHandlingServiceProvider extends Provider
{
    public function register()
    {
        $logger = $this->app->make(LoggerFactory::class)->createLogger(Channels::CHANNEL_EXCEPTIONS);
        $handler = $this->app->make(ErrorHandler::class, ['logger' => $logger]);
        $handler = ErrorHandler::register($handler);
        $handler->setExceptionHandler([$handler, 'renderConcreteException']);
    }
}

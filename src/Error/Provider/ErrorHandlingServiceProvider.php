<?php
namespace Concrete\Core\Error\Provider;

use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Error\Handling\ErrorHandler;
use Concrete\Core\Logging\Channels;
use Concrete\Core\Logging\LoggerFactory;
use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;

class ErrorHandlingServiceProvider extends Provider
{
    public function register()
    {
        $logger = $this->app->make(LoggerFactory::class)->createLogger(Channels::CHANNEL_EXCEPTIONS);
        $config = $this->app->make('config');
        $handler = new ErrorHandler($logger, $config);
        $handler = ErrorHandler::register($handler);
        $handler->setExceptionHandler([$handler, 'renderConcreteException']);
    }
}

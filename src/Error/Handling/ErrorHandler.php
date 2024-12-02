<?php

namespace Concrete\Core\Error\Handling;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\Handling\ErrorRenderer\ConcreteErrorRenderer;
use Concrete\Core\Logging\Levels;
use Concrete\Core\Permission\Checker;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;
use Symfony\Component\ErrorHandler\ErrorRenderer\CliErrorRenderer;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

class ErrorHandler extends SymfonyErrorHandler
{

    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * @var Repository
     */
    protected $config;

    private function fillErrorLevelsFromConfig(array $errors, array $errorConfiguration, string $key): array
    {
        $levels = [];
        foreach ($errors as $errorCode) {
            $levels[$errorCode] = constant(LogLevel::class . '::' . $errorConfiguration[$key]['logLevel']);
        }
        return $levels;
    }

    public function __construct(?LoggerInterface $logger = null, Repository $config)
    {
        $this->config = $config;
        $errorConfiguration = $config->get('concrete.error.handling');
        $thrownErrors = E_ALL;
        if (!($errorConfiguration['warning']['halt'] ?? false)) {
            $thrownErrors = $thrownErrors & ~E_WARNING & ~E_CORE_WARNING & ~E_USER_WARNING;
        }
        if (!($errorConfiguration['notice']['halt'] ?? false)) {
            $thrownErrors = $thrownErrors & ~E_NOTICE & ~E_USER_NOTICE;
        }
        // Note: "deprecated" is not checked for thrown errors here because symfony will not
        // let deprecated errors trigger full exceptions

        $levels = [];
        if (!empty($errorConfiguration['error']['logLevel'])) {
            $levels += $this->fillErrorLevelsFromConfig([
                E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR, E_RECOVERABLE_ERROR, E_USER_ERROR
            ], $errorConfiguration, 'error');
        }
        if (!empty($errorConfiguration['warning']['logLevel'])) {
            $levels += $this->fillErrorLevelsFromConfig([
                E_WARNING, E_USER_WARNING, E_COMPILE_WARNING, E_CORE_WARNING
            ], $errorConfiguration, 'warning');
        }
        if (!empty($errorConfiguration['notice']['logLevel'])) {
            $levels += $this->fillErrorLevelsFromConfig([E_NOTICE, E_USER_NOTICE], $errorConfiguration, 'notice');
        }
        if (!empty($errorConfiguration['deprecated']['logLevel'])) {
            $levels += $this->fillErrorLevelsFromConfig([E_DEPRECATED, E_USER_DEPRECATED], $errorConfiguration, 'deprecated');
        }

        parent::__construct(null,true);
        $this->setDefaultLogger($logger,  $levels);
        $this->throwAt($thrownErrors, true);
    }

    /**
     * Mostly ported from SymfonyErrorHandler::renderException, but that method cannot be overridden, and the classes
     * that do the rendering are hard-coded within the method, so we need to swap out the entire method in order to
     * override the Cli and Html error renderers.
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function renderConcreteException(\Throwable $exception): void
    {
        $renderer = \in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? new CliErrorRenderer() : new ConcreteErrorRenderer($this->config, new Checker());

        $exception = $renderer->render($exception);

        if (!headers_sent()) {
            http_response_code($exception->getStatusCode());

            foreach ($exception->getHeaders() as $name => $value) {
                header($name.': '.$value, false);
            }
        }

        echo $exception->getAsString();
    }

}
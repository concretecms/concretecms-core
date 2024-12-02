<?php

namespace Concrete\Core\Error\Handling\ErrorRenderer;

use Concrete\Core\Application\Service\UserInterface;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Permission\Checker;
use Concrete\Core\View\ErrorView;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ConcreteErrorRenderer implements ErrorRendererInterface
{

    /**
     * @var Repository 
     */
    protected $config;

    /**
     * @var Checker
     */
    protected $checker;

    public function __construct(Repository $config, Checker $checker)
    {
        $this->config = $config;
        $this->checker = $checker;
    }

    public function render(\Throwable $exception): FlattenException
    {
        $finalException = FlattenException::createFromThrowable($exception);
        $setting = $this->config->get('concrete.error.display.guests');
        if ($this->checker->canViewDebugErrorInformation()) {
            $setting = $this->config->get('concrete.error.display.privileged');
        }
        if ($setting === 'debug') {
            // Let's load the symfony debug html renderer, but then use Dom/Xpath to remove symfony-specific
            // things that we can't control. Ideally we'd have a lot more control here but we don't so let's
            // hack away the things that we can.
            $htmlErrorRenderer = new HtmlErrorRenderer(true);
            $originalHtml = $htmlErrorRenderer->render($exception)->getAsString();
            $document = new \DOMDocument();
            $document->loadHTML($originalHtml);
            $xpath = new \DOMXPath($document);
            // Remove symfony header
            foreach ($xpath->query('//header') as $header) {
                $header->parentNode->removeChild($header);
            }
            // Remove symfony illustration
            foreach ($xpath->query('//*[contains(attribute::class, "exception-illustration")]') as $illustration) {
                $illustration->parentNode->removeChild($illustration);
            }
            return $finalException->setAsString($document->saveHTML());
        } else {
            $o = new \stdClass;
            $o->title = t('An unexpected error occurred.');
            if ($setting === 'message') {
                $o->content = h($exception->getMessage());
            } else {
                $o->content = t('An error occurred while processing this request.');
            }
            $ve = new ErrorView($o);
            return $finalException->setAsString($ve->render());
        }
    }
}
<?php
/**
 * Error Handler Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2016 Bowling Green State University Libraries
 * @license MIT
 * @package Supplies
 */

namespace App\Handler;

use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

/**
 * Handles general errors and exceptions from the application.
 */
class ErrorHandler extends \Slim\Handlers\Error
{
    /**
     * PSR-3 logger.
     * @var LoggerInterface
     */
    private $logger;

    /**
     * View renderer.
     * @var Twig
     */
    private $view;

    /**
     * Whether the application is in debug mode.
     * @var boolean
     */
    private $debug;

    /**
     * Construct the action with objects and configuration.
     * @param LoggerInterface $logger PSR-3 logger.
     * @param Twig $view View renderer.
     * @param boolean $debug Whether the application is in debug mode.
     */
    public function __construct(LoggerInterface $logger, Twig $view, $debug)
    {
        $this->logger = $logger;
        $this->view = $view;
        $this->debug = $debug;
    }

    /**
     * Renders the given exception as HTML.
     * @param \Exception $exception The exception to render.
     * @return string The exception rendered as HTML.
     */
    protected function renderHtmlErrorMessage(\Exception $exception)
    {
        // Pass no arguments to template by default.
        $args = [];

        // If the error was because something was not found,
        // set the title and message to specify as such.
        if ($exception instanceof \App\Exception\NotFoundException) {
            $args['title'] = 'Not Found';
            $args['message'] = 'The requested resource could not be found.';
        }

        // If the application is in debug mode, render details
        // about the exception as the error message.
        if ($this->debug) {
            $args['message'] = $this->renderHtmlException($exception);

            // Add any previous exceptions.
            while ($exception = $exception->getPrevious()) {
                $args['message'] .= '<h2>Previous exception</h2>';
                $args['message'] .= $this->renderHtmlException($exception);
            }
        }

        // Return the error message template view.
        return $this->view->fetch('error.html.twig', $args);
    }

    /**
     * Writes a throwable object to the error log.
     * @param throwable $throwable The throwable object to write.
     */
    protected function writeToErrorLog($throwable)
    {
        // Write the throwable as an exception to the PSR-3 logger.
        $this->logger->error('Exception', ['exception' => $throwable]);
    }
}

<?php
/**
 * Method Not Allowed Handler Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2016 Bowling Green State University Libraries
 * @license MIT
 * @package Supplies
 */

namespace App\Handler;

use Slim\Views\Twig;

/**
 * Handles method not allowed errors for the application.
 */
class NotAllowedHandler extends \Slim\Handlers\NotAllowed
{
    /**
     * View renderer.
     * @var Twig
     */
    private $view;

    /**
     * Construct the action with an object.
     * @param Twig $view View renderer.
     */
    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Renders an error message as HTML that the method was not allowed.
     * @param string[] $methods The names of the methods that are allowed.
     * @return string An HTML error message that the method was not allowed.
     */
    protected function renderHtmlNotAllowedMessage($methods)
    {
        // Return the error message template view with which methods to use.
        return $this->view->fetch('error.html.twig', [
            'title' => 'Method Not Allowed',
            'message' =>
                '<p>You must use one of these methods: '.
                implode(', ', $methods) . '</p>'
        ]);
    }
}

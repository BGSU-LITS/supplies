<?php
/**
 * Not Found Handler Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2016 Bowling Green State University Libraries
 * @license MIT
 * @package Supplies
 */

namespace App\Handler;

use Slim\Views\Twig;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handles not found errors for the application.
 */
class NotFoundHandler extends \Slim\Handlers\NotFound
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
     * Renders an error message as HTML that the resource was not found.
     * @param Request $req The request.
     * @return string An HTML error message that the resource was not found.
     */
    protected function renderHtmlNotFoundOutput(Request $req)
    {
        // Not used.
        $req;

        // Return the error message template view with appropriate message.
        return $this->view->fetch('error.html.twig', [
            'title' => 'Not Found',
            'message' => '<p>The requested resource could not be found.</p>'
        ]);
    }
}

<?php
/**
 * Form Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2016 Bowling Green State University Libraries
 * @license MIT
 * @package Supplies
 */

namespace App\Action;

use \App\Exception\RequestException;

use Slim\Flash\Messages;
use Slim\Views\Twig;
use Swift_Mailer;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * A class to be invoked for the form action.
 */
class IndexAction
{
    /**
     * Flash messenger.
     * @var Messages
     */
    private $flash;

    /**
     * View renderer.
     * @var Twig
     */
    private $view;

    /**
     * Email sender.
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * Address email should be sent to.
     * @var string
     */
    private $mailTo;

    /**
     * Address email should be carbon copied to.
     * @var string
     */
    private $mailCc;

    /**
     * Construct the action with objects and configuration.
     * @param Messages $flash Flash messenger
     * @param Twig $view View renderer.
     * @param Swift_Mailer $mailer Email sender.
     * @param string $mailTo Address email should be sent to.
     * @param string $mailCc Address email should be carbon copied to.
     */
    public function __construct(
        Messages $flash,
        Twig $view,
        Swift_Mailer $mailer,
        $mailTo,
        $mailCc
    ) {
        $this->flash = $flash;
        $this->view = $view;
        $this->mailer = $mailer;
        $this->mailTo = $mailTo;
        $this->mailCc = $mailCc;
    }

    /**
     * Method called when class is invoked as an action.
     * @param Request $req The request for the action.
     * @param Response $res The response from the action.
     * @param array $args The arguments for the action.
     * @return Response The response from the action.
     */
    public function __invoke(Request $req, Response $res, array $args)
    {
        $args['messages'] = $this->messages();

        if ($req->getMethod() === 'POST') {
            $keys = [
                'department',
                'name',
                'email',
                'phone',
                'items'
            ];

            foreach ($keys as $key) {
                $args[$key] = $req->getParam($key);
            }

            foreach (array_keys($args['items']) as $item) {
                if (is_array($args['items'][$item])) {
                    foreach ($args['items'][$item] as $key => $value) {
                        if (empty($value)) {
                            unset($args['items'][$item][$key]);
                        }
                    }
                }

                if (empty($args['items'][$item])) {
                    unset($args['items'][$item]);
                }
            }

            try {
                $this->validateCsrf($req);
                $this->validateRequest($args);
                $this->sendEmail($args);

                $this->flash->addMessage(
                    'success',
                    'Your supply request has been sent.'.
                    ' You may send another request below.'
                );

                return $res->withStatus(302)->withHeader(
                    'Location',
                    $req->getUri()->getBasePath()
                );
            } catch (RequestException $exception) {
                $args['messages'][] = [
                    'level' => 'danger',
                    'message' => $exception->getMessage()
                ];
            }
        }

        // Render form template.
        return $this->view->render($res, 'index.html.twig', $args);
    }

    private function messages()
    {
        $result = [];

        foreach (['success', 'danger'] as $level) {
            $messages = $this->flash->getMessage($level);

            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $result[] = [
                        'level' => $level,
                        'message' => $message
                    ];
                }
            }
        }

        return $result;
    }

    private function sendEmail(array $args)
    {
        try {
            $mailCc = [$args['email']];

            if (!empty($this->mailCc)) {
                $mailCc[] = $this->mailCc;
            }

            $message = $this->mailer->createMessage()
                ->setSubject('Supply Request')
                ->setFrom($args['email'])
                ->setTo($this->mailTo)
                ->setCc($mailCc)
                ->setBody(
                    $this->view->fetch('email.html.twig', $args),
                    'text/html'
                );

            if (!$this->mailer->send($message)) {
                throw new RequestException(
                    'Could not send email to the address specified.'
                );
            }
        } catch (\Swift_SwiftException $e) {
            throw new RequestException(
                'An unexpected error occurred. Please try again.'
            );
        }
    }

    private function validateCsrf(Request $req)
    {
        if ($req->getAttribute('csrf_failed')) {
            throw new RequestException(
                'Your request timed out. Please try again.'
            );
        }
    }

    private function validateRequest(array $args)
    {
        if (!filter_var($args['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RequestException(
                'You must specify a valid email.'
            );
        }

        if (empty($args['items'])) {
            throw new RequestException(
                'You must specify at least one item.'
            );
        }
    }
}

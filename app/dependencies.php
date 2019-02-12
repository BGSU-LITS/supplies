<?php
/**
 * Application Dependencies
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2016 Bowling Green State University Libraries
 * @license MIT
 * @package Supplies
 */

namespace App;

use Slim\Container;
use Slim\Csrf\Guard;
use Psr\Log\LoggerInterface;
use Slim\Flash\Messages;
use Swift_Mailer;
use Slim\Views\Twig;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

// Add Slim CSRF Guard to the container.
$container[Guard::class] = function (Container $container) {
    $guard = new Guard;

    // Mark requests with failed CSRF, instead of displaying error.
    $guard->setFailureCallable(function (Request $req, Response $res, $next) {
        return $next($req->withAttribute('csrf_failed', true), $res);
    });

    return $guard;
};

// Add a PSR-3 compatible logger to the container.
$container[LoggerInterface::class] = function (Container $container) {
    // Create new monolog logger.
    $logger = new \Monolog\Logger('supplies');

    // If a log file was specified, add handler for that file to logger.
    if ($container['settings']['log']) {
        // Create stream handler for the specified log path.
        $handler = new \Monolog\Handler\StreamHandler(
            $container['settings']['log']
        );

        // Format the handler to only include stacktraces if in debug mode.
        $formatter = new \Monolog\Formatter\LineFormatter();
        $formatter->includeStacktraces($container['settings']['debug']);
        $handler->setFormatter($formatter);

        // Add web information to handler, and add handler to logger.
        $handler->pushProcessor(new \Monolog\Processor\WebProcessor());
        $logger->pushHandler($handler);
    }

    return $logger;
};

// Add Slim Flash Messages to the container.
$container[Messages::class] = function (Container $container) {
    return new Messages;
};

// Add Swift Mailer to the container.
$container[Swift_Mailer::class] = function (Container $container) {
    $transport = \Swift_SmtpTransport::newInstance(
        $container['settings']['smtp']['host'],
        $container['settings']['smtp']['port']
    );

    return Swift_Mailer::newInstance($transport);
};

// Add a Twig template processor to the container.
$container[Twig::class] = function (Container $container) {
    // Always search package's template directory.
    $paths = [dirname(__DIR__) . '/templates'];

    // If another template directory is specified, search it first.
    if (!empty($container['settings']['template']['path'])) {
        array_unshift($paths, $container['settings']['template']['path']);
    }

    // Define options for Twig.
    $options = [
        'cache' => dirname(__DIR__) . '/cache',
        'debug' => $container['settings']['debug']
    ];

    // Create Twig view and make package settings available.
    $view = new Twig($paths, $options);
    $view['settings'] = $container['settings']->all();

    // Add Aura.Html helper to the view.
    $helperLocatorFactory = new \Aura\Html\HelperLocatorFactory();
    $view['helper'] = $helperLocatorFactory->newInstance();

    // Add hidden inputs for CSRF protection.
    $view['csrf_hidden'] = trim($view['helper']->input([
        'type' => 'hidden',
        'name' => $container[Guard::class]->getTokenNameKey(),
        'value' => $container[Guard::class]->getTokenName()
    ]));

    $view['csrf_hidden'] .= trim($view['helper']->input([
        'type' => 'hidden',
        'name' => $container[Guard::class]->getTokenValueKey(),
        'value' => $container[Guard::class]->getTokenValue()
    ]));

    // Add Slim extension to the view.
    $basePath = $container['request']->getUri()->getBasePath();

    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        rtrim(str_ireplace('index.php', '', $basePath), '/')
    ));

    return $view;
};

// Add the index action to the container.
$container[Action\IndexAction::class] = function (Container $container) {
    return new Action\IndexAction(
        $container[Messages::class],
        $container[Twig::class],
        $container[Swift_Mailer::class],
        $container['settings']['mail']['to'],
        $container['settings']['mail']['cc']
    );
};

// Add our application's error handler to container.
$container['errorHandler'] = function (Container $container) {
    return new Handler\ErrorHandler(
        $container[LoggerInterface::class],
        $container[Twig::class],
        $container['settings']['debug']
    );
};

// Add our application's not found handler to container.
$container['notFoundHandler'] = function (Container $container) {
    return new Handler\NotFoundHandler($container[Twig::class]);
};

// Add our application's method not allowed handler to container.
$container['notAllowedHandler'] = function (Container $container) {
    return new Handler\NotAllowedHandler($container[Twig::class]);
};

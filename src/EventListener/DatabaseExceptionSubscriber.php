<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Replaces Doctrine exceptions by an HttpException with a generic message,
 * so that SQL queries and driver details never reach the client.
 */
final class DatabaseExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // Must run before API Platform's ErrorListener builds the response.
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof NoResultException) {
            $status = 404;
            $message = 'Resource not found.';
        } elseif ($exception instanceof NonUniqueResultException) {
            $status = 400;
            $message = 'The request could not be processed.';
        } elseif ($exception instanceof ConstraintViolationException) {
            $status = 400;
            $message = 'The request violates a data constraint.';
        } elseif ($exception instanceof DbalException || $exception instanceof ORMException) {
            $status = 500;
            $message = 'A database error occurred.';
        } else {
            return;
        }

        $this->logger->error('Database exception: '.$exception->getMessage(), ['exception' => $exception]);

        $event->setThrowable(new HttpException($status, $message, $exception));
    }
}

<?php

namespace App\Tests;

use App\EventListener\DatabaseExceptionSubscriber;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DatabaseExceptionSubscriberTest extends TestCase
{
    public function testSqlDetailsAreHidden(): void
    {
        $driverException = new class('SQLSTATE[23000]: INSERT INTO secret_table') extends \Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return '23000';
            }
        };
        $exception = new UniqueConstraintViolationException($driverException, new Query('INSERT INTO secret_table', [], []));

        $event = new ExceptionEvent($this->createMock(KernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);
        (new DatabaseExceptionSubscriber(new NullLogger()))->onKernelException($event);

        $result = $event->getThrowable();
        $this->assertInstanceOf(HttpExceptionInterface::class, $result);
        $this->assertSame(400, $result->getStatusCode());
        $this->assertStringNotContainsString('secret_table', $result->getMessage());
        $this->assertSame($exception, $result->getPrevious());
    }
}

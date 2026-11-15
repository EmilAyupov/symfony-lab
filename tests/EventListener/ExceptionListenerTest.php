<?php

namespace App\Tests\EventListener;

use App\EventListener\ExceptionListener;
use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class ExceptionListenerTest extends TestCase
{
    public function testValidationExceptionReturns422(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $listener = new ExceptionListener($logger);

        $exception = new ValidationException([
            'price' => ['Product price cannot be negative.'],
        ]);

        $event = $this->createExceptionEvent($exception);

        $listener->onException($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            [
                'status' => 422,
                'errors' => [
                    'price' => ['Product price cannot be negative.'],
                ],
            ],
            json_decode($response->getContent(), true),
        );
    }

    public function testProductNotFoundExceptionReturns404(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $listener = new ExceptionListener($logger);

        $event = $this->createExceptionEvent(
            new ProductNotFoundException('Product not found.'),
        );

        $listener->onException($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            [
                'status' => 404,
                'errors' => [
                    'product' => ['Product not found.'],
                ],
            ],
            json_decode($response->getContent(), true),
        );
    }


    private function createExceptionEvent(
        \Throwable $exception,
    ): ExceptionEvent
    {
        $kernel = $this->createStub(KernelInterface::class);
        $request = Request::create('/');

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    public function testUnknownExceptionReturns500AndLogsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Необработанное исключение',
                self::callback(
                    fn(array $context): bool => $context['exception'] instanceof \RuntimeException
                        && $context['method'] === 'GET'
                        && $context['path'] === '/'
                )
            );

        $listener = new ExceptionListener($logger);
        $event = $this->createExceptionEvent(
            new \RuntimeException('Unexpected error')
        );

        $listener->onException($event);

        $response = $event->getResponse();

        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"status":500,"errors":{"server":["Внутренняя ошибка сервера."]}}',
            $response->getContent()
        );
    }

}

<?php

namespace App\EventListener;

use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request   = $event->getRequest();

        $context = [
            'exception' => $exception,
            'method'    => $request->getMethod(),
            'path'      => $request->getPathInfo(),
        ];

        // --- Клиентские ошибки: низкий уровень, без стектрейса как error ---
        if ($exception instanceof ValidationException) {
            $this->logger->info('Ошибка валидации запроса', $context + [
                'errors' => $exception->errors,
            ]);

            $event->setResponse(new JsonResponse(
                ['status' => 422, 'errors' => $exception->errors],
                422
            ));
            return;
        }

        if ($exception instanceof ProductNotFoundException) {
            $this->logger->notice('Товар не найден', $context);

            $event->setResponse(new JsonResponse(
                ['status' => 404, 'errors' => ['product' => [$exception->getMessage()]]],
                404
            ));
            return;
        }

        // --- Всё остальное: серверная ошибка, высокий уровень ---
        $this->logger->error('Необработанное исключение', $context);

        $event->setResponse(new JsonResponse(
            [
                'status' => 500,
                'errors' => [
                    'server' => ['Внутренняя ошибка сервера.'],
                ],
            ],
            500
        ));
    }
}

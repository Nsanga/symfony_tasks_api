<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    // This method handles kernel exceptions
    public function onKernelException(ExceptionEvent $event): void
    {
        // Get the exception object from the event
        $exception = $event->getThrowable();

        // Determine the status code based on the type of exception
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        // Create a JSON response with the error message and status code
        $response = new JsonResponse([
            'error' => $exception->getMessage(),
        ], $statusCode);

        // Set the JSON response as the event's response
        $event->setResponse($response);
    }
}

<?php

declare(strict_types=1);

namespace NeneField\Tests\Support;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test double that records whether it was invoked and with which request,
 * then returns a 200 response. Used to assert middleware pass-through.
 */
final class RecordingRequestHandler implements RequestHandlerInterface
{
    public bool $called = false;
    public ?ServerRequestInterface $request = null;

    public function __construct(
        private readonly Psr17Factory $psr17,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->called = true;
        $this->request = $request;

        return $this->psr17->createResponse(200);
    }
}

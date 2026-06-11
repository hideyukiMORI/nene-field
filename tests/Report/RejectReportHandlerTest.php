<?php

declare(strict_types=1);

namespace NeneField\Tests\Report;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneField\Report\RejectReportHandler;
use NeneField\Report\RejectReportInput;
use NeneField\Report\RejectReportUseCaseInterface;
use NeneField\Report\Report;
use NeneField\Report\ReportStatus;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Boundary handling for `POST /reports/{id}/reject`. The comment is mandatory and
 * is trimmed, so a whitespace-only comment is rejected; only an approver/admin may
 * reject. None of these guard failures reach the use case.
 */
final class RejectReportHandlerTest extends TestCase
{
    public function test_empty_comment_is_rejected(): void
    {
        $useCase = new SpyRejectReportUseCase();
        $response = $this->dispatch($useCase, 'approver', ['comment' => '']);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($useCase->called);
    }

    public function test_whitespace_only_comment_is_rejected(): void
    {
        $useCase = new SpyRejectReportUseCase();
        $response = $this->dispatch($useCase, 'approver', ['comment' => "   \n\t"]);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($useCase->called);
    }

    public function test_missing_comment_is_rejected(): void
    {
        $useCase = new SpyRejectReportUseCase();
        $response = $this->dispatch($useCase, 'approver', []);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($useCase->called);
    }

    public function test_submitter_is_forbidden(): void
    {
        $useCase = new SpyRejectReportUseCase();
        $response = $this->dispatch($useCase, 'submitter', ['comment' => 'needs fixing']);

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($useCase->called);
    }

    public function test_valid_comment_is_trimmed_and_passed_through(): void
    {
        $useCase = new SpyRejectReportUseCase();
        $response = $this->dispatch($useCase, 'approver', ['comment' => '  please revise  ']);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($useCase->called);
        self::assertSame('please revise', $useCase->input?->comment);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function dispatch(SpyRejectReportUseCase $useCase, string $role, array $body): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new RejectReportHandler(
            $useCase,
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17, 'https://nene-field.dev/problems/'),
        );

        $request = $psr17->createServerRequest('POST', '/reports/r1/reject')
            ->withBody($psr17->createStream((string) json_encode($body)))
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['report_id' => 'r1'])
            ->withAttribute('nene2.auth.claims', ['sub' => 'approver-1', 'role' => $role, 'org' => 'org-1']);

        return $handler->handle($request);
    }
}

/**
 * Records the reject input and returns a minimal rejected report.
 */
final class SpyRejectReportUseCase implements RejectReportUseCaseInterface
{
    public bool $called = false;
    public ?RejectReportInput $input = null;

    public function execute(RejectReportInput $input): Report
    {
        $this->called = true;
        $this->input = $input;

        return new Report(
            reportId: $input->reportId,
            organizationId: $input->organizationId,
            userId: 'owner-1',
            title: 'T',
            body: 'B',
            workDate: '2026-06-11',
            status: ReportStatus::Rejected,
            approverId: $input->actorId,
            approverComment: $input->comment,
        );
    }
}

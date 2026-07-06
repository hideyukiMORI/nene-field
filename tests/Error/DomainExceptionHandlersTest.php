<?php

declare(strict_types=1);

namespace NeneField\Tests\Error;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use NeneField\Attachment\AttachmentIntegrityException;
use NeneField\Attachment\AttachmentIntegrityExceptionHandler;
use NeneField\Attachment\AttachmentNotFoundException;
use NeneField\Attachment\AttachmentNotFoundExceptionHandler;
use NeneField\Attachment\AttachmentReportNotFoundException;
use NeneField\Attachment\AttachmentReportNotFoundExceptionHandler;
use NeneField\Attachment\AttachmentStorageException;
use NeneField\Attachment\AttachmentStorageExceptionHandler;
use NeneField\Attachment\AttachmentTooLargeException;
use NeneField\Attachment\AttachmentTooLargeExceptionHandler;
use NeneField\Attachment\ReportNotAcceptingAttachmentsException;
use NeneField\Attachment\ReportNotAcceptingAttachmentsExceptionHandler;
use NeneField\Attachment\TooManyAttachmentsException;
use NeneField\Attachment\TooManyAttachmentsExceptionHandler;
use NeneField\Attachment\UnsupportedAttachmentTypeException;
use NeneField\Attachment\UnsupportedAttachmentTypeExceptionHandler;
use NeneField\Auth\InvalidCredentialsException;
use NeneField\Auth\InvalidCredentialsExceptionHandler;
use NeneField\Auth\Role;
use NeneField\Organization\OrganizationNotFoundException;
use NeneField\Organization\OrganizationNotFoundExceptionHandler;
use NeneField\Organization\OrganizationSlugConflictException;
use NeneField\Organization\OrganizationSlugConflictExceptionHandler;
use NeneField\Report\ReportNotEditableException;
use NeneField\Report\ReportNotEditableExceptionHandler;
use NeneField\Report\ReportNotFoundException;
use NeneField\Report\ReportNotFoundExceptionHandler;
use NeneField\Report\ReportNotInSubmittedStateException;
use NeneField\Report\ReportNotInSubmittedStateExceptionHandler;
use NeneField\Template\TemplateNotFoundException;
use NeneField\Template\TemplateNotFoundExceptionHandler;
use NeneField\User\CannotDeleteSelfException;
use NeneField\User\CannotDeleteSelfExceptionHandler;
use NeneField\User\RoleNotAssignableException;
use NeneField\User\RoleNotAssignableExceptionHandler;
use NeneField\User\UserEmailConflictException;
use NeneField\User\UserEmailConflictExceptionHandler;
use NeneField\User\UserNotFoundException;
use NeneField\User\UserNotFoundExceptionHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Each domain exception now maps to a response via a DomainExceptionHandler
 * (wired into the NENE2 error middleware) instead of a per-handler try/catch.
 * This test locks the migrated responses (status + problem `type` + detail) so
 * the adoption stays bit-for-bit non-breaking against the previous behaviour.
 */
final class DomainExceptionHandlersTest extends TestCase
{
    private const BASE = 'https://nene-field.dev/problems/';

    /**
     * @param DomainExceptionHandlerInterface $handler
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases')]
    public function test_handler_maps_exception(
        DomainExceptionHandlerInterface $handler,
        Throwable $exception,
        int $status,
        string $type,
        string $detail,
    ): void {
        self::assertTrue($handler->supports($exception));

        $psr17 = new Psr17Factory();
        $request = $psr17->createServerRequest('GET', '/whatever');
        $response = $handler->handle($exception, $request);

        self::assertSame($status, $response->getStatusCode());
        self::assertStringContainsString('application/problem+json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        self::assertSame(self::BASE . $type, $body['type']);
        self::assertSame($status, $body['status']);
        self::assertSame($detail, $body['detail']);
    }

    /**
     * @return iterable<string, array{DomainExceptionHandlerInterface, Throwable, int, string, string}>
     */
    public static function cases(): iterable
    {
        $pd = new ProblemDetailsResponseFactory(new Psr17Factory(), new Psr17Factory(), self::BASE);

        yield 'organization-not-found' => [new OrganizationNotFoundExceptionHandler($pd), new OrganizationNotFoundException(), 404, 'organization-not-found', 'The organization was not found.'];
        yield 'organization-slug-conflict' => [new OrganizationSlugConflictExceptionHandler($pd), new OrganizationSlugConflictException(), 409, 'organization-slug-conflict', 'An organization with this slug or custom domain already exists.'];
        yield 'invalid-credentials' => [new InvalidCredentialsExceptionHandler($pd), new InvalidCredentialsException(), 401, 'unauthorized', 'The email or password is incorrect.'];
        yield 'attachment-report-not-found' => [new AttachmentReportNotFoundExceptionHandler($pd), new AttachmentReportNotFoundException(), 404, 'report-not-found', 'The report was not found.'];
        yield 'report-not-accepting-attachments' => [new ReportNotAcceptingAttachmentsExceptionHandler($pd), new ReportNotAcceptingAttachmentsException(), 409, 'report-not-accepting-attachments', 'Attachments can only be changed while the report is a draft or rejected.'];
        yield 'too-many-attachments' => [new TooManyAttachmentsExceptionHandler($pd), new TooManyAttachmentsException(), 413, 'payload-too-large', 'The report already has the maximum number of attachments.'];
        yield 'attachment-too-large' => [new AttachmentTooLargeExceptionHandler($pd), new AttachmentTooLargeException(), 413, 'payload-too-large', 'The attachment exceeds the maximum file size.'];
        yield 'unsupported-attachment-type' => [new UnsupportedAttachmentTypeExceptionHandler($pd), new UnsupportedAttachmentTypeException('application/zip'), 422, 'validation-failed', 'Unsupported media type "application/zip". Allowed: image/jpeg, image/png, application/pdf.'];
        yield 'attachment-not-found' => [new AttachmentNotFoundExceptionHandler($pd), new AttachmentNotFoundException(), 404, 'attachment-not-found', 'The attachment was not found.'];
        yield 'attachment-integrity' => [new AttachmentIntegrityExceptionHandler($pd), new AttachmentIntegrityException(), 500, 'attachment-integrity-failed', 'The attachment could not be served.'];
        yield 'attachment-storage' => [new AttachmentStorageExceptionHandler($pd), new AttachmentStorageException(), 500, 'attachment-unavailable', 'The attachment could not be served.'];
        yield 'report-not-found' => [new ReportNotFoundExceptionHandler($pd), new ReportNotFoundException(), 404, 'report-not-found', 'The report was not found.'];
        yield 'report-not-in-submitted-state' => [new ReportNotInSubmittedStateExceptionHandler($pd), new ReportNotInSubmittedStateException(), 409, 'report-not-in-submitted-state', 'The report is not awaiting approval.'];
        yield 'report-not-editable-default' => [new ReportNotEditableExceptionHandler($pd), new ReportNotEditableException(), 409, 'report-not-editable', 'The report cannot be modified in its current state.'];
        yield 'report-not-editable-submit' => [new ReportNotEditableExceptionHandler($pd), new ReportNotEditableException('The report cannot be submitted in its current state.'), 409, 'report-not-editable', 'The report cannot be submitted in its current state.'];
        yield 'report-not-editable-delete' => [new ReportNotEditableExceptionHandler($pd), new ReportNotEditableException('Only a draft report can be deleted.'), 409, 'report-not-editable', 'Only a draft report can be deleted.'];
        yield 'role-not-assignable' => [new RoleNotAssignableExceptionHandler($pd), new RoleNotAssignableException(Role::Superadmin), 422, 'validation-failed', 'The role "superadmin" cannot be assigned through user management.'];
        yield 'user-email-conflict' => [new UserEmailConflictExceptionHandler($pd), new UserEmailConflictException(), 409, 'user-email-conflict', 'A user with this email already exists.'];
        yield 'user-not-found' => [new UserNotFoundExceptionHandler($pd), new UserNotFoundException(), 404, 'user-not-found', 'The user was not found.'];
        yield 'cannot-delete-self' => [new CannotDeleteSelfExceptionHandler($pd), new CannotDeleteSelfException(), 409, 'cannot-delete-self', 'You cannot delete your own account.'];
        yield 'template-not-found' => [new TemplateNotFoundExceptionHandler($pd), new TemplateNotFoundException(), 404, 'template-not-found', 'The template was not found.'];
    }

    public function test_role_not_assignable_reports_error_against_role_field(): void
    {
        $pd = new ProblemDetailsResponseFactory(new Psr17Factory(), new Psr17Factory(), self::BASE);
        $handler = new RoleNotAssignableExceptionHandler($pd);
        $request = (new Psr17Factory())->createServerRequest('POST', '/users');

        $response = $handler->handle(new RoleNotAssignableException(Role::Superadmin), $request);

        /** @var array{errors: list<array{field: string, code: string, message: string}>} $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        self::assertSame('role', $body['errors'][0]['field']);
        self::assertSame('invalid_value', $body['errors'][0]['code']);
    }

    public function test_unsupported_attachment_type_reports_error_against_file_field(): void
    {
        $pd = new ProblemDetailsResponseFactory(new Psr17Factory(), new Psr17Factory(), self::BASE);
        $handler = new UnsupportedAttachmentTypeExceptionHandler($pd);
        $request = (new Psr17Factory())->createServerRequest('POST', '/reports/r1/attachments');

        $response = $handler->handle(new UnsupportedAttachmentTypeException('application/zip'), $request);

        /** @var array{errors: list<array{field: string, code: string}>} $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        self::assertSame('file', $body['errors'][0]['field']);
        self::assertSame('unsupported_media_type', $body['errors'][0]['code']);
    }

    public function test_handler_ignores_unrelated_exception(): void
    {
        $pd = new ProblemDetailsResponseFactory(new Psr17Factory(), new Psr17Factory(), self::BASE);
        $handler = new ReportNotFoundExceptionHandler($pd);

        self::assertFalse($handler->supports(new \RuntimeException('nope')));
    }
}

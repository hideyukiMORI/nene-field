<?php

declare(strict_types=1);

namespace NeneField\Attachment;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers report-attachment routes (nested under a report). Upload/delete are
 * restricted to the owning submitter on a draft/rejected report; download is for
 * any authenticated caller who can see the report — all enforced in the use
 * cases. Invoked with the {@see Router} during runtime assembly.
 */
final readonly class AttachmentRouteRegistrar
{
    public function __construct(
        private UploadAttachmentHandler $uploadHandler,
        private DownloadAttachmentHandler $downloadHandler,
        private DeleteAttachmentHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $router->post('/reports/{report_id}/attachments', fn (ServerRequestInterface $r) => $this->uploadHandler->handle($r));
        $router->get('/reports/{report_id}/attachments/{attachment_id}', fn (ServerRequestInterface $r) => $this->downloadHandler->handle($r));
        $router->delete('/reports/{report_id}/attachments/{attachment_id}', fn (ServerRequestInterface $r) => $this->deleteHandler->handle($r));
    }
}

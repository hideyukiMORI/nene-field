<?php

declare(strict_types=1);

namespace NeneField\Attachment;

/**
 * Stores attachment binaries on the local filesystem under a configured root
 * (Tier A shared hosting / self-host default). The `storageKey` is treated as a
 * path relative to the root; keys containing traversal segments are rejected so
 * a crafted key can never escape the storage root.
 */
final readonly class LocalAttachmentStorage implements AttachmentStorageInterface
{
    public function __construct(
        private string $rootPath,
    ) {
    }

    public function write(string $storageKey, string $contents): void
    {
        $path = $this->resolve($storageKey);
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0o770, true) && !is_dir($dir)) {
            throw new AttachmentStorageException('Could not create the storage directory.');
        }

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new AttachmentStorageException('Could not write the attachment.');
        }
    }

    public function read(string $storageKey): string
    {
        $path = $this->resolve($storageKey);
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new AttachmentStorageException('Could not read the attachment.');
        }

        return $contents;
    }

    public function delete(string $storageKey): void
    {
        $path = $this->resolve($storageKey);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function exists(string $storageKey): bool
    {
        return is_file($this->resolve($storageKey));
    }

    private function resolve(string $storageKey): string
    {
        if ($storageKey === '' || str_contains($storageKey, '..') || str_starts_with($storageKey, '/')) {
            throw new AttachmentStorageException('Invalid storage key.');
        }

        return rtrim($this->rootPath, '/') . '/' . $storageKey;
    }
}

<?php

declare(strict_types=1);

namespace NeneField\Tests\Attachment;

use NeneField\Attachment\AttachmentStorageException;
use NeneField\Attachment\LocalAttachmentStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Filesystem storage behaviour and its path-traversal guard. A crafted storage
 * key must never escape the configured root (defence in depth; keys are
 * application-generated, but the guard is the backstop).
 */
final class LocalAttachmentStorageTest extends TestCase
{
    private string $root;
    private LocalAttachmentStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/nf_store_' . bin2hex(random_bytes(6));
        $this->storage = new LocalAttachmentStorage($this->root);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($it as $file) {
                $path = $file->getPathname();
                $file->isDir() ? @rmdir($path) : @unlink($path);
            }
            @rmdir($this->root);
        }
        parent::tearDown();
    }

    public function test_write_read_delete_round_trip(): void
    {
        $key = 'att/org/report/abc';

        self::assertFalse($this->storage->exists($key));
        $this->storage->write($key, 'hello');
        self::assertTrue($this->storage->exists($key));
        self::assertSame('hello', $this->storage->read($key));

        $this->storage->delete($key);
        self::assertFalse($this->storage->exists($key));
    }

    public function test_overwrite_replaces_contents(): void
    {
        $key = 'att/org/report/x';
        $this->storage->write($key, 'first');
        $this->storage->write($key, 'second');
        self::assertSame('second', $this->storage->read($key));
    }

    public function test_read_missing_throws(): void
    {
        $this->expectException(AttachmentStorageException::class);
        $this->storage->read('att/org/report/missing');
    }

    public function test_delete_missing_is_noop(): void
    {
        $this->storage->delete('att/org/report/missing');
        self::assertFalse($this->storage->exists('att/org/report/missing'));
    }

    #[DataProvider('unsafeKeys')]
    public function test_traversal_and_empty_keys_are_rejected(string $key): void
    {
        $this->expectException(AttachmentStorageException::class);
        $this->storage->write($key, 'x');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeKeys(): iterable
    {
        yield 'parent traversal' => ['../escape'];
        yield 'nested traversal' => ['att/../../etc/passwd'];
        yield 'absolute path' => ['/etc/passwd'];
        yield 'empty' => [''];
    }

    public function test_traversal_key_is_rejected_on_read(): void
    {
        $this->expectException(AttachmentStorageException::class);
        $this->storage->read('../secret');
    }

    public function test_traversal_key_is_rejected_on_exists(): void
    {
        $this->expectException(AttachmentStorageException::class);
        $this->storage->exists('att/../secret');
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\FileUploadService;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Unit tests for FileUploadService
 */
class FileUploadServiceTest extends TestCase
{
    private FilesystemOperator $uploadsStorage;
    private FilesystemOperator $documentsStorage;
    private FileUploadService $service;

    protected function setUp(): void
    {
        $this->uploadsStorage = $this->createMock(FilesystemOperator::class);
        $this->documentsStorage = $this->createMock(FilesystemOperator::class);
        $slugger = new AsciiSlugger();

        $this->service = new FileUploadService(
            $this->uploadsStorage,
            $this->documentsStorage,
            $slugger
        );
    }

    public function testUploadPublicFileSuccess(): void
    {
        // Create a temporary test file
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'test content');

        $uploadedFile = new UploadedFile(
            $testFilePath,
            'test-image.jpg',
            'image/jpeg',
            null,
            true  // test mode
        );

        $this->uploadsStorage->expects($this->once())
            ->method('writeStream')
            ->with($this->stringContains('.jpg'));

        $result = $this->service->uploadPublicFile($uploadedFile);

        $this->assertStringEndsWith('.jpg', $result);
        $this->assertStringContainsString('test-image', $result);

        fclose($testFile);
    }

    public function testUploadPublicFileWithSubdirectory(): void
    {
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'test content');

        $uploadedFile = new UploadedFile(
            $testFilePath,
            'avatar.png',
            'image/png',
            null,
            true
        );

        $this->uploadsStorage->expects($this->once())
            ->method('writeStream')
            ->with($this->stringStartsWith('avatars/'));

        $result = $this->service->uploadPublicFile($uploadedFile, 'avatars');

        $this->assertStringStartsWith('avatars/', $result);
        $this->assertStringEndsWith('.png', $result);

        fclose($testFile);
    }

    public function testUploadPrivateFileSuccess(): void
    {
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'confidential document');

        $uploadedFile = new UploadedFile(
            $testFilePath,
            'contract.pdf',
            'application/pdf',
            null,
            true
        );

        $this->documentsStorage->expects($this->once())
            ->method('writeStream')
            ->with($this->stringContains('.pdf'));

        $result = $this->service->uploadPrivateFile($uploadedFile);

        $this->assertStringEndsWith('.pdf', $result);
        $this->assertStringContainsString('contract', $result);

        fclose($testFile);
    }

    public function testUploadFileExceedsMaxSize(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/exceeds maximum allowed size/');

        // Create a file that's too large (mock)
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(11 * 1024 * 1024); // 11MB (over 10MB limit)
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');

        $this->service->uploadPublicFile($uploadedFile);
    }

    public function testUploadFileInvalidMimeType(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/File type .* is not allowed/');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getMimeType')->willReturn('application/x-executable'); // Not allowed

        $this->service->uploadPublicFile($uploadedFile);
    }

    public function testUploadInvalidFile(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Invalid file upload');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->method('isValid')->willReturn(false); // Invalid upload

        $this->service->uploadPublicFile($uploadedFile);
    }

    public function testDeletePublicFileSuccess(): void
    {
        $this->uploadsStorage->expects($this->once())
            ->method('fileExists')
            ->with('test.jpg')
            ->willReturn(true);

        $this->uploadsStorage->expects($this->once())
            ->method('delete')
            ->with('test.jpg');

        $result = $this->service->deletePublicFile('test.jpg');

        $this->assertTrue($result);
    }

    public function testDeletePublicFileNotFound(): void
    {
        $this->uploadsStorage->expects($this->once())
            ->method('fileExists')
            ->with('nonexistent.jpg')
            ->willReturn(false);

        $this->uploadsStorage->expects($this->never())
            ->method('delete');

        $result = $this->service->deletePublicFile('nonexistent.jpg');

        $this->assertFalse($result);
    }

    public function testDeletePrivateFileSuccess(): void
    {
        $this->documentsStorage->expects($this->once())
            ->method('fileExists')
            ->with('document.pdf')
            ->willReturn(true);

        $this->documentsStorage->expects($this->once())
            ->method('delete')
            ->with('document.pdf');

        $result = $this->service->deletePrivateFile('document.pdf');

        $this->assertTrue($result);
    }

    public function testGetPublicUrl(): void
    {
        $url = $this->service->getPublicUrl('images/test.jpg');

        $this->assertEquals('/uploads/images/test.jpg', $url);
    }

    public function testGetPublicUrlWithLeadingSlash(): void
    {
        $url = $this->service->getPublicUrl('/images/test.jpg');

        $this->assertEquals('/uploads/images/test.jpg', $url);
    }

    public function testFileExists(): void
    {
        $this->uploadsStorage->expects($this->once())
            ->method('fileExists')
            ->with('test.jpg')
            ->willReturn(true);

        $result = $this->service->fileExists('test.jpg');

        $this->assertTrue($result);
    }

    public function testFileDoesNotExist(): void
    {
        $this->uploadsStorage->expects($this->once())
            ->method('fileExists')
            ->with('missing.jpg')
            ->willReturn(false);

        $result = $this->service->fileExists('missing.jpg');

        $this->assertFalse($result);
    }

    public function testGetAllowedMimeTypes(): void
    {
        $mimeTypes = $this->service->getAllowedMimeTypes();

        $this->assertIsArray($mimeTypes);
        $this->assertNotEmpty($mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('application/pdf', $mimeTypes);
        $this->assertContains('application/zip', $mimeTypes);
    }

    public function testIsImageType(): void
    {
        $this->assertTrue($this->service->isImageType('image/jpeg'));
        $this->assertTrue($this->service->isImageType('image/png'));
        $this->assertTrue($this->service->isImageType('image/gif'));
        $this->assertFalse($this->service->isImageType('application/pdf'));
    }

    public function testIsDocumentType(): void
    {
        $this->assertTrue($this->service->isDocumentType('application/pdf'));
        $this->assertTrue($this->service->isDocumentType('application/msword'));
        $this->assertTrue($this->service->isDocumentType('text/plain'));
        $this->assertFalse($this->service->isDocumentType('image/jpeg'));
    }

    public function testUploadPreservesOriginalName(): void
    {
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'test content');

        $uploadedFile = new UploadedFile(
            $testFilePath,
            'my-document.pdf',
            'application/pdf',
            null,
            true
        );

        $this->uploadsStorage->expects($this->once())
            ->method('writeStream')
            ->with($this->callback(function ($filename) {
                // When preserving original name, should have the slugged name without unique ID
                return str_contains($filename, 'my-document') && str_ends_with($filename, '.pdf');
            }));

        $result = $this->service->uploadPublicFile($uploadedFile, null, true);

        $this->assertStringContainsString('my-document', $result);
        $this->assertStringEndsWith('.pdf', $result);

        fclose($testFile);
    }

    public function testUploadGeneratesUniqueFilename(): void
    {
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'test content');

        $uploadedFile = new UploadedFile(
            $testFilePath,
            'document.pdf',
            'application/pdf',
            null,
            true
        );

        $this->uploadsStorage->expects($this->once())
            ->method('writeStream')
            ->with($this->callback(function ($filename) {
                // When not preserving, should have unique ID
                return str_contains($filename, 'document') && str_ends_with($filename, '.pdf');
            }));

        $result = $this->service->uploadPublicFile($uploadedFile, null, false);

        $this->assertStringContainsString('document', $result);
        $this->assertStringEndsWith('.pdf', $result);

        fclose($testFile);
    }

    /**
     * Test various allowed image types
     *
     * @dataProvider allowedImageTypesProvider
     */
    public function testAllowedImageTypes(string $mimeType, string $extension): void
    {
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'test content');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getMimeType')->willReturn($mimeType);
        $uploadedFile->method('getPathname')->willReturn($testFilePath);
        $uploadedFile->method('getClientOriginalName')->willReturn("test.{$extension}");
        $uploadedFile->method('guessExtension')->willReturn($extension);

        $this->uploadsStorage->expects($this->once())
            ->method('writeStream');

        $result = $this->service->uploadPublicFile($uploadedFile);

        $this->assertStringEndsWith(".{$extension}", $result);

        fclose($testFile);
    }

    public static function allowedImageTypesProvider(): array
    {
        return [
            'JPEG' => ['image/jpeg', 'jpeg'],
            'PNG' => ['image/png', 'png'],
            'GIF' => ['image/gif', 'gif'],
            'WebP' => ['image/webp', 'webp'],
        ];
    }

    /**
     * Test various allowed document types
     *
     * @dataProvider allowedDocumentTypesProvider
     */
    public function testAllowedDocumentTypes(string $mimeType, string $extension): void
    {
        $testFile = tmpfile();
        $testFilePath = stream_get_meta_data($testFile)['uri'];
        file_put_contents($testFilePath, 'test content');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getMimeType')->willReturn($mimeType);
        $uploadedFile->method('getPathname')->willReturn($testFilePath);
        $uploadedFile->method('getClientOriginalName')->willReturn("document.{$extension}");
        $uploadedFile->method('guessExtension')->willReturn($extension);

        $this->documentsStorage->expects($this->once())
            ->method('writeStream');

        $result = $this->service->uploadPrivateFile($uploadedFile);

        $this->assertStringEndsWith(".{$extension}", $result);

        fclose($testFile);
    }

    public static function allowedDocumentTypesProvider(): array
    {
        return [
            'PDF' => ['application/pdf', 'pdf'],
            'Word' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx'],
            'Text' => ['text/plain', 'txt'],
            'CSV' => ['text/csv', 'csv'],
        ];
    }
}

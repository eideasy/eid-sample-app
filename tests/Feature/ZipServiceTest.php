<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TempFileStorageService;
use App\Services\ZipService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class ZipServiceTest extends TestCase
{
    /**
     * @dataProvider batchFileNames
     */
    public function testBatchZipPreservesFinalExtension(string $fileName, string $baseName, string $extension): void
    {
        Log::shouldReceive('info');
        $archivePath = tempnam(sys_get_temp_dir(), 'batch-zip-test-');
        $this->assertNotFalse($archivePath);
        unlink($archivePath);

        $storage = Mockery::mock(TempFileStorageService::class);
        $storage->shouldReceive('createTempFolderIfNeeded')->once()->andReturn($archivePath);
        $files = ['first PDF contents', 'second PDF contents', 'third PDF contents', 'fourth PDF contents'];
        $archive = new ZipArchive();

        try {
            $result = (new ZipService($storage))->zipFiles($fileName, $files, false);
            $this->assertTrue($archive->open($archivePath));
            $this->assertSame(4, $archive->numFiles);

            foreach ($files as $index => $contents) {
                $expectedName = $baseName . $index . $extension;
                $this->assertSame($expectedName, $archive->getNameIndex($index));
                $this->assertSame($contents, $archive->getFromName($expectedName));
            }

            $this->assertSame($baseName . '.zip', $result->getFileName());
            $this->assertSame($baseName . '.zip', $result->getFilePath());
        } finally {
            if ($archive->numFiles > 0) {
                $archive->close();
            }
            if (file_exists($archivePath)) {
                unlink($archivePath);
            }
        }
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function batchFileNames(): array
    {
        return [
            'date in filename' => ['25.09.pdf', '25.09', '.pdf'],
            'multiple dots' => ['contract.final.v2.pdf', 'contract.final.v2', '.pdf'],
            'single extension' => ['document.pdf', 'document', '.pdf'],
            'no extension' => ['document', 'document', ''],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class ZipService
{
    protected TempFileStorageService $tempFileStorageService;

    public function __construct(TempFileStorageService $tempFileStorageService)
    {
        $this->tempFileStorageService = $tempFileStorageService;
    }

    /**
     * @param Collection|array $files
     */
    public function zipFiles(
        string $fileName,
        $files,
        bool $addUuid = true
    ): ZipDto {
        [$fileNameWithOutExtension, $fileExtension] = explode('.', $fileName);
        $filePath = $fileNameWithOutExtension . '.zip';

        if ($addUuid) {
            $uuid = Uuid::uuid4()->toString();
            $filePath = $fileNameWithOutExtension . '_' . $uuid . '.zip';
        }
        Log::info('Adding uuid to zip file', ['file_path' => $filePath]);

        $absolutePath = $this->tempFileStorageService->createTempFolderIfNeeded($filePath);

        $zip = new ZipArchive();
        $zip->open($absolutePath, ZipArchive::CREATE);
        foreach ($files as $index => $file) {
            $pdfFileName = $fileNameWithOutExtension . ((string) $index) . '.' . $fileExtension;
            $zip->addFromString($pdfFileName, $file);
        }
        $zip->close();

        $zipFileName = $fileNameWithOutExtension . '.zip';
        $zipFileName = iconv('utf-8', 'ascii//TRANSLIT', $zipFileName);

        return new ZipDto($zipFileName, $filePath);
    }
}

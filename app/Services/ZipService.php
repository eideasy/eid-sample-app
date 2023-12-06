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
    public function zipPdfs(
        string $fileName,
        $files,
        bool $addUuid = true
    ): ZipDto {
        $filePath = $fileName . '.zip';
        if ($addUuid) {
            $uuid = Uuid::uuid4()->toString();
            $filePath = $fileName . '_' . $uuid . '.zip';
            Log::info('Adding uuid to zip file', [
                'uuid' => $uuid,
                'name' => $filePath,
            ]);
        }

        $absolutePath = $this->tempFileStorageService->createTempFolderIfNeeded($filePath);
        $fileNameWithOutExtension = str_replace('.pdf', '', $fileName);

        $zip = new ZipArchive();
        $zip->open($absolutePath, ZipArchive::CREATE);
        foreach ($files as $index => $file) {
            $pdfFileName = $fileNameWithOutExtension . ((string) $index) . '.pdf';
            $zip->addFromString($pdfFileName, $file);
        }
        $zip->close();

        $fileName = str_replace('.pdf', '', $fileName) . '.zip';
        $fileName = str_replace(',', '', $fileName);
        $fileName = iconv('utf-8', 'ascii//TRANSLIT', $fileName);

//        DeleteTempFileJob::dispatch($filePath)->delay(now()->addMinutes(5));

        return new ZipDto($fileName, $filePath);
    }
}

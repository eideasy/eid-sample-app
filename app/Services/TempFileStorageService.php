<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;
use Psr\Log\LoggerInterface;

class TempFileStorageService
{
    public const TEMP_STORAGE_PATH = 'temp_file_storage/';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function path(string $fileName): string
    {
        return self::TEMP_STORAGE_PATH . $fileName;
    }

    public function basePath(): string
    {
        return storage_path('app/' . self::TEMP_STORAGE_PATH);
    }

    public function absolutePath(string $filePath): string
    {
        return self::basePath() . $filePath;
    }

    public function createTempFolderIfNeeded(string $filePath): string
    {
        $basePath = self::basePath();

        if (!file_exists($basePath) && !mkdir($basePath, 0777, true) && !is_dir($basePath)) {
            Storage::makeDirectory($basePath);
        }

        return $basePath . $filePath;
    }

    public function delete(string $fileName): bool
    {
        $storagePath = self::path($fileName);
        $this->logger->info('Deleting temp storage file', ['storage_path' => $storagePath]);
        // Deleting missing file will return true.
        if (!Storage::exists($storagePath)) {
            return false;
        }

        return Storage::delete($storagePath);
    }

    public function fileContent(string $filePath): ?string
    {
        $storagePath = null;
        try {
            $storagePath = self::path($filePath);

            return Storage::get($storagePath);
        } catch (UnableToReadFile $exception) {
            $this->logger->warning('Temp file not found', [
                'storage_path' => $storagePath,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return null;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to get temp file content', [
                'storage_path' => $storagePath,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\TempFileStorageService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

class DeleteTempFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private string $filePath;

    public function __construct(string $absolutePath)
    {
        $this->filePath = $absolutePath;
    }

    public function handle(
        LoggerInterface $logger,
        TempFileStorageService $tempFileStorageService
    ): void {
        $logContext = ['file_path' => $this->filePath];
        $logger->info('Deleting temp file', $logContext);

        $response = $tempFileStorageService->delete($this->filePath);
        if ($response) {
            $logger->info('Deleting temp file completed', $logContext);
        } else {
            $logger->error('Failed to delete temp file', $logContext);
            throw new Exception('Failed to delete temp file');
        }
    }
}

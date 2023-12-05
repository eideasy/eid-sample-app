<?php

declare(strict_types=1);

namespace App\Services;

class ZipDto
{
    private string $fileName;
    private string $filePath;

    public function __construct(
        string $fileName,
        string $filePath
    ) {
        info('ZipDto created', [
            'file_name' => $fileName,
            'file_path' => $filePath,
        ]);

        $this->fileName = $fileName;
        $this->filePath = $filePath;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}

<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadHelper
{
    /**
     * Upload a file to S3
     *
     * @param UploadedFile|null $file
     * @param string $directory
     * @return string|null Path of the uploaded file
     */
    public static function upload(?UploadedFile $file, string $directory): ?string
    {
        if (!$file || !$file instanceof UploadedFile) {
            return null;
        }

        $path = $file->store($directory, 's3');
        Storage::disk('s3')->setVisibility($path, 'public');

        return $path;
    }

    /**
     * Delete a file from S3
     *
     * @param string|null $path
     * @return bool True if deleted or no file exists
     */
    public static function delete(?string $path): bool
    {
        if (!$path) {
            return true;
        }

        return Storage::disk('s3')->delete($path);
    }

    /**
     * Replace an existing file with a new one
     *
     * @param string|null $oldPath
     * @param UploadedFile|null $newFile
     * @param string $directory
     * @return string|null New file path
     */
    public static function replace(?string $oldPath, ?UploadedFile $newFile, string $directory): ?string
    {
        self::delete($oldPath);
        return self::upload($newFile, $directory);
    }
}

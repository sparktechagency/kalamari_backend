<?php

// namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;


if (!function_exists('kmCount')) {
    function kmCount($number)
    {
        if ($number >= 1000000) {
            $formatted = $number / 1000000;
            return rtrim(rtrim(number_format($formatted, 1), '0'), '.') . 'M';
        } elseif ($number >= 1000) {
            $formatted = $number / 1000;
            return rtrim(rtrim(number_format($formatted, 1), '0'), '.') . 'K';
        }
        return $number;
    }
}

if (!function_exists('imageUpload')) {
    function imageUpload($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            return null;
        }

        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalFileName = Str::slug($originalFileName);
        $fileName = time() . '_' . $originalFileName;

        if (str_starts_with($file->getMimeType(), 'image/')) {

            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);

            if ($width || $height) {
                $image->scaleDown(width: $width, height: $height);
            }

            if ($forceWebp) {
                $fileName .= '.webp';
                $encodedImage = $image->toWebp($quality);
            } else {
                $fileName .= '.' . $file->getClientOriginalExtension();
                $encodedImage = match ($file->getMimeType()) {
                    'image/jpeg', 'image/jpg' => $image->toJpeg($quality),
                    'image/webp' => $image->toWebp($quality),
                    'image/gif' => $image->toGif(),
                    default => $image->toPng(),
                };
            }

            $filePath = "{$directory}/{$fileName}";
            Storage::disk('public')->put($filePath, (string) $encodedImage);

            return $filePath;
        }

        $fileName .= '.' . $file->getClientOriginalExtension();
        $filePath = "{$directory}/{$fileName}";
        return $file->storeAs($directory, $fileName, 'public');
    }
}





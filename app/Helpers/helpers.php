<?php

// namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
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
    function imageUpload1($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
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

    function imageUpload2($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return null;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugName = Str::slug($originalName);
        $timestamp = time();
        $mimeType = $file->getMimeType();

        // ইমেজ প্রসেসিং চেক
        if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/octet-stream') {
            try {
                // ১. ড্রাইভার সেটআপ (Imagick HEIC সাপোর্ট করে)
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file);

                // ২. রিসাইজিং
                if ($width || $height) {
                    $image->scaleDown(width: $width, height: $height);
                }

                // ৩. এক্সটেনশন এবং এনকোডিং নির্ধারণ
                if ($forceWebp || $mimeType === 'image/heic' || $mimeType === 'image/heif') {
                    $fileName = "{$timestamp}_{$slugName}.webp";
                    $encodedImage = $image->toWebp($quality);
                } else {
                    $extension = $file->getClientOriginalExtension();
                    $fileName = "{$timestamp}_{$slugName}.{$extension}";

                    $encodedImage = match ($mimeType) {
                        'image/jpeg', 'image/jpg' => $image->toJpeg($quality),
                        'image/gif'  => $image->toGif(),
                        'image/webp' => $image->toWebp($quality),
                        default      => $image->toPng(),
                    };
                }

                $filePath = "{$directory}/{$fileName}";
                Storage::disk('public')->put($filePath, $encodedImage->toString());

                return $filePath;
            } catch (\Exception $e) {
                // যদি ইমেজ ডিকোড করতে না পারে, তবে নরমাল ফাইল হিসেবে সেভ হবে
                Log::error("Image processing failed: " . $e->getMessage());
            }
        }

        // ইমেজ না হলে বা প্রসেসিং ফেইল করলে সাধারণ আপলোড
        $fileName = "{$timestamp}_{$slugName}." . $file->getClientOriginalExtension();
        return $file->storeAs($directory, $fileName, 'public');
    }

    function imageUpload($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return null;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugName = Str::slug($originalName);
        $timestamp = time();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $isHeic = in_array($extension, ['heic', 'heif']) || $mimeType === 'image/heic';

        try {
            if ($isHeic) {
                // ১. প্রথমে JPG হিসেবে কনভার্ট করার প্রস্তুতি (বেশি স্ট্যাবল)
                $tempJpgName = "{$timestamp}_{$slugName}.jpg";
                $finalWebpName = "{$timestamp}_{$slugName}.webp";

                $tempSource = $file->getRealPath();
                $storageDir = storage_path("app/public/" . trim($directory, '/'));

                if (!file_exists($storageDir)) {
                    mkdir($storageDir, 0755, true);
                }

                $tempJpgPath = $storageDir . '/' . $tempJpgName;
                $finalWebpPath = $storageDir . '/' . $finalWebpName;

                // ২. HEIC -> JPG কমান্ড রান করা
                $command = "heif-convert -q {$quality} " . escapeshellarg($tempSource) . " " . escapeshellarg($tempJpgPath) . " 2>&1";
                exec($command, $output, $returnVar);

                if ($returnVar === 0 && file_exists($tempJpgPath)) {
                    // ৩. এখন তৈরি হওয়া JPG ফাইলটিকে Intervention Image দিয়ে WebP তে কনভার্ট এবং রিসাইজ করা
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($tempJpgPath);

                    if ($width || $height) {
                        $image->scaleDown(width: $width, height: $height);
                    }

                    // WebP হিসেবে সেভ করা
                    $image->toWebp($quality)->save($finalWebpPath);

                    // টেম্পোরারি JPG ফাইলটি মুছে ফেলা
                    unlink($tempJpgPath);

                    return trim($directory, '/') . "/{$finalWebpName}";
                } else {
                    Log::error("HEIC to JPG failed. Command: {$command}. Output: " . implode(', ', $output));
                }
            } else {
                // সাধারণ ছবির জন্য (JPG, PNG)
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file->getRealPath());

                if ($width || $height) {
                    $image->scaleDown(width: $width, height: $height);
                }

                if ($forceWebp) {
                    $fileName = "{$timestamp}_{$slugName}.webp";
                    $encoded = $image->toWebp($quality);
                } else {
                    $fileName = "{$timestamp}_{$slugName}.{$extension}";
                    $encoded = match ($extension) {
                        'jpg', 'jpeg' => $image->toJpeg($quality),
                        'gif'         => $image->toGif(),
                        'png'         => $image->toPng(),
                        default       => $image->toWebp($quality),
                    };
                }

                $filePath = "{$directory}/{$fileName}";
                Storage::disk('public')->put($filePath, $encoded->toString());
                return $filePath;
            }
        } catch (\Exception $e) {
            Log::error("Image Upload Exception: " . $e->getMessage());
        }

        // ব্যাকআপ: সবকিছু ফেইল করলে অরিজিনাল ফাইল আপলোড
        $fileName = "{$timestamp}_{$slugName}.{$extension}";
        return $file->storeAs($directory, $fileName, 'public');
    }
}

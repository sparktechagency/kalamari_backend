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


    function imageUpload3($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return null;
        }

        // HEIC প্রসেসিং এর জন্য মেমোরি বাড়িয়ে নেওয়া
        ini_set('memory_limit', '512M');

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugName = Str::slug($originalName);
        $timestamp = time();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // HEIC শনাক্তকরণ
        $isHeic = in_array($extension, ['heic', 'heif']) || $mimeType === 'image/heic' || $mimeType === 'image/heif';

        if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/octet-stream' || $isHeic) {
            try {
                // ১. ড্রাইভার সেটআপ
                $manager = new ImageManager(new Driver());

                // ২. ফাইল রিড করা (getRealPath ব্যবহার করা হয়েছে যা HEIC এর জন্য নিরাপদ)
                $image = $manager->read($file->getRealPath());

                // ৩. রিসাইজিং
                if ($width || $height) {
                    $image->scaleDown(width: $width, height: $height);
                }

                // ৪. ফরম্যাট এবং এনকোডিং
                if ($forceWebp || $isHeic) {
                    $fileName = "{$timestamp}_{$slugName}.webp";
                    $encoded = $image->toWebp($quality);
                } else {
                    $fileName = "{$timestamp}_{$slugName}.{$extension}";
                    $encoded = match ($extension) {
                        'jpg', 'jpeg' => $image->toJpeg($quality),
                        'gif'         => $image->toGif(),
                        'png'         => $image->toPng(),
                        'webp'        => $image->toWebp($quality),
                        default       => $image->toJpeg($quality),
                    };
                }

                $filePath = "{$directory}/{$fileName}";

                // ৫. স্টোরেজে সেভ করা
                Storage::disk('public')->put($filePath, $encoded->toString());

                return $filePath;
            } catch (\Exception $e) {
                // এরর আসলে লগে ডিটেইল সেভ হবে
                Log::error("V3 Image Processing Error for {$originalName}: " . $e->getMessage());
            }
        }

        // প্রসেসিং ফেইল করলে সাধারণ ফাইল হিসেবে আপলোড
        $fileName = "{$timestamp}_{$slugName}.{$extension}";
        return $file->storeAs($directory, $fileName, 'public');
    }

    function imageUpload4($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return null;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugName = Str::slug($originalName);
        $timestamp = time();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // ১. HEIC শনাক্তকরণ
        $isHeic = in_array($extension, ['heic', 'heif']) || $mimeType === 'image/heic';

        try {
            if ($isHeic) {
                // HEIC হলে 'heif-convert' ব্যবহার করে সরাসরি WebP তে রূপান্তর
                $fileName = "{$timestamp}_{$slugName}.webp";
                $tempSource = $file->getRealPath();

                // সাময়িকভাবে ফাইল রাখার পাথ (storage/app/public/temp)
                $tempDir = storage_path("app/public/{$directory}");
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                $targetPath = $tempDir . '/' . $fileName;

                // কমান্ড রান করা (heif-convert -q quality source target)
                $command = "heif-convert -q {$quality} " . escapeshellarg($tempSource) . " " . escapeshellarg($targetPath) . " 2>&1";
                exec($command, $output, $returnVar);

                if ($returnVar === 0) {
                    // সাকসেস হলে রিসাইজিং এর জন্য চেক করা
                    if ($width || $height) {
                        $manager = new ImageManager(new Driver());
                        $image = $manager->read($targetPath);
                        $image->scaleDown(width: $width, height: $height);
                        $image->toWebp($quality)->save($targetPath);
                    }
                    return "{$directory}/{$fileName}";
                } else {
                    Log::error("heif-convert failed: " . implode("\n", $output));
                    // যদি কনভার্ট ফেইল করে তবে সাধারণ আপলোড হবে ক্যাচ ব্লকের বাইরে
                }
            } else {
                // ২. সাধারণ ইমেজ (JPG, PNG) প্রসেসিং
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
                        'webp'        => $image->toWebp($quality),
                        default       => $image->toJpeg($quality),
                    };
                }

                $filePath = "{$directory}/{$fileName}";
                Storage::disk('public')->put($filePath, $encoded->toString());
                return $filePath;
            }
        } catch (\Exception $e) {
            Log::error("Image Processing Error: " . $e->getMessage());
        }

        // ৩. সবকিছু ফেইল করলে বা ইমেজ না হলে সাধারণ ফাইল আপলোড
        $fileName = "{$timestamp}_{$slugName}.{$extension}";
        return $file->storeAs($directory, $fileName, 'public');
    }

    function imageUpload5($file, string $fieldName, string $directory, ?int $width = null, ?int $height = null, int $quality = 90, bool $forceWebp = false): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return null;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugName = Str::slug($originalName);
        $timestamp = time();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // HEIC শনাক্তকরণ
        $isHeic = in_array($extension, ['heic', 'heif']) || $mimeType === 'image/heic';

        try {
            if ($isHeic) {
                // ১. HEIC এর জন্য স্পেশাল কনভার্সন (Native heif-convert)
                $fileName = "{$timestamp}_{$slugName}.webp";
                $tempSource = $file->getRealPath();

                $publicPath = "app/public/{$directory}";
                if (!file_exists(storage_path($publicPath))) {
                    mkdir(storage_path($publicPath), 0755, true);
                }
                $targetPath = storage_path("{$publicPath}/{$fileName}");

                // Native Command: heif-convert -q 90 source.heic target.webp
                $command = "heif-convert -q {$quality} " . escapeshellarg($tempSource) . " " . escapeshellarg($targetPath) . " 2>&1";
                exec($command, $output, $returnVar);

                if ($returnVar === 0) {
                    // ২. যদি রিসাইজিং এর প্রয়োজন হয় (এখন এটি WebP ফাইল, তাই Imagick সহজেই পারবে)
                    if ($width || $height) {
                        $manager = new ImageManager(new Driver());
                        $image = $manager->read($targetPath);
                        $image->scaleDown(width: $width, height: $height);
                        $image->toWebp($quality)->save($targetPath);
                    }
                    return "{$directory}/{$fileName}";
                } else {
                    Log::error("heif-convert error: " . implode("\n", $output));
                }
            } else {
                // ৩. সাধারণ ছবির জন্য (JPG, PNG) Intervention Image V3
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
                        'webp'        => $image->toWebp($quality),
                        default       => $image->toJpeg($quality),
                    };
                }

                $filePath = "{$directory}/{$fileName}";
                Storage::disk('public')->put($filePath, $encoded->toString());
                return $filePath;
            }
        } catch (\Exception $e) {
            Log::error("Image Processing Failed: " . $e->getMessage());
        }

        // ব্যাকআপ: যদি কনভার্সন ফেইল করে তবে অরিজিনাল ফাইল আপলোড
        $fileName = "{$timestamp}_{$slugName}.{$extension}";
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

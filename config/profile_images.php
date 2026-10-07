<?php

declare(strict_types=1);

function gokaltara_profile_storage_base_path(): string
{
    $requestPath = parse_url(
        (string) ($_SERVER['REQUEST_URI'] ?? ''),
        PHP_URL_PATH
    );

    $script = str_replace(
        '\\',
        '/',
        is_string($requestPath) && $requestPath !== ''
            ? $requestPath
            : (string) ($_SERVER['SCRIPT_NAME'] ?? '')
    );

    $parts = array_values(
        array_filter(
            explode('/', trim($script, '/')),
            static function ($part): bool {
                return $part !== '';
            }
        )
    );

    if (!$parts) {
        return '';
    }

    $count = count($parts);

    if (
        $count >= 2 &&
        in_array($parts[$count - 2], ['admin', 'api'], true)
    ) {
        array_splice($parts, -2);
    } else {
        array_splice($parts, -1);
    }

    return $parts
        ? '/' . implode('/', $parts)
        : '';
}

function gokaltara_profile_image_url_direct(
    string $filename,
    bool $thumbnail = false
): string {
    $filename = trim($filename);

    if ($filename === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $filename)) {
        if (!$thumbnail) {
            return $filename;
        }

        $parsed = parse_url($filename);

        if (is_array($parsed)) {
            $host = (string) ($parsed['host'] ?? '');
            $path = (string) ($parsed['path'] ?? '');
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

            if ($host !== '' && $path !== '') {
                $segments = array_values(
                    array_filter(
                        explode('/', trim($path, '/')),
                        static function ($part): bool {
                            return $part !== '';
                        }
                    )
                );

                if ($segments) {
                    $blobFilename = array_pop($segments);
                    $segments[] = 'thumbs';
                    $segments[] = $blobFilename;

                    $scheme = (string) ($parsed['scheme'] ?? 'https');
                    $fragment = isset($parsed['fragment'])
                        ? '#' . $parsed['fragment']
                        : '';

                    return
                        $scheme . '://' .
                        $host . '/' .
                        implode('/', array_map('rawurlencode', $segments)) .
                        $query .
                        $fragment;
                }
            }
        }

        return $filename;
    }

    $safeName = basename(
        str_replace('\\', '/', $filename)
    );

    if (
        $safeName === '' ||
        !preg_match(
            '/\.(?:jpe?g|png|webp)$/i',
            $safeName
        )
    ) {
        return rtrim(
            gokaltara_profile_storage_base_path(),
            '/'
        ) . '/assets/images/no-image.jpg';
    }

    $profileDir = __DIR__ . '/../assets/images/profil';

    $basePath = rtrim(
        gokaltara_profile_storage_base_path(),
        '/'
    );

    $assetsPath = $basePath . '/assets/images/profil';

    $stem = pathinfo(
        $safeName,
        PATHINFO_FILENAME
    );

    if ($thumbnail) {
        $legacyThumb =
            $profileDir .
            '/thumbs/' .
            $stem .
            '.webp';

        if (is_file($legacyThumb)) {
            return
                $assetsPath .
                '/thumbs/' .
                rawurlencode($stem) .
                '.webp';
        }
    }

    $candidates = array_values(
        array_unique(
            [
                $safeName,
                $stem . '.webp'
            ]
        )
    );

    foreach ($candidates as $candidate) {
        if (
            is_file(
                $profileDir . '/' . $candidate
            )
        ) {
            return
                $assetsPath .
                '/' .
                rawurlencode($candidate);
        }
    }

    return
        rtrim($basePath, '/') .
        '/assets/images/no-image.jpg';
}

function gokaltara_save_profile_upload_direct(
    array $file,
    int $userId
): array {
    if (
        ($file['error'] ?? UPLOAD_ERR_NO_FILE)
        !== UPLOAD_ERR_OK
    ) {
        return [
            'success' => false,
            'message' => 'File foto profil gagal diunggah.'
        ];
    }

    $tmpName = (string) (
        $file['tmp_name'] ?? ''
    );

    $fileSize = (int) (
        $file['size'] ?? 0
    );

    if (
        $tmpName === '' ||
        !is_uploaded_file($tmpName)
    ) {
        return [
            'success' => false,
            'message' => 'File foto profil tidak valid.'
        ];
    }

    if (
        $fileSize <= 0 ||
        $fileSize > 3 * 1024 * 1024
    ) {
        return [
            'success' => false,
            'message' => 'Ukuran foto profil maksimal 3 MB.'
        ];
    }

    $imageInfo = @getimagesize($tmpName);

    if (!$imageInfo) {
        return [
            'success' => false,
            'message' => 'File yang dipilih bukan gambar yang valid.'
        ];
    }

    $mime = (string) (
        $imageInfo['mime'] ?? ''
    );

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($extensions[$mime])) {
        return [
            'success' => false,
            'message' => 'Format gambar harus JPG, PNG, atau WEBP.'
        ];
    }

    $width = (int) (
        $imageInfo[0] ?? 0
    );

    $height = (int) (
        $imageInfo[1] ?? 0
    );

    if (
        $width <= 0 ||
        $height <= 0 ||
        $width > 10000 ||
        $height > 10000 ||
        ($width * $height) > 50000000
    ) {
        return [
            'success' => false,
            'message' => 'Dimensi foto profil terlalu besar.'
        ];
    }

    $directory =
        __DIR__ .
        '/../assets/images/profil';

    if (
        !is_dir($directory) &&
        !mkdir($directory, 0755, true) &&
        !is_dir($directory)
    ) {
        return [
            'success' => false,
            'message' => 'Folder penyimpanan foto profil tidak dapat dibuat.'
        ];
    }

    try {
        $filename =
            'profil_' .
            $userId .
            '_' .
            date('YmdHis') .
            '_' .
            bin2hex(random_bytes(6)) .
            '.' .
            $extensions[$mime];
    } catch (Throwable $error) {
        return [
            'success' => false,
            'message' => 'Nama file foto profil gagal dibuat.'
        ];
    }

    $destination =
        $directory .
        '/' .
        $filename;

    if (
        !move_uploaded_file(
            $tmpName,
            $destination
        )
    ) {
        return [
            'success' => false,
            'message' => 'Foto profil gagal disimpan.'
        ];
    }

    return [
        'success' => true,
        'filename' => $filename
    ];
}

function gokaltara_delete_profile_image_direct(
    string $filename
): void {
    $filename = trim($filename);

    if (
        $filename === '' ||
        preg_match('#^https?://#i', $filename)
    ) {
        return;
    }

    $safeName = basename(
        str_replace('\\', '/', $filename)
    );

    if (
        !preg_match(
            '/\.(?:jpe?g|png|webp)$/i',
            $safeName
        )
    ) {
        return;
    }

    $profileDir =
        __DIR__ .
        '/../assets/images/profil';

    $fullPath =
        $profileDir .
        '/' .
        $safeName;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }

    $thumbPath =
        $profileDir .
        '/thumbs/' .
        pathinfo(
            $safeName,
            PATHINFO_FILENAME
        ) .
        '.webp';

    if (is_file($thumbPath)) {
        @unlink($thumbPath);
    }
}
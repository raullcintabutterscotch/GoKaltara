<?php

declare(strict_types=1);

require_once __DIR__ . '/image_optimizer.php';

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

    if (gokaltara_is_remote_image($filename)) {
        if (!$thumbnail) {
            return $filename;
        }

        $thumb = gokaltara_blob_thumbnail_url($filename);
        return $thumb !== '' ? $thumb : $filename;
    }

    $safeName = basename(str_replace('\\', '/', $filename));

    if (!preg_match('/\.(?:jpe?g|png|webp)$/i', $safeName)) {
        return rtrim(gokaltara_profile_storage_base_path(), '/') . '/assets/images/no-image.jpg';
    }

    $profileDir = __DIR__ . '/../assets/images/profil';
    $basePath = rtrim(gokaltara_profile_storage_base_path(), '/');
    $assetsPath = $basePath . '/assets/images/profil';
    $stem = pathinfo($safeName, PATHINFO_FILENAME);

    if ($thumbnail) {
        $thumbName = $stem . '.webp';
        if (is_file($profileDir . '/thumbs/' . $thumbName)) {
            return $assetsPath . '/thumbs/' . rawurlencode($thumbName);
        }
    }

    foreach (array_values(array_unique([$safeName, $stem . '.webp'])) as $candidate) {
        if (is_file($profileDir . '/' . $candidate)) {
            return $assetsPath . '/' . rawurlencode($candidate);
        }
    }

    return rtrim($basePath, '/') . '/assets/images/no-image.jpg';
}

function gokaltara_save_profile_upload_direct(
    array $file,
    int $userId
): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File foto profil gagal diunggah.'];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $fileSize = (int) ($file['size'] ?? 0);

    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return ['success' => false, 'message' => 'File foto profil tidak valid.'];
    }

    if ($fileSize <= 0 || $fileSize > 3 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Ukuran foto profil maksimal 3 MB.'];
    }

    $imageInfo = @getimagesize($tmpName);
    if (!$imageInfo) {
        return ['success' => false, 'message' => 'File yang dipilih bukan gambar yang valid.'];
    }

    $mime = (string) ($imageInfo['mime'] ?? '');
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        return ['success' => false, 'message' => 'Format foto harus JPG, PNG, atau WEBP.'];
    }

    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width <= 0 || $height <= 0 || $width > 10000 || $height > 10000 || ($width * $height) > 50000000) {
        return ['success' => false, 'message' => 'Dimensi foto profil terlalu besar.'];
    }

    try {
        $baseName = 'profil_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Nama file foto profil gagal dibuat.'];
    }

    $tempFull = tempnam(sys_get_temp_dir(), 'gkprof_');
    $tempThumb = tempnam(sys_get_temp_dir(), 'gkprof_');

    if ($tempFull === false || $tempThumb === false) {
        if (is_string($tempFull) && is_file($tempFull)) @unlink($tempFull);
        if (is_string($tempThumb) && is_file($tempThumb)) @unlink($tempThumb);
        return ['success' => false, 'message' => 'Penyimpanan sementara foto gagal.'];
    }

    $fullOk = gokaltara_create_resized_webp($tmpName, $tempFull, 600, 82);
    $thumbOk = gokaltara_create_resized_webp($tmpName, $tempThumb, 180, 78);

    if (!$fullOk || !$thumbOk) {
        @unlink($tempFull);
        @unlink($tempThumb);
        return ['success' => false, 'message' => 'Foto gagal diproses menjadi WebP. Pastikan GD PHP aktif.'];
    }

    if (gokaltara_blob_enabled()) {
        $remoteFull = gokaltara_blob_upload_file($tempFull, 'profil/' . $baseName . '.webp', 'image/webp');
        if (!$remoteFull['success']) {
            @unlink($tempFull);
            @unlink($tempThumb);
            return ['success' => false, 'message' => $remoteFull['message'] ?? 'Upload foto gagal.'];
        }

        $remoteThumb = gokaltara_blob_upload_file($tempThumb, 'profil/thumbs/' . $baseName . '.webp', 'image/webp');
        if (!$remoteThumb['success']) {
            gokaltara_blob_delete((string) ($remoteFull['url'] ?? ''));
            @unlink($tempFull);
            @unlink($tempThumb);
            return ['success' => false, 'message' => $remoteThumb['message'] ?? 'Upload thumbnail foto gagal.'];
        }

        @unlink($tempFull);
        @unlink($tempThumb);

        return [
            'success' => true,
            'filename' => (string) $remoteFull['url'],
            'thumbnail' => (string) ($remoteThumb['url'] ?? '')
        ];
    }

    $directory = __DIR__ . '/../assets/images/profil';
    $thumbDirectory = $directory . '/thumbs';

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        @unlink($tempFull); @unlink($tempThumb);
        return ['success' => false, 'message' => 'Folder foto profil tidak dapat dibuat.'];
    }

    if (!is_dir($thumbDirectory) && !mkdir($thumbDirectory, 0755, true) && !is_dir($thumbDirectory)) {
        @unlink($tempFull); @unlink($tempThumb);
        return ['success' => false, 'message' => 'Folder thumbnail profil tidak dapat dibuat.'];
    }

    $filename = $baseName . '.webp';
    $destination = $directory . '/' . $filename;
    $thumbDestination = $thumbDirectory . '/' . $filename;

    if (!rename($tempFull, $destination) || !rename($tempThumb, $thumbDestination)) {
        if (is_file($destination)) @unlink($destination);
        if (is_file($thumbDestination)) @unlink($thumbDestination);
        if (is_file($tempFull)) @unlink($tempFull);
        if (is_file($tempThumb)) @unlink($tempThumb);
        return ['success' => false, 'message' => 'Foto profil gagal disimpan.'];
    }

    return [
        'success' => true,
        'filename' => $filename,
        'thumbnail' => $filename
    ];
}

function gokaltara_delete_profile_image_direct(
    string $filename
): void {
    $filename = trim($filename);

    if ($filename === '') {
        return;
    }

    if (gokaltara_is_remote_image($filename)) {
        gokaltara_blob_delete($filename);

        $thumb = gokaltara_blob_thumbnail_url($filename);
        if ($thumb !== '') {
            gokaltara_blob_delete($thumb);
        }

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
<?php

declare(strict_types=1);

/**
 * Profile photos deliberately bypass the food image optimizer. Cropper output
 * is saved as-is so the database always points to the same file that the UI
 * previews and later pages render.
 */
function gokaltara_profile_storage_base_path(): string
{
    $request_path = parse_url(
        (string) ($_SERVER['REQUEST_URI'] ?? ''),
        PHP_URL_PATH
    );
    $script = str_replace(
        '\\',
        '/',
        is_string($request_path) && $request_path !== ''
            ? $request_path
            : (string) ($_SERVER['SCRIPT_NAME'] ?? '')
    );
    $parts = array_values(array_filter(explode('/', trim($script, '/')), static function ($part): bool {
        return $part !== '';
    }));

    if (!$parts) {
        return '';
    }

    $count = count($parts);
    if ($count >= 2 && in_array($parts[$count - 2], ['admin', 'api'], true)) {
        array_splice($parts, -2);
    } else {
        array_splice($parts, -1);
    }

    return $parts ? '/' . implode('/', $parts) : '';
}

function gokaltara_profile_image_url_direct(string $filename, bool $thumbnail = false): string
{
    $filename = trim($filename);
    if ($filename === '') {
        return '';
    }

    // Preserve existing remote profile URLs without asking the optimizer for
    // a thumbnail. New profile uploads are always stored locally as raw files.
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }

    $safe_name = basename(str_replace('\\', '/', $filename));
    if ($safe_name === '' || !preg_match('/\.(?:jpe?g|png|webp)$/i', $safe_name)) {
        return rtrim(gokaltara_profile_storage_base_path(), '/') . '/assets/images/no-image.jpg';
    }

    $profile_dir = __DIR__ . '/../assets/images/profil';
    $base_path = rtrim(gokaltara_profile_storage_base_path(), '/');
    $assets_path = $base_path . '/assets/images/profil';
    $stem = pathinfo($safe_name, PATHINFO_FILENAME);

    if ($thumbnail) {
        $legacy_thumb = $profile_dir . '/thumbs/' . $stem . '.webp';
        if (is_file($legacy_thumb)) {
            return $assets_path . '/thumbs/' . rawurlencode($stem) . '.webp';
        }
    }

    // Support both the new raw image and legacy optimizer-created WebP files.
    $candidates = array_values(array_unique([
        $safe_name,
        $stem . '.webp'
    ]));

    foreach ($candidates as $candidate) {
        if (is_file($profile_dir . '/' . $candidate)) {
            return $assets_path . '/' . rawurlencode($candidate);
        }
    }

    return rtrim($base_path, '/') . '/assets/images/no-image.jpg';
}

function gokaltara_save_profile_upload_direct(array $file, int $user_id): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File foto profil gagal diunggah.'];
    }

    $tmp_name = (string) ($file['tmp_name'] ?? '');
    $file_size = (int) ($file['size'] ?? 0);
    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        return ['success' => false, 'message' => 'File foto profil tidak valid.'];
    }
    if ($file_size <= 0 || $file_size > 3 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Ukuran foto profil maksimal 3 MB.'];
    }

    $image_info = @getimagesize($tmp_name);
    if (!$image_info) {
        return ['success' => false, 'message' => 'File yang dipilih bukan gambar yang valid.'];
    }

    $mime = (string) ($image_info['mime'] ?? '');
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    if (!isset($extensions[$mime])) {
        return ['success' => false, 'message' => 'Format gambar harus JPG, PNG, atau WEBP.'];
    }

    $width = (int) ($image_info[0] ?? 0);
    $height = (int) ($image_info[1] ?? 0);
    if (
        $width <= 0 || $height <= 0 ||
        $width > 10000 || $height > 10000 ||
        ($width * $height) > 50000000
    ) {
        return ['success' => false, 'message' => 'Dimensi foto profil terlalu besar.'];
    }

    $directory = __DIR__ . '/../assets/images/profil';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['success' => false, 'message' => 'Folder penyimpanan foto profil tidak dapat dibuat.'];
    }

    try {
        $filename = 'profil_' . $user_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
    } catch (Throwable $error) {
        return ['success' => false, 'message' => 'Nama file foto profil gagal dibuat.'];
    }

    $destination = $directory . '/' . $filename;
    if (!move_uploaded_file($tmp_name, $destination)) {
        return ['success' => false, 'message' => 'Foto profil gagal disimpan.'];
    }

    return ['success' => true, 'filename' => $filename];
}

function gokaltara_delete_profile_image_direct(string $filename): void
{
    $filename = trim($filename);
    if ($filename === '' || preg_match('#^https?://#i', $filename)) {
        return;
    }

    $safe_name = basename(str_replace('\\', '/', $filename));
    if (!preg_match('/\.(?:jpe?g|png|webp)$/i', $safe_name)) {
        return;
    }

    $profile_dir = __DIR__ . '/../assets/images/profil';
    $full_path = $profile_dir . '/' . $safe_name;
    if (is_file($full_path)) {
        @unlink($full_path);
    }

    // Clean up only legacy optimizer thumbnails for the same local filename.
    $thumb_path = $profile_dir . '/thumbs/' . pathinfo($safe_name, PATHINFO_FILENAME) . '.webp';
    if (is_file($thumb_path)) {
        @unlink($thumb_path);
    }
}


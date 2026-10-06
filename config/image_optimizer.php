<?php

declare(strict_types=1);

function gokaltara_image_extension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function gokaltara_image_stem(string $filename): string
{
    return pathinfo(basename($filename), PATHINFO_FILENAME);
}

function gokaltara_app_base_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $script = '/' . trim($script, '/');

    if ($script === '/') {
        return '';
    }

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

    if ($count >= 2 && in_array($parts[$count - 2], ['admin', 'api'], true)) {
        array_splice($parts, -2);
    } else {
        array_splice($parts, -1);
    }

    return $parts
        ? '/' . implode('/', $parts)
        : '';
}

function gokaltara_is_remote_image(string $filename): bool
{
    return (bool) preg_match(
        '#^https?://#i',
        trim($filename)
    );
}

function gokaltara_blob_token(): string
{
    return trim((string) ($_ENV['BLOB_READ_WRITE_TOKEN'] ?? getenv('BLOB_READ_WRITE_TOKEN') ?: ''));
}

function gokaltara_blob_enabled(): bool
{
    return gokaltara_blob_token() !== '';
}

function gokaltara_blob_http_request(
    string $method,
    string $url,
    ?string $body = null,
    string $content_type = '',
    array $extra_headers = []
): array {
    $token = gokaltara_blob_token();

    if ($token === '') {
        return [
            'success' => false,
            'status' => 0,
            'body' => '',
            'message' => 'BLOB_READ_WRITE_TOKEN belum dikonfigurasi.'
        ];
    }

    $store_id = '';
    $token_parts = explode('_', $token);

    if (count($token_parts) >= 4) {
        $store_id = (string) $token_parts[3];
    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'x-api-version: 12',
        'x-api-blob-request-id: ' . bin2hex(random_bytes(12))
    ];

    if ($store_id !== '') {
        $headers[] = 'x-vercel-blob-store-id: ' . $store_id;
    }

    if ($content_type !== '') {
        $headers[] = 'x-content-type: ' . $content_type;
    }

    foreach ($extra_headers as $name => $value) {
        $headers[] = $name . ': ' . $value;
    }

    if ($body !== null) {
        $headers[] = 'Content-Length: ' . strlen($body);
    }

    $context = stream_context_create([
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'ignore_errors' => true,
            'timeout' => 30
        ]
    ]);

    $response_body = @file_get_contents($url, false, $context);
    $response_body = $response_body === false ? '' : $response_body;

    $status = 0;
    $response_headers = $http_response_header ?? [];

    foreach ($response_headers as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    return [
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $response_body,
        'headers' => $response_headers
    ];
}

function gokaltara_blob_upload_file(
    string $file_path,
    string $pathname,
    string $content_type = 'image/webp'
): array {
    if (!is_file($file_path)) {
        return [
            'success' => false,
            'message' => 'File upload tidak ditemukan.'
        ];
    }

    $body = @file_get_contents($file_path);

    if ($body === false) {
        return [
            'success' => false,
            'message' => 'File upload tidak dapat dibaca.'
        ];
    }

    $base_api = trim((string) ($_ENV['VERCEL_BLOB_API_URL'] ?? getenv('VERCEL_BLOB_API_URL') ?: ''));

    if ($base_api === '') {
        $base_api = 'https://vercel.com/api/blob';
    }

    $url = rtrim($base_api, '/') . '?' . http_build_query(
        ['pathname' => $pathname],
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    $response = gokaltara_blob_http_request(
        'PUT',
        $url,
        $body,
        $content_type,
        [
            'x-cache-control-max-age' => '31536000'
        ]
    );

    if (!$response['success']) {
        $detail = trim((string) ($response['body'] ?? ''));

        return [
            'success' => false,
            'message' => 'Upload ke Vercel Blob gagal.' . (
                $detail !== ''
                    ? ' ' . mb_substr($detail, 0, 300)
                    : ''
            )
        ];
    }

    $data = json_decode(
        (string) ($response['body'] ?? ''),
        true
    );

    $blob_url = trim((string) ($data['url'] ?? ''));

    if ($blob_url === '') {
        return [
            'success' => false,
            'message' => 'Vercel Blob tidak mengembalikan URL gambar.'
        ];
    }

    return [
        'success' => true,
        'url' => $blob_url,
        'pathname' => (string) ($data['pathname'] ?? $pathname)
    ];
}

function gokaltara_blob_delete(string $url): bool {
    if (!gokaltara_blob_enabled() || !gokaltara_is_remote_image($url)) {
        return false;
    }

    $base_api = trim((string) ($_ENV['VERCEL_BLOB_API_URL'] ?? getenv('VERCEL_BLOB_API_URL') ?: ''));

    if ($base_api === '') {
        $base_api = 'https://vercel.com/api/blob';
    }

    $api_url = rtrim($base_api, '/') . '/delete';

    $body = json_encode(
        ['urls' => [$url]],
        JSON_UNESCAPED_SLASHES
    );

    $response = gokaltara_blob_http_request(
        'POST',
        $api_url,
        $body === false ? '{}' : $body,
        '',
        [
            'Content-Type' => 'application/json'
        ]
    );

    return $response['success'];
}

function gokaltara_blob_thumbnail_url(string $url): string
{
    if (!gokaltara_is_remote_image($url)) {
        return '';
    }

    $parsed = parse_url($url);

    if (!is_array($parsed)) {
        return '';
    }

    $host = $parsed['host'] ?? '';
    $path = $parsed['path'] ?? '';

    if ($host === '' || $path === '') {
        return '';
    }

    $segments = array_values(
        array_filter(
            explode('/', trim($path, '/')),
            static function ($part): bool {
                return $part !== '';
            }
        )
    );

    if (!$segments) {
        return '';
    }

    $filename = array_pop($segments);
    $segments[] = 'thumbs';
    $segments[] = $filename;

    $scheme = $parsed['scheme'] ?? 'https';

    return $scheme . '://' . $host . '/' . implode('/', array_map('rawurlencode', $segments));
}

function gokaltara_image_url(
    string $filename,
    string $base_url = '',
    bool $thumbnail = false
): string {
    $filename = trim($filename);

    if ($filename === '') {
        return rtrim(
            $base_url !== '' ? $base_url : gokaltara_app_base_path() . '/assets/images',
            '/'
        ) . '/no-image.jpg';
    }

    if (gokaltara_is_remote_image($filename)) {
        if (!$thumbnail) {
            return $filename;
        }

        $thumb_url = gokaltara_blob_thumbnail_url($filename);

        return $thumb_url !== '' ? $thumb_url : $filename;
    }

    $filename = basename($filename);

    $base_path = rtrim(
        $base_url !== '' ? $base_url : gokaltara_app_base_path(),
        '/'
    );

    $assets_path = $base_path . '/assets/images';
    $root = dirname(__DIR__) . '/assets/images';
    $stem = gokaltara_image_stem($filename);

    if ($thumbnail) {
        $thumb_file = $root . '/thumbs/' . $stem . '.webp';

        if (is_file($thumb_file)) {
            return $assets_path . '/thumbs/' . rawurlencode($stem) . '.webp';
        }
    }

    $extension = gokaltara_image_extension($filename);
    $webp_file = $root . '/' . $stem . '.webp';

    if ($extension !== 'webp' && is_file($webp_file)) {
        return $assets_path . '/' . rawurlencode($stem) . '.webp';
    }

    $original_file = $root . '/' . $filename;

    if (is_file($original_file)) {
        return $assets_path . '/' . rawurlencode($filename);
    }

    return $assets_path . '/no-image.jpg';
}

function gokaltara_profile_image_url(
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

        $thumb_url = gokaltara_blob_thumbnail_url($filename);

        return $thumb_url !== '' ? $thumb_url : $filename;
    }

    $filename = basename($filename);
    $base_path = rtrim(gokaltara_app_base_path(), '/');
    $assets_path = $base_path . '/assets/images/profil';
    $root = dirname(__DIR__) . '/assets/images/profil';
    $stem = gokaltara_image_stem($filename);

    if ($thumbnail) {
        $thumb_file = $root . '/thumbs/' . $stem . '.webp';

        if (is_file($thumb_file)) {
            return $assets_path . '/thumbs/' . rawurlencode($stem) . '.webp';
        }
    }

    $extension = gokaltara_image_extension($filename);
    $webp_file = $root . '/' . $stem . '.webp';

    if ($extension !== 'webp' && is_file($webp_file)) {
        return $assets_path . '/' . rawurlencode($stem) . '.webp';
    }

    $original_file = $root . '/' . $filename;

    if (is_file($original_file)) {
        return $assets_path . '/' . rawurlencode($filename);
    }

    return '';
}

function gokaltara_create_resized_webp(
    string $source_path,
    string $destination_path,
    int $max_dimension,
    int $quality
): bool {
    if (!extension_loaded('gd')) {
        return false;
    }

    $image_info = @getimagesize($source_path);

    if (!$image_info) {
        return false;
    }

    $mime = $image_info['mime'] ?? '';

    $source = @imagecreatefromstring(
        (string) file_get_contents($source_path)
    );

    if (!$source) {
        return false;
    }

    $source_width = imagesx($source);
    $source_height = imagesy($source);

    if ($source_width <= 0 || $source_height <= 0) {
        imagedestroy($source);
        return false;
    }

    if (
        $mime === 'image/jpeg' &&
        function_exists('exif_read_data')
    ) {
        $exif = @exif_read_data($source_path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if ($orientation === 3) {
            $source = imagerotate($source, 180, 0);
        } elseif ($orientation === 6) {
            $source = imagerotate($source, -90, 0);
        } elseif ($orientation === 8) {
            $source = imagerotate($source, 90, 0);
        }

        $source_width = imagesx($source);
        $source_height = imagesy($source);
    }

    $scale = min(
        1,
        $max_dimension / max($source_width, $source_height)
    );

    $target_width = max(
        1,
        (int) round($source_width * $scale)
    );

    $target_height = max(
        1,
        (int) round($source_height * $scale)
    );

    $canvas = imagecreatetruecolor(
        $target_width,
        $target_height
    );

    if (!$canvas) {
        imagedestroy($source);
        return false;
    }

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    $transparent = imagecolorallocatealpha(
        $canvas,
        0,
        0,
        0,
        127
    );

    imagefilledrectangle(
        $canvas,
        0,
        0,
        $target_width,
        $target_height,
        $transparent
    );

    imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        0,
        0,
        $target_width,
        $target_height,
        $source_width,
        $source_height
    );

    $directory = dirname($destination_path);

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        imagedestroy($canvas);
        imagedestroy($source);
        return false;
    }

    $success = imagewebp(
        $canvas,
        $destination_path,
        $quality
    );

    imagedestroy($canvas);
    imagedestroy($source);

    return $success && is_file($destination_path);
}

function gokaltara_process_upload(
    array $file,
    string $target_dir,
    string $prefix,
    int $input_max_bytes,
    int $full_max_dimension,
    int $thumb_max_dimension,
    int $full_quality = 82,
    int $thumb_quality = 78
): array {
    if (!extension_loaded('gd')) {
        return [
            'success' => false,
            'message' => 'GD PHP belum aktif pada server.'
        ];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File gambar gagal diunggah.'
        ];
    }

    $tmp_name = (string) ($file['tmp_name'] ?? '');
    $file_size = (int) ($file['size'] ?? 0);

    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        return [
            'success' => false,
            'message' => 'File gambar tidak valid.'
        ];
    }

    if ($file_size <= 0 || $file_size > $input_max_bytes) {
        return [
            'success' => false,
            'message' => 'Ukuran gambar melebihi batas yang diizinkan.'
        ];
    }

    $image_info = @getimagesize($tmp_name);

    if (!$image_info) {
        return [
            'success' => false,
            'message' => 'File yang dipilih bukan gambar yang valid.'
        ];
    }

    $mime = (string) ($image_info['mime'] ?? '');
    $allowed_mimes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!in_array($mime, $allowed_mimes, true)) {
        return [
            'success' => false,
            'message' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.'
        ];
    }

    $width = (int) ($image_info[0] ?? 0);
    $height = (int) ($image_info[1] ?? 0);

    if (
        $width <= 0 ||
        $height <= 0 ||
        $width > 10000 ||
        $height > 10000 ||
        ($width * $height) > 50000000
    ) {
        return [
            'success' => false,
            'message' => 'Dimensi gambar terlalu besar.'
        ];
    }

    if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
        return [
            'success' => false,
            'message' => 'Folder penyimpanan gambar tidak dapat dibuat.'
        ];
    }

    $thumb_dir = rtrim($target_dir, '/\\') . '/thumbs';

    if (!is_dir($thumb_dir) && !mkdir($thumb_dir, 0755, true)) {
        return [
            'success' => false,
            'message' => 'Folder thumbnail tidak dapat dibuat.'
        ];
    }

    $base_name =
        $prefix . '_' .
        time() . '_' .
        bin2hex(random_bytes(6));

    $filename = $base_name . '.webp';
    $thumb_filename = $base_name . '.webp';

    $full_path =
        rtrim($target_dir, '/\\') . '/' .
        $filename;

    $thumb_path =
        $thumb_dir . '/' .
        $thumb_filename;

    $full_success = gokaltara_create_resized_webp(
        $tmp_name,
        $full_path,
        $full_max_dimension,
        $full_quality
    );

    if (!$full_success) {
        if (is_file($full_path)) {
            unlink($full_path);
        }

        return [
            'success' => false,
            'message' => 'Gambar gagal diproses menjadi WebP.'
        ];
    }

    $thumb_success = gokaltara_create_resized_webp(
        $tmp_name,
        $thumb_path,
        $thumb_max_dimension,
        $thumb_quality
    );

    if (!$thumb_success) {
        if (is_file($full_path)) {
            unlink($full_path);
        }

        if (is_file($thumb_path)) {
            unlink($thumb_path);
        }

        return [
            'success' => false,
            'message' => 'Thumbnail gambar gagal dibuat.'
        ];
    }

    $result = [
        'success' => true,
        'filename' => $filename,
        'thumbnail' => $thumb_filename,
        'full_path' => $full_path,
        'thumbnail_path' => $thumb_path,
        'width' => $width,
        'height' => $height,
        'remote' => false
    ];

    if (!gokaltara_blob_enabled()) {
        return $result;
    }

    $target_name = strtolower(basename(rtrim($target_dir, '/\\')));
    $blob_folder = $target_name === 'profil' ? 'profil' : 'kuliner';

    $remote_full = gokaltara_blob_upload_file(
        $full_path,
        $blob_folder . '/' . $filename,
        'image/webp'
    );

    if (!$remote_full['success']) {
        if (is_file($full_path)) {
            unlink($full_path);
        }

        if (is_file($thumb_path)) {
            unlink($thumb_path);
        }

        return [
            'success' => false,
            'message' => $remote_full['message']
        ];
    }

    $remote_thumb = gokaltara_blob_upload_file(
        $thumb_path,
        $blob_folder . '/thumbs/' . $thumb_filename,
        'image/webp'
    );

    if (!$remote_thumb['success']) {
        gokaltara_blob_delete((string) ($remote_full['url'] ?? ''));

        if (is_file($full_path)) {
            unlink($full_path);
        }

        if (is_file($thumb_path)) {
            unlink($thumb_path);
        }

        return [
            'success' => false,
            'message' => $remote_thumb['message']
        ];
    }

    if (is_file($full_path)) {
        unlink($full_path);
    }

    if (is_file($thumb_path)) {
        unlink($thumb_path);
    }

    return [
        'success' => true,
        'filename' => (string) $remote_full['url'],
        'thumbnail' => (string) $remote_thumb['url'],
        'full_path' => '',
        'thumbnail_path' => '',
        'width' => $width,
        'height' => $height,
        'remote' => true
    ];
}

function gokaltara_delete_optimized_image(
    string $filename,
    string $target_dir
): void {
    $filename = trim($filename);

    if ($filename === '') {
        return;
    }

    if (gokaltara_is_remote_image($filename)) {
        gokaltara_blob_delete($filename);

        $thumb_url = gokaltara_blob_thumbnail_url($filename);

        if ($thumb_url !== '') {
            gokaltara_blob_delete($thumb_url);
        }

        return;
    }

    $filename = basename($filename);
    $target_dir = rtrim($target_dir, '/\\');

    $full_path = $target_dir . '/' . $filename;

    $thumb_path =
        $target_dir .
        '/thumbs/' .
        gokaltara_image_stem($filename) .
        '.webp';

    if (is_file($full_path)) {
        unlink($full_path);
    }

    if (is_file($thumb_path)) {
        unlink($thumb_path);
    }
}

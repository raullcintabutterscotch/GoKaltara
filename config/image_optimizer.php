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

function gokaltara_image_url(
    string $filename,
    string $base_url = "assets/images/",
    bool $thumbnail = false
): string {
    $filename = basename(trim($filename));

    if ($filename === "") {
        return rtrim($base_url, "/") . "/no-image.jpg";
    }

    $root = dirname(__DIR__) . "/assets/images";
    $stem = gokaltara_image_stem($filename);

    if ($thumbnail) {
        $thumb_file = $root . "/thumbs/" . $stem . ".webp";

        if (is_file($thumb_file)) {
            return rtrim($base_url, "/") . "/thumbs/" . rawurlencode($stem) . ".webp";
        }
    }

    $extension = gokaltara_image_extension($filename);
    $webp_file = $root . "/" . $stem . ".webp";

    if ($extension !== "webp" && is_file($webp_file)) {
        return rtrim($base_url, "/") . "/" . rawurlencode($stem) . ".webp";
    }

    $original_file = $root . "/" . $filename;

    if (is_file($original_file)) {
        return rtrim($base_url, "/") . "/" . rawurlencode($filename);
    }

    return rtrim($base_url, "/") . "/no-image.jpg";
}

function gokaltara_profile_image_url(
    string $filename,
    bool $thumbnail = false
): string {
    $filename = basename(trim($filename));

    if ($filename === "") {
        return "";
    }

    $root = dirname(__DIR__) . "/assets/images/profil";
    $stem = gokaltara_image_stem($filename);

    if ($thumbnail) {
        $thumb_file = $root . "/thumbs/" . $stem . ".webp";

        if (is_file($thumb_file)) {
            return "assets/images/profil/thumbs/" . rawurlencode($stem) . ".webp";
        }
    }

    $extension = gokaltara_image_extension($filename);
    $webp_file = $root . "/" . $stem . ".webp";

    if ($extension !== "webp" && is_file($webp_file)) {
        return "assets/images/profil/" . rawurlencode($stem) . ".webp";
    }

    $original_file = $root . "/" . $filename;

    if (is_file($original_file)) {
        return "assets/images/profil/" . rawurlencode($filename);
    }

    return "";
}

function gokaltara_create_resized_webp(
    string $source_path,
    string $destination_path,
    int $max_dimension,
    int $quality
): bool {
    if (!extension_loaded("gd")) {
        return false;
    }

    $image_info = @getimagesize($source_path);

    if (!$image_info) {
        return false;
    }

    $mime = $image_info["mime"] ?? "";

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
        $mime === "image/jpeg" &&
        function_exists("exif_read_data")
    ) {
        $exif = @exif_read_data($source_path);
        $orientation = (int) ($exif["Orientation"] ?? 1);

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
    if (!extension_loaded("gd")) {
        return [
            "success" => false,
            "message" => "GD PHP belum aktif pada server."
        ];
    }

    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [
            "success" => false,
            "message" => "File gambar gagal diunggah."
        ];
    }

    $tmp_name = (string) ($file["tmp_name"] ?? "");
    $file_size = (int) ($file["size"] ?? 0);

    if ($tmp_name === "" || !is_uploaded_file($tmp_name)) {
        return [
            "success" => false,
            "message" => "File gambar tidak valid."
        ];
    }

    if ($file_size <= 0 || $file_size > $input_max_bytes) {
        return [
            "success" => false,
            "message" => "Ukuran gambar melebihi batas yang diizinkan."
        ];
    }

    $image_info = @getimagesize($tmp_name);

    if (!$image_info) {
        return [
            "success" => false,
            "message" => "File yang dipilih bukan gambar yang valid."
        ];
    }

    $mime = (string) ($image_info["mime"] ?? "");
    $allowed_mimes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!in_array($mime, $allowed_mimes, true)) {
        return [
            "success" => false,
            "message" => "Format gambar harus JPG, JPEG, PNG, atau WEBP."
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
            "success" => false,
            "message" => "Dimensi gambar terlalu besar."
        ];
    }

    if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
        return [
            "success" => false,
            "message" => "Folder penyimpanan gambar tidak dapat dibuat."
        ];
    }

    $thumb_dir = rtrim($target_dir, "/\\") . "/thumbs";

    if (!is_dir($thumb_dir) && !mkdir($thumb_dir, 0755, true)) {
        return [
            "success" => false,
            "message" => "Folder thumbnail tidak dapat dibuat."
        ];
    }

    $base_name =
        $prefix . "_" .
        time() . "_" .
        bin2hex(random_bytes(6));

    $filename = $base_name . ".webp";
    $thumb_filename = $base_name . ".webp";

    $full_path =
        rtrim($target_dir, "/\\") . "/" .
        $filename;

    $thumb_path =
        $thumb_dir . "/" .
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
            "success" => false,
            "message" => "Gambar gagal diproses menjadi WebP."
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
            "success" => false,
            "message" => "Thumbnail gambar gagal dibuat."
        ];
    }

    return [
        "success" => true,
        "filename" => $filename,
        "thumbnail" => $thumb_filename,
        "full_path" => $full_path,
        "thumbnail_path" => $thumb_path,
        "width" => $width,
        "height" => $height
    ];
}

function gokaltara_delete_optimized_image(
    string $filename,
    string $target_dir
): void {
    $filename = basename(trim($filename));

    if ($filename === "") {
        return;
    }

    $target_dir = rtrim($target_dir, "/\\");

    $full_path = $target_dir . "/" . $filename;
    $thumb_path =
        $target_dir .
        "/thumbs/" .
        gokaltara_image_stem($filename) .
        ".webp";

    if (is_file($full_path)) {
        unlink($full_path);
    }

    if (is_file($thumb_path)) {
        unlink($thumb_path);
    }
}

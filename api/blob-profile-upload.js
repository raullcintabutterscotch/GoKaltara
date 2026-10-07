import { put } from "@vercel/blob";
import crypto from "node:crypto";

export const config = {
    api: {
        bodyParser: false
    }
};

function readBody(req) {
    return new Promise((resolve, reject) => {
        const chunks = [];

        req.on("data", chunk => {
            chunks.push(Buffer.from(chunk));
        });

        req.on("end", () => {
            resolve(Buffer.concat(chunks));
        });

        req.on("error", reject);
    });
}

function validSignature(userId, timestamp, fileName, signature) {
    const secret = process.env.BLOB_UPLOAD_SECRET || "";

    if (!secret || !signature) {
        return false;
    }

    const now = Math.floor(Date.now() / 1000);
    const requestTime = Number(timestamp);

    if (!Number.isFinite(requestTime)) {
        return false;
    }

    if (Math.abs(now - requestTime) > 300) {
        return false;
    }

    const payload = `${userId}|${timestamp}|${fileName}`;

    const expected = crypto
        .createHmac("sha256", secret)
        .update(payload)
        .digest("hex");

    if (expected.length !== signature.length) {
        return false;
    }

    return crypto.timingSafeEqual(
        Buffer.from(expected),
        Buffer.from(signature)
    );
}

function detectImage(buffer) {
    if (
        buffer.length >= 3 &&
        buffer[0] === 0xff &&
        buffer[1] === 0xd8 &&
        buffer[2] === 0xff
    ) {
        return {
            mime: "image/jpeg",
            extension: "jpg"
        };
    }

    if (
        buffer.length >= 8 &&
        buffer[0] === 0x89 &&
        buffer[1] === 0x50 &&
        buffer[2] === 0x4e &&
        buffer[3] === 0x47 &&
        buffer[4] === 0x0d &&
        buffer[5] === 0x0a &&
        buffer[6] === 0x1a &&
        buffer[7] === 0x0a
    ) {
        return {
            mime: "image/png",
            extension: "png"
        };
    }

    if (
        buffer.length >= 12 &&
        buffer.toString("ascii", 0, 4) === "RIFF" &&
        buffer.toString("ascii", 8, 12) === "WEBP"
    ) {
        return {
            mime: "image/webp",
            extension: "webp"
        };
    }

    return null;
}

export default async function handler(req, res) {
    res.setHeader(
        "Cache-Control",
        "no-store, no-cache, must-revalidate, max-age=0"
    );

    if (req.method !== "POST") {
        return res.status(405).json({
            success: false,
            message: "Method tidak diizinkan."
        });
    }

    const userId = String(req.headers["x-user-id"] || "");
    const timestamp = String(req.headers["x-timestamp"] || "");
    const fileName = String(req.headers["x-file-name"] || "profile");
    const signature = String(req.headers["x-signature"] || "");

    if (!userId || !timestamp || !fileName || !signature) {
        return res.status(400).json({
            success: false,
            message: "Data upload tidak lengkap."
        });
    }

    if (
        !validSignature(
            userId,
            timestamp,
            fileName,
            signature
        )
    ) {
        return res.status(403).json({
            success: false,
            message: "Signature upload tidak valid."
        });
    }

    try {
        const buffer = await readBody(req);

        if (!buffer.length) {
            return res.status(400).json({
                success: false,
                message: "File kosong."
            });
        }

        if (buffer.length > 4 * 1024 * 1024) {
            return res.status(413).json({
                success: false,
                message: "Ukuran file terlalu besar."
            });
        }

        const image = detectImage(buffer);

        if (!image) {
            return res.status(400).json({
                success: false,
                message: "File harus berupa JPG, PNG, atau WEBP."
            });
        }

        const pathname =
            `profiles/user-${userId}/profile-${Date.now()}.${image.extension}`;

        const blob = await put(
            pathname,
            buffer,
            {
                access: "public",
                addRandomSuffix: true,
                contentType: image.mime,
                cacheControlMaxAge: 31536000
            }
        );

        return res.status(200).json({
            success: true,
            url: blob.url,
            pathname: blob.pathname
        });
    } catch (error) {
        return res.status(500).json({
            success: false,
            message: "Upload ke Vercel Blob gagal."
        });
    }
}
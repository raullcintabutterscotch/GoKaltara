import { put } from "@vercel/blob";
import crypto from "node:crypto";

function getRequestBody(req) {
    if (req.body && typeof req.body === "object") {
        return req.body;
    }

    return new Promise((resolve, reject) => {
        let body = "";

        req.on("data", chunk => {
            body += chunk;
        });

        req.on("end", () => {
            try {
                resolve(JSON.parse(body));
            } catch {
                reject(new Error("JSON tidak valid."));
            }
        });

        req.on("error", reject);
    });
}

function createSignature(userId, bucket, secret) {
    return crypto
        .createHmac("sha256", secret)
        .update(`${userId}|${bucket}`)
        .digest("hex");
}

function verifySignature(userId, token) {
    const secret = process.env.BLOB_UPLOAD_SECRET || "";

    if (!secret || !token) {
        return false;
    }

    const currentBucket = Math.floor(Date.now() / 300000);

    for (const bucket of [currentBucket, currentBucket - 1]) {
        const expected = createSignature(
            userId,
            bucket,
            secret
        );

        const a = Buffer.from(expected);
        const b = Buffer.from(token);

        if (
            a.length === b.length &&
            crypto.timingSafeEqual(a, b)
        ) {
            return true;
        }
    }

    return false;
}

export default async function handler(req, res) {
    if (req.method !== "POST") {
        return res.status(405).json({
            success: false,
            message: "Method tidak diizinkan."
        });
    }

    try {
        const body = await getRequestBody(req);

        const userId = Number(body.userId || 0);
        const token = String(body.token || "");
        const dataUrl = String(body.dataUrl || "");

        if (!userId) {
            return res.status(400).json({
                success: false,
                message: "User tidak valid."
            });
        }

        if (!verifySignature(userId, token)) {
            return res.status(403).json({
                success: false,
                message: "Token upload tidak valid."
            });
        }

        const match = dataUrl.match(
            /^data:(image\/(?:jpeg|png|webp));base64,(.+)$/i
        );

        if (!match) {
            return res.status(400).json({
                success: false,
                message: "Format gambar tidak valid."
            });
        }

        const contentType = match[1].toLowerCase();
        const base64 = match[2];

        const buffer = Buffer.from(base64, "base64");

        if (!buffer.length) {
            return res.status(400).json({
                success: false,
                message: "Gambar kosong."
            });
        }

        if (buffer.length > 3 * 1024 * 1024) {
            return res.status(400).json({
                success: false,
                message: "Ukuran gambar terlalu besar."
            });
        }

        let extension = "jpg";

        if (contentType === "image/png") {
            extension = "png";
        }

        if (contentType === "image/webp") {
            extension = "webp";
        }

        const pathname =
            `profiles/${userId}/` +
            `${Date.now()}-${crypto.randomUUID()}.${extension}`;

        const blob = await put(
            pathname,
            buffer,
            {
                access: "public",
                contentType,
                addRandomSuffix: false,
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
            message: error instanceof Error
                ? error.message
                : "Upload foto gagal."
        });
    }
}
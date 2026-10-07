document.addEventListener("DOMContentLoaded", () => {
    const fotoInput = document.getElementById("fotoInput");
    const cropImage = document.getElementById("cropImage");
    const cropModalElement = document.getElementById("cropModal");
    const saveButton = document.getElementById("saveButton");

    const namaInput = document.getElementById("namaLengkap");
    const usernameInput = document.getElementById("username");
    const levelInput = document.getElementById("level");

    const liveName = document.getElementById("liveName");
    const liveUsername = document.getElementById("liveUsername");
    const liveRole = document.getElementById("liveRole");

    const selectedFile = document.getElementById("selectedFile");
    const alertBox = document.getElementById("alertBox");

    const mainPreview = document.getElementById("mainPreview");

    const zoomIn = document.getElementById("zoomIn");
    const zoomOut = document.getElementById("zoomOut");
    const moveLeft = document.getElementById("moveLeft");
    const moveRight = document.getElementById("moveRight");
    const moveUp = document.getElementById("moveUp");
    const moveDown = document.getElementById("moveDown");
    const resetCrop = document.getElementById("resetCrop");
    const useCrop = document.getElementById("useCrop");

    if (
        !fotoInput ||
        !cropImage ||
        !cropModalElement ||
        !saveButton
    ) {
        return;
    }

    const cropModal =
        new bootstrap.Modal(cropModalElement);

    let cropper = null;
    let selectedBlob = null;
    let selectedFileName = "";
    let objectUrl = "";

    function showAlert(message, success = true) {
        alertBox.innerHTML = `
            <div class="profile-alert ${success ? "success" : "error"}">
                <i class="bi ${
                    success
                        ? "bi-check-circle-fill"
                        : "bi-exclamation-circle-fill"
                }"></i>
                <span>${escapeHtml(message)}</span>
            </div>
        `;

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });

        setTimeout(() => {
            const alert = alertBox.querySelector(
                ".profile-alert"
            );

            if (alert) {
                alert.classList.add("hide");
            }
        }, 3500);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function clearObjectUrl() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = "";
        }
    }

    function updatePreview(blob) {
        if (!blob) {
            return;
        }

        const previewUrl =
            URL.createObjectURL(blob);

        const existingImage =
            mainPreview.querySelector(
                "#mainPreviewImage"
            );

        const initial =
            mainPreview.querySelector(
                "#mainPreviewInitial"
            );

        if (existingImage) {
            existingImage.src = previewUrl;
        } else {
            if (initial) {
                initial.remove();
            }

            const image =
                document.createElement("img");

            image.id = "mainPreviewImage";
            image.alt = "Foto Profil";
            image.width = 180;
            image.height = 180;
            image.src = previewUrl;

            mainPreview.appendChild(image);
        }

        setTimeout(() => {
            URL.revokeObjectURL(previewUrl);
        }, 10000);
    }

    async function createOptimizedBlob() {
        return new Promise((resolve, reject) => {
            if (!cropper) {
                reject(
                    new Error(
                        "Cropper belum siap."
                    )
                );

                return;
            }

            cropper
                .getCroppedCanvas({
                    width: 600,
                    height: 600,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: "high",
                    fillColor: "#ffffff"
                })
                .toBlob(
                    blob => {
                        if (!blob) {
                            reject(
                                new Error(
                                    "Gagal membuat foto."
                                )
                            );

                            return;
                        }

                        resolve(blob);
                    },
                    "image/webp",
                    0.82
                );
        });
    }

    fotoInput.addEventListener(
        "change",
        () => {
            const file =
                fotoInput.files &&
                fotoInput.files[0];

            if (!file) {
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                showAlert(
                    "Ukuran foto maksimal 3 MB.",
                    false
                );

                fotoInput.value = "";

                return;
            }

            const allowed = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            if (!allowed.includes(file.type)) {
                showAlert(
                    "Format foto harus JPG, PNG, atau WEBP.",
                    false
                );

                fotoInput.value = "";

                return;
            }

            selectedFileName = file.name;

            selectedFile.textContent =
                file.name;

            clearObjectUrl();

            objectUrl =
                URL.createObjectURL(file);

            cropImage.src = objectUrl;

            cropModal.show();
        }
    );

    cropModalElement.addEventListener(
        "shown.bs.modal",
        () => {
            destroyCropper();

            cropper = new Cropper(
                cropImage,
                {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: "move",
                    autoCropArea: 1,
                    responsive: true,
                    restore: false,
                    background: false,
                    guides: true,
                    center: true,
                    movable: true,
                    zoomable: true,
                    zoomOnWheel: true,
                    rotatable: false,
                    scalable: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false
                }
            );
        }
    );

    cropModalElement.addEventListener(
        "hidden.bs.modal",
        () => {
            destroyCropper();

            cropImage.removeAttribute(
                "src"
            );

            clearObjectUrl();
        }
    );

    zoomIn.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.zoom(0.1);
            }
        }
    );

    zoomOut.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.zoom(-0.1);
            }
        }
    );

    moveLeft.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.move(-20, 0);
            }
        }
    );

    moveRight.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.move(20, 0);
            }
        }
    );

    moveUp.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.move(0, -20);
            }
        }
    );

    moveDown.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.move(0, 20);
            }
        }
    );

    resetCrop.addEventListener(
        "click",
        () => {
            if (cropper) {
                cropper.reset();
            }
        }
    );

    useCrop.addEventListener(
        "click",
        async () => {
            try {
                selectedBlob =
                    await createOptimizedBlob();

                updatePreview(
                    selectedBlob
                );

                selectedFile.textContent =
                    `${selectedFileName} → WebP 600×600`;

                cropModal.hide();
            } catch (error) {
                showAlert(
                    error.message ||
                    "Foto gagal diproses.",
                    false
                );
            }
        }
    );

    namaInput.addEventListener(
        "input",
        () => {
            liveName.textContent =
                namaInput.value.trim() ||
                "Administrator";
        }
    );

    usernameInput.addEventListener(
        "input",
        () => {
            liveUsername.textContent =
                usernameInput.value.trim()
                    ? `@${usernameInput.value.trim()}`
                    : "@username";
        }
    );

    saveButton.addEventListener(
        "click",
        async () => {

            const nama =
                namaInput.value.trim();

            const username =
                usernameInput.value.trim();

            const level = "admin";

            if (nama.length < 3) {
                showAlert(
                    "Nama lengkap minimal 3 karakter.",
                    false
                );

                return;
            }

            if (username.length < 3) {
                showAlert(
                    "Username minimal 3 karakter.",
                    false
                );

                return;
            }

            saveButton.disabled = true;

            saveButton.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>
                Menyimpan...
            `;

            try {

                const formData =
                    new FormData();

                formData.append(
                    "nama_lengkap",
                    nama
                );

                formData.append(
                    "username",
                    username
                );

                formData.append(
                    "level",
                    level
                );

                if (selectedBlob) {
                    formData.append(
                        "foto_profil",
                        selectedBlob,
                        "profile.webp"
                    );
                }

                const response =
                    await fetch(
                        "profil.php",
                        {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin",
                            cache: "no-store"
                        }
                    );

                const data =
                    await response.json();

                if (!response.ok ||
                    !data.success
                ) {
                    throw new Error(
                        data.message ||
                        "Profil gagal disimpan."
                    );
                }

                liveName.textContent =
                    data.nama_lengkap;

                liveUsername.textContent =
                    `@${data.username}`;

                liveRole.textContent = "Administrator";

                if (
                    data.foto_profil_url
                ) {
                    let image =
                        mainPreview.querySelector(
                            "#mainPreviewImage"
                        );

                    if (!image) {
                        image =
                            document.createElement(
                                "img"
                            );

                        image.id =
                            "mainPreviewImage";

                        image.alt =
                            "Foto Profil";

                        image.width = 180;
                        image.height = 180;

                        mainPreview.appendChild(
                            image
                        );
                    }

                    image.src =
                        data.foto_profil_url +
                        "?v=" +
                        Date.now();
                }

                selectedBlob = null;
                selectedFileName = "";

                selectedFile.textContent =
                    "Foto tersimpan di Vercel Blob";

                showAlert(
                    data.message,
                    true
                );

            } catch (error) {

                showAlert(
                    error.message ||
                    "Terjadi kesalahan.",
                    false
                );

            } finally {

                saveButton.disabled = false;

                saveButton.innerHTML = `
                    <i class="bi bi-floppy2-fill"></i>
                    Simpan Perubahan
                `;
            }
        }
    );
});
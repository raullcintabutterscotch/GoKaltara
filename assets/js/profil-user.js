document.addEventListener("DOMContentLoaded", function () {
    const fotoInput = document.getElementById("fotoInput");
    const cropImage = document.getElementById("cropImage");
    const mainPreview = document.getElementById("mainPreview");
    const selectedFile = document.getElementById("selectedFile");
    const namaLengkap = document.getElementById("namaLengkap");
    const username = document.getElementById("username");
    const liveName = document.getElementById("liveName");
    const liveUsername = document.getElementById("liveUsername");
    const saveButton = document.getElementById("saveButton");
    const alertBox = document.getElementById("alertBox");
    const cropModalElement = document.getElementById("cropModal");

    if (
        !fotoInput ||
        !cropImage ||
        !mainPreview ||
        !cropModalElement
    ) {
        return;
    }

    const cropModal = new bootstrap.Modal(
        cropModalElement
    );

    let cropper = null;
    let croppedBlob = null;

    function showAlert(message, type = "danger") {
        alertBox.innerHTML = `
            <div class="alert alert-${type} rounded-4 border-0 small">
                ${message}
            </div>
        `;
    }

    function updateLiveProfile() {
        const nama =
            namaLengkap.value.trim() ||
            "Nama Lengkap";

        const user =
            username.value.trim() ||
            "username";

        liveName.textContent = nama;
        liveUsername.textContent = "@" + user;
    }

    namaLengkap.addEventListener(
        "input",
        updateLiveProfile
    );

    username.addEventListener(
        "input",
        updateLiveProfile
    );

    fotoInput.addEventListener(
        "change",
        function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                showAlert(
                    "Ukuran foto maksimal 3 MB."
                );

                this.value = "";

                return;
            }

            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            if (!allowedTypes.includes(file.type)) {
                showAlert(
                    "Format foto harus JPG, PNG, atau WEBP."
                );

                this.value = "";

                return;
            }

            selectedFile.textContent =
                file.name;

            const reader = new FileReader();

            reader.onload = function (event) {

                cropImage.src =
                    event.target.result;

                cropModal.show();
            };

            reader.readAsDataURL(file);
        }
    );

    cropModalElement.addEventListener(
        "shown.bs.modal",
        function () {

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(
                cropImage,
                {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: "move",
                    autoCropArea: 1,
                    responsive: true,
                    background: false,
                    guides: true,
                    center: true,
                    movable: true,
                    zoomable: true,
                    rotatable: false,
                    scalable: false
                }
            );
        }
    );

    cropModalElement.addEventListener(
        "hidden.bs.modal",
        function () {

            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }
    );

    const zoomIn =
        document.getElementById("zoomIn");

    const zoomOut =
        document.getElementById("zoomOut");

    const moveLeft =
        document.getElementById("moveLeft");

    const moveRight =
        document.getElementById("moveRight");

    const moveUp =
        document.getElementById("moveUp");

    const moveDown =
        document.getElementById("moveDown");

    const resetCrop =
        document.getElementById("resetCrop");

    if (zoomIn) {
        zoomIn.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.zoom(0.1);
                }
            }
        );
    }

    if (zoomOut) {
        zoomOut.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.zoom(-0.1);
                }
            }
        );
    }

    if (moveLeft) {
        moveLeft.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.move(-20, 0);
                }
            }
        );
    }

    if (moveRight) {
        moveRight.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.move(20, 0);
                }
            }
        );
    }

    if (moveUp) {
        moveUp.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.move(0, -20);
                }
            }
        );
    }

    if (moveDown) {
        moveDown.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.move(0, 20);
                }
            }
        );
    }

    if (resetCrop) {
        resetCrop.addEventListener(
            "click",
            function () {

                if (cropper) {
                    cropper.reset();
                }
            }
        );
    }

    const useCrop =
        document.getElementById("useCrop");

    if (useCrop) {
        useCrop.addEventListener(
            "click",
            function () {

                if (!cropper) {
                    return;
                }

                const canvas =
                    cropper.getCroppedCanvas({
                        width: 600,
                        height: 600,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: "high"
                    });

                canvas.toBlob(
                    function (blob) {

                        if (!blob) {
                            return;
                        }

                        croppedBlob = blob;

                        const previewUrl =
                            URL.createObjectURL(
                                blob
                            );

                        mainPreview.innerHTML = `
                            <img
                                id="previewFoto"
                                src="${previewUrl}"
                                alt="Preview Foto Profil"
                            >
                        `;

                        selectedFile.textContent =
                            "Foto sudah diatur dan siap disimpan.";

                        cropModal.hide();
                    },
                    "image/jpeg",
                    0.9
                );
            }
        );
    }

    saveButton.addEventListener(
        "click",
        async function () {

            const nama =
                namaLengkap.value.trim();

            const user =
                username.value.trim();

            alertBox.innerHTML = "";

            if (nama === "") {
                showAlert(
                    "Nama lengkap wajib diisi."
                );

                namaLengkap.focus();

                return;
            }

            if (nama.length < 3) {
                showAlert(
                    "Nama lengkap minimal 3 karakter."
                );

                namaLengkap.focus();

                return;
            }

            if (user === "") {
                showAlert(
                    "Username wajib diisi."
                );

                username.focus();

                return;
            }

            if (user.length < 3) {
                showAlert(
                    "Username minimal 3 karakter."
                );

                username.focus();

                return;
            }

            const formData = new FormData();

            formData.append(
                "nama_lengkap",
                nama
            );

            formData.append(
                "username",
                user
            );

            if (croppedBlob) {
                formData.append(
                    "foto_profil",
                    croppedBlob,
                    "profil.webp"
                );
            }

            saveButton.disabled = true;

            saveButton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Menyimpan...
            `;

            try {

                const response =
                    await fetch(
                        "profil-user.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );

                const responseText =
                    await response.text();

                let data;

                try {

                    data =
                        JSON.parse(
                            responseText
                        );

                } catch (error) {

                    console.error(
                        responseText
                    );

                    showAlert(
                        "Server mengirim respons yang tidak valid."
                    );

                    return;
                }

                if (!data.success) {

                    showAlert(
                        data.message ||
                        "Profil gagal diperbarui."
                    );

                    return;
                }

                showAlert(
                    data.message ||
                    "Profil berhasil diperbarui.",
                    "success"
                );

                liveName.textContent =
                    data.nama_lengkap;

                liveUsername.textContent =
                    "@" + data.username;

                if (data.foto_profil_url) {
                    const imageUrl = new URL(
                        data.foto_profil_url,
                        window.location.href
                    );
                    imageUrl.searchParams.set("v", Date.now().toString());

                    const previewContainer = document.getElementById("mainPreview");
                    let preview = document.querySelector("#mainPreview img");

                    if (previewContainer && !preview) {
                        preview = document.createElement("img");
                        preview.id = "previewFoto";
                        preview.alt = "Foto Profil";
                        preview.width = 220;
                        preview.height = 220;
                        previewContainer.replaceChildren(preview);
                    }

                    if (preview) {
                        preview.src = imageUrl.toString();
                    }
                }

                window.GokaltaraProfileLive?.apply(data);
                window.GokaltaraProfileLive?.publish(data);

                croppedBlob = null;

                selectedFile.textContent =
                    "Profil berhasil diperbarui.";

            } catch (error) {

                console.error(error);

                showAlert(
                    error.message ||
                    "Terjadi kesalahan saat menyimpan profil."
                );

            } finally {

                saveButton.disabled =
                    false;

                saveButton.innerHTML = `
                    <i class="bi bi-floppy2-fill me-2"></i>
                    Simpan Perubahan
                `;
            }
        }
    );

    updateLiveProfile();
});

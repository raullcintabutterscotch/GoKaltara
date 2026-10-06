document.addEventListener("DOMContentLoaded", function () {

    const fotoInput = document.getElementById("fotoInput");
    const cropImage = document.getElementById("cropImage");
    const mainPreview = document.getElementById("mainPreview");
    const selectedFile = document.getElementById("selectedFile");

    const namaLengkap = document.getElementById("namaLengkap");
    const username = document.getElementById("username");
    const level = document.getElementById("level");

    const liveName = document.getElementById("liveName");
    const liveUsername = document.getElementById("liveUsername");
    const liveRole = document.getElementById("liveRole");

    const saveButton = document.getElementById("saveButton");
    const alertBox = document.getElementById("alertBox");

    const cropModalElement =
        document.getElementById("cropModal");

    if (
        !fotoInput ||
        !cropImage ||
        !mainPreview ||
        !selectedFile ||
        !namaLengkap ||
        !username ||
        !level ||
        !liveName ||
        !liveUsername ||
        !liveRole ||
        !saveButton ||
        !alertBox ||
        !cropModalElement
    ) {
        return;
    }

    const cropModal =
        new bootstrap.Modal(
            cropModalElement
        );

    let cropper = null;
    let croppedBlob = null;
    let currentObjectUrl = null;

    function showAlert(
        message,
        type = "danger"
    ) {

        alertBox.innerHTML = `
            <div class="profile-alert ${type}">
                ${message}
            </div>
        `;

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }

    function updateLiveProfile() {

        liveName.textContent =
            namaLengkap.value.trim() ||
            "Administrator";

        liveUsername.textContent =
            "@" +
            (
                username.value.trim() ||
                "admin"
            );

        liveRole.textContent =
            level.value === "admin"
                ? "Administrator"
                : "User";
    }

    function revokeObjectUrl() {

        if (currentObjectUrl) {

            URL.revokeObjectURL(
                currentObjectUrl
            );

            currentObjectUrl = null;
        }
    }

    function destroyCropper() {

        if (cropper) {

            cropper.destroy();

            cropper = null;
        }
    }

    namaLengkap.addEventListener(
        "input",
        updateLiveProfile
    );

    username.addEventListener(
        "input",
        updateLiveProfile
    );

    level.addEventListener(
        "change",
        updateLiveProfile
    );

    fotoInput.addEventListener(
        "change",
        function () {

            const file =
                this.files &&
                this.files[0];

            if (!file) {
                return;
            }

            if (
                ![
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ].includes(file.type)
            ) {

                showAlert(
                    "Format foto harus JPG, PNG, atau WEBP."
                );

                this.value = "";

                return;
            }

            if (
                file.size >
                3 * 1024 * 1024
            ) {

                showAlert(
                    "Ukuran foto maksimal 3 MB."
                );

                this.value = "";

                return;
            }

            revokeObjectUrl();

            currentObjectUrl =
                URL.createObjectURL(file);

            cropImage.src =
                currentObjectUrl;

            selectedFile.textContent =
                file.name;

            cropModal.show();
        }
    );

    cropModalElement.addEventListener(
        "shown.bs.modal",
        function () {

            destroyCropper();

            if (
                typeof Cropper === "undefined" ||
                !cropImage.src
            ) {
                return;
            }

            cropper =
                new Cropper(
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
        function () {

            destroyCropper();

            cropImage.removeAttribute(
                "src"
            );

            revokeObjectUrl();
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

    document
        .getElementById("useCrop")
        .addEventListener(
            "click",
            function () {

                if (!cropper) {

                    showAlert(
                        "Silakan pilih dan atur foto terlebih dahulu."
                    );

                    return;
                }

                const canvas =
                    cropper.getCroppedCanvas({
                        width: 600,
                        height: 600,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: "high",
                        fillColor: "#ffffff"
                    });

                if (!canvas) {

                    showAlert(
                        "Foto gagal diproses."
                    );

                    return;
                }

                canvas.toBlob(
                    function (blob) {

                        if (!blob) {

                            showAlert(
                                "Foto gagal diproses."
                            );

                            return;
                        }

                        croppedBlob = blob;

                        const previewUrl =
                            URL.createObjectURL(
                                blob
                            );

                        mainPreview.innerHTML = `
                            <img
                                src="${previewUrl}"
                                alt="Preview Foto Profil"
                            >
                        `;

                        selectedFile.textContent =
                            "Foto sudah diatur dan siap disimpan.";

                        cropModal.hide();
                    },
                    "image/jpeg",
                    0.88
                );
            }
        );

    saveButton.addEventListener(
        "click",
        async function () {

            const nama =
                namaLengkap.value.trim();

            const user =
                username.value.trim();

            const role =
                level.value;

            if (nama === "") {

                showAlert(
                    "Nama lengkap wajib diisi."
                );

                return;
            }

            if (nama.length < 3) {

                showAlert(
                    "Nama lengkap minimal 3 karakter."
                );

                return;
            }

            if (user === "") {

                showAlert(
                    "Username wajib diisi."
                );

                return;
            }

            if (user.length < 3) {

                showAlert(
                    "Username minimal 3 karakter."
                );

                return;
            }

            if (
                role !== "admin" &&
                role !== "user"
            ) {

                showAlert(
                    "Role tidak valid."
                );

                return;
            }

            const formData =
                new FormData();

            formData.append(
                "nama_lengkap",
                nama
            );

            formData.append(
                "username",
                user
            );

            formData.append(
                "level",
                role
            );

            if (croppedBlob) {

                formData.append(
                    "foto_profil",
                    croppedBlob,
                    "profil.jpg"
                );
            }

            const originalButton =
                saveButton.innerHTML;

            saveButton.disabled = true;

            saveButton.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>
                Menyimpan...
            `;

            alertBox.innerHTML = "";

            try {

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

                liveName.textContent =
                    data.nama_lengkap;

                liveUsername.textContent =
                    "@" +
                    data.username;

                liveRole.textContent =
                    data.level === "admin"
                        ? "Administrator"
                        : "User";

                if (
                    data.foto_profil
                ) {

                    const fotoUrl =
                        "../assets/images/profil/" +
                        encodeURIComponent(
                            data.foto_profil
                        ) +
                        "?v=" +
                        Date.now();

                    mainPreview.innerHTML = `
                        <img
                            src="${fotoUrl}"
                            id="mainPreviewImage"
                            alt="Foto Profil"
                        > 
                    `;

                    document
                        .querySelectorAll(
                            ".avatar-image"
                        )
                        .forEach(
                            function (image) {

                                image.src =
                                    fotoUrl;
                            }
                        );
                }

                croppedBlob = null;

                showAlert(
                    data.message ||
                    "Profil berhasil diperbarui.",
                    "success"
                );

                setTimeout(
                    function () {

                        if (
                            data.level === "admin"
                        ) {

                            window.location.href =
                                "dashboard.php";

                        } else {

                            window.location.href =
                                "../index.php";
                        }

                    },
                    900
                );

            } catch (error) {

                console.error(error);

                showAlert(
                    "Tidak dapat terhubung ke server."
                );

            } finally {

                saveButton.disabled = false;

                saveButton.innerHTML =
                    originalButton;
            }
        }
    );

    updateLiveProfile();

});
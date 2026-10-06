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
    const liveRole =
        document.getElementById("liveRole") ||
        document.querySelector(".admin-role");

    const saveButton = document.getElementById("saveButton");
    const alertBox = document.getElementById("alertBox");
    const cropModalElement = document.getElementById("cropModal");

    const sidebarProfileImage =
        document.getElementById("sidebarProfileImage");

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

    const cropModal = new bootstrap.Modal(
        cropModalElement
    );

    let cropper = null;
    let croppedBlob = null;
    let selectedObjectUrl = null;
    let previewObjectUrl = null;

    function showAlert(message, type = "danger") {
        alertBox.innerHTML = `
            <div class="profile-alert ${type}">
                ${message}
            </div>
        `;
    }

    function updateLiveProfile() {
        const nama =
            namaLengkap.value.trim() ||
            "Administrator";

        const user =
            username.value.trim() ||
            "admin";

        const role =
            level.value === "admin"
                ? "Administrator"
                : "User";

        liveName.textContent = nama;
        liveUsername.textContent = "@" + user;
        liveRole.textContent = role;
    }

    function revokeSelectedObjectUrl() {
        if (selectedObjectUrl) {
            URL.revokeObjectURL(
                selectedObjectUrl
            );

            selectedObjectUrl = null;
        }
    }

    function revokePreviewObjectUrl() {
        if (previewObjectUrl) {
            URL.revokeObjectURL(
                previewObjectUrl
            );

            previewObjectUrl = null;
        }
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function createPreviewImage(src) {
        mainPreview.innerHTML = `
            <img
                id="mainPreviewImage"
                alt="Foto Profil"
                width="220"
                height="220"
            >
        `;

        const image = mainPreview.querySelector("img");

        watchImageLoading(image, mainPreview);
        image.src = src;
    }

    function watchImageLoading(
        image,
        wrapper,
        loadingClass = "is-loading",
        checkCurrentSource = true
    ) {
        if (!image || !wrapper) {
            return;
        }

        const finishLoading = () => {
            wrapper.classList.remove(loadingClass);
        };

        wrapper.classList.add(loadingClass);
        image.addEventListener("load", finishLoading, { once: true });
        image.addEventListener("error", finishLoading, { once: true });

        if (
            checkCurrentSource &&
            image.getAttribute("src") &&
            image.complete
        ) {
            finishLoading();
        }
    }

    function withFreshProfileUrl(url) {
        if (!url) {
            return "";
        }

        const freshUrl = new URL(url, window.location.href);
        freshUrl.searchParams.set("v", Date.now().toString());

        return freshUrl.toString();
    }

    function updateProfileImages(url, thumbnailUrl = "") {
        const cacheUrl = withFreshProfileUrl(url);
        const sidebarCacheUrl = withFreshProfileUrl(thumbnailUrl || url);

        createPreviewImage(cacheUrl);

        const currentSidebarImage =
            document.getElementById("sidebarProfileImage");

        if (currentSidebarImage) {
            const sidebarWrapper =
                currentSidebarImage.closest(".admin-profile");

            watchImageLoading(
                currentSidebarImage,
                sidebarWrapper,
                "is-avatar-loading",
                false
            );
            currentSidebarImage.src = sidebarCacheUrl;
        } else {
            const image = document.createElement("img");

            image.id = "sidebarProfileImage";
            image.className = "avatar avatar-image";
            image.alt = "Foto Profil";
            image.width = 44;
            image.height = 44;
            const initial = document.getElementById("sidebarProfileInitial");
            const wrapper = initial?.closest(".admin-profile");

            if (wrapper && initial) {
                initial.replaceWith(image);
                watchImageLoading(
                    image,
                    wrapper,
                    "is-avatar-loading"
                );
                image.src = sidebarCacheUrl;
            }
        }

        const oldInitial =
            document.getElementById(
                "sidebarProfileInitial"
            );

        if (oldInitial) {
            oldInitial.remove();
        }
    }

    watchImageLoading(
        mainPreview.querySelector("img"),
        mainPreview
    );

    if (sidebarProfileImage) {
        watchImageLoading(
            sidebarProfileImage,
            sidebarProfileImage.closest(".admin-profile"),
            "is-avatar-loading"
        );
    }

    function setSavingState(isSaving) {
        saveButton.disabled = isSaving;

        if (isSaving) {
            saveButton.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm"
                    aria-hidden="true"
                ></span>
                <span>Menyimpan...</span>
            `;
        } else {
            saveButton.innerHTML = `
                <i class="bi bi-floppy2-fill"></i>
                <span>Simpan Perubahan</span>
            `;
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

            revokeSelectedObjectUrl();

            selectedObjectUrl =
                URL.createObjectURL(file);

            cropImage.src =
                selectedObjectUrl;

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
                showAlert(
                    "Cropper belum berhasil dimuat."
                );

                return;
            }

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

            revokeSelectedObjectUrl();
        }
    );

    document
        .getElementById("zoomIn")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.zoom(0.1);
                }
            }
        );

    document
        .getElementById("zoomOut")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.zoom(-0.1);
                }
            }
        );

    document
        .getElementById("moveLeft")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.move(-20, 0);
                }
            }
        );

    document
        .getElementById("moveRight")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.move(20, 0);
                }
            }
        );

    document
        .getElementById("moveUp")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.move(0, -20);
                }
            }
        );

    document
        .getElementById("moveDown")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.move(0, 20);
                }
            }
        );

    document
        .getElementById("resetCrop")
        ?.addEventListener(
            "click",
            function () {
                if (cropper) {
                    cropper.reset();
                }
            }
        );

    document
        .getElementById("useCrop")
        ?.addEventListener(
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

                        revokePreviewObjectUrl();

                        previewObjectUrl =
                            URL.createObjectURL(
                                blob
                            );

                        createPreviewImage(
                            previewObjectUrl
                        );

                        selectedFile.textContent =
                            "Foto sudah diatur dan siap disimpan.";

                        cropModal.hide();
                    },
                    "image/jpeg",
                    0.9
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

            setSavingState(true);

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

                const text =
                    await response.text();

                let data;

                try {
                    data = JSON.parse(text);
                } catch (error) {
                    console.error(text);

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
                    "@" + data.username;

                liveRole.textContent =
                    data.level === "admin"
                        ? "Administrator"
                        : "User";

                if (data.foto_profil_url) {
                    updateProfileImages(
                        data.foto_profil_url,
                        data.foto_profil_thumbnail_url || ""
                    );
                }

                window.GokaltaraProfileLive?.apply(data);
                window.GokaltaraProfileLive?.publish(data);

                croppedBlob = null;

                revokePreviewObjectUrl();

                selectedFile.textContent =
                    "Profil berhasil diperbarui.";

                showAlert(
                    data.message ||
                    "Profil berhasil diperbarui.",
                    "success"
                );

            } catch (error) {
                console.error(error);

                showAlert(
                    "Tidak dapat terhubung ke server."
                );
            } finally {
                setSavingState(false);
            }
        }
    );

    updateLiveProfile();
});

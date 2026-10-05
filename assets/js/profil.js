
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
const cropModalElement = document.getElementById("cropModal");

const cropModal = new bootstrap.Modal(
    cropModalElement
);

let cropper = null;
let croppedBlob = null;

function updateLiveProfile() {

    liveName.textContent =
        namaLengkap.value.trim() || "Nama Lengkap";

    liveUsername.textContent =
        "@" + (
            username.value.trim() || "username"
        );

    liveRole.textContent =
        level.value === "admin"
            ? "Administrator"
            : "User";

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

        const file = this.files[0];

        if (!file) {
            return;
        }

        if (file.size > 2 * 1024 * 1024) {

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    Ukuran foto maksimal 2 MB.
                </div>
            `;

            this.value = "";

            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    Format foto harus JPG, PNG, atau WEBP.
                </div>
            `;

            this.value = "";

            return;
        }

        selectedFile.textContent = file.name;

        const reader = new FileReader();

        reader.onload = function (event) {

            cropImage.src = event.target.result;

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

document.getElementById(
    "zoomIn"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.zoom(0.1);
        }

    }
);

document.getElementById(
    "zoomOut"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.zoom(-0.1);
        }

    }
);

document.getElementById(
    "moveLeft"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.move(-20, 0);
        }

    }
);

document.getElementById(
    "moveRight"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.move(20, 0);
        }

    }
);

document.getElementById(
    "moveUp"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.move(0, -20);
        }

    }
);

document.getElementById(
    "moveDown"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.move(0, 20);
        }

    }
);

document.getElementById(
    "resetCrop"
).addEventListener(
    "click",
    function () {

        if (cropper) {
            cropper.reset();
        }

    }
);

document.getElementById(
    "useCrop"
).addEventListener(
    "click",
    function () {

        if (!cropper) {
            return;
        }

        const canvas = cropper.getCroppedCanvas({
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
                    URL.createObjectURL(blob);

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
            0.9
        );

    }
);

saveButton.addEventListener(
    "click",
    async function () {

        const nama = namaLengkap.value.trim();
        const user = username.value.trim();

        if (nama === "") {

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    Nama lengkap wajib diisi.
                </div>
            `;

            return;
        }

        if (nama.length < 3) {

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    Nama lengkap minimal 3 karakter.
                </div>
            `;

            return;
        }

        if (user === "") {

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    Username wajib diisi.
                </div>
            `;

            return;
        }

        if (user.length < 4) {

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    Username minimal 4 karakter.
                </div>
            `;

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

        formData.append(
            "level",
            level.value
        );

        if (croppedBlob) {

            formData.append(
                "foto_profil",
                croppedBlob,
                "profil.jpg"
            );

        }

        saveButton.disabled = true;

        saveButton.innerHTML = `
            <span
                class="spinner-border spinner-border-sm me-2"
            ></span>
            Menyimpan...
        `;

        try {

            const response = await fetch(
                "profil.php",
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
                    JSON.parse(responseText);

            } catch (error) {

                console.error(responseText);

                alertBox.innerHTML = `
                    <div class="alert alert-danger rounded-4 border-0 small">
                        Server mengirim respons yang tidak valid.
                        Buka Console Browser untuk melihat detailnya.
                    </div>
                `;

                return;
            }

            if (!data.success) {

                alertBox.innerHTML = `
                    <div class="alert alert-danger rounded-4 border-0 small">
                        ${data.message}
                    </div>
                `;

                return;
            }

            alertBox.innerHTML = `
                <div class="alert alert-success rounded-4 border-0 small">
                    ${data.message}
                </div>
            `;

            liveName.textContent =
                data.nama_lengkap;

            liveUsername.textContent =
                "@" + data.username;

            liveRole.textContent =
                data.level === "admin"
                    ? "Administrator"
                    : "User";

            croppedBlob = null;

            setTimeout(
                function () {

                    if (data.level === "admin") {
                        window.location.href =
                            "dashboard.php";
                    } else {
                        window.location.href =
                            "../index.php";
                    }

                },
                800
            );

        } catch (error) {

            console.error(error);

            alertBox.innerHTML = `
                <div class="alert alert-danger rounded-4 border-0 small">
                    ${error.message}
                </div>
            `;

        } finally {

            saveButton.disabled = false;

            saveButton.innerHTML = `
                <i class="bi bi-floppy2-fill me-2"></i>
                Simpan Perubahan
            `;

        }

    }
);

updateLiveProfile();

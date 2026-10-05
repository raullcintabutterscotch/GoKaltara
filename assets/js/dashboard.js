const fotoInput = document.getElementById("foto_profil");
const previewContainer = document.getElementById("profilePreview");
const selectedFile = document.getElementById("selectedFile");

fotoInput.addEventListener("change", function () {

    const file = this.files[0];

    if (!file) {
        selectedFile.textContent = "Belum memilih foto baru";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        selectedFile.textContent = "Ukuran file melebihi 2 MB";
        this.value = "";
        return;
    }

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!allowedTypes.includes(file.type)) {
        selectedFile.textContent = "Format file tidak didukung";
        this.value = "";
        return;
    }

    selectedFile.textContent = file.name;

    const reader = new FileReader();

    reader.onload = function (event) {

        previewContainer.innerHTML = "";

        const img = document.createElement("img");

        img.src = event.target.result;
        img.alt = "Preview Foto Profil";
        img.id = "previewImage";

        previewContainer.appendChild(img);
    };

    reader.readAsDataURL(file);
});


document.addEventListener("DOMContentLoaded", () => {
    const input = document.querySelector("[data-image-input]");
    const preview = document.querySelector("[data-image-preview]");
    const placeholder = document.querySelector("[data-image-placeholder]");

    if (!input || !preview || !placeholder) {
        return;
    }

    input.addEventListener("change", () => {
        const file = input.files[0];

        if (!file) {
            return;
        }

        if (!file.type.startsWith("image/")) {
            input.value = "";
            return;
        }

        const reader = new FileReader();

        reader.onload = event => {
            preview.src = event.target.result;
            preview.classList.add("show");
            placeholder.classList.add("hide");
        };

        reader.readAsDataURL(file);
    });
});
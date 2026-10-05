document.addEventListener(
    "DOMContentLoaded",
    function () {

        const grid =
            document.getElementById(
                "favoriteGrid"
            );

        const totalElement =
            document.getElementById(
                "favoriteTotal"
            );

        const empty =
            document.getElementById(
                "favoriteEmpty"
            );

        async function removeFavorite(
            button
        ) {

            const idKuliner =
                Number(
                    button.dataset.id ||
                    0
                );

            if (!idKuliner) {
                return;
            }

            const confirmed =
                window.confirm(
                    "Hapus kuliner ini dari favorit?"
                );

            if (!confirmed) {
                return;
            }

            button.disabled =
                true;

            const formData =
                new FormData();

            formData.append(
                "ajax",
                "1"
            );

            formData.append(
                "action",
                "hapus_favorit"
            );

            formData.append(
                "id_kuliner",
                String(idKuliner)
            );

            try {

                const response =
                    await fetch(
                        "favorit.php",
                        {
                            method:
                                "POST",
                            body:
                                formData
                        }
                    );

                const result =
                    await response.json();

                if (
                    !result.success
                ) {

                    alert(
                        result.message
                    );

                    return;
                }

                const card =
                    button.closest(
                        ".favorite-card"
                    );

                if (card) {

                    card.style.transition =
                        "opacity .2s ease, transform .2s ease";

                    card.style.opacity =
                        "0";

                    card.style.transform =
                        "scale(.97)";

                    setTimeout(
                        function () {

                            card.remove();

                            updateTotal();

                            checkEmpty();

                        },
                        200
                    );

                }

            } catch (
                error
            ) {

                alert(
                    "Gagal menghapus favorit."
                );

            } finally {

                button.disabled =
                    false;
            }
        }

        function updateTotal() {

            if (!grid) {
                return;
            }

            const cards =
                grid.querySelectorAll(
                    ".favorite-card"
                );

            if (totalElement) {

                totalElement.textContent =
                    cards.length;
            }
        }

        function checkEmpty() {

            if (!grid) {
                return;
            }

            const cards =
                grid.querySelectorAll(
                    ".favorite-card"
                );

            if (
                cards.length ===
                0
            ) {

                grid.remove();

                if (
                    empty
                ) {

                    empty.style.display =
                        "flex";

                } else {

                    const container =
                        document.querySelector(
                            ".favorite-container"
                        );

                    if (!container) {
                        return;
                    }

                    const newEmpty =
                        document.createElement(
                            "div"
                        );

                    newEmpty.className =
                        "favorite-empty";

                    newEmpty.innerHTML =
                        `
                        <div class="favorite-empty-icon">
                            <i class="bi bi-heart"></i>
                        </div>

                        <h2>
                            Belum Ada Favorit
                        </h2>

                        <p>
                            Simpan kuliner yang kamu sukai agar mudah ditemukan kembali.
                        </p>

                        <a href="katalog.php">
                            Jelajahi Katalog
                        </a>
                        `;

                    container.appendChild(
                        newEmpty
                    );
                }
            }
        }

        document
            .querySelectorAll(
                ".favorite-remove"
            )
            .forEach(
                function (
                    button
                ) {

                    button.addEventListener(
                        "click",
                        function () {

                            removeFavorite(
                                button
                            );

                        }
                    );

                }
            );

    }
);
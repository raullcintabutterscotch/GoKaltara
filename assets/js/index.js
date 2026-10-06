document.addEventListener("DOMContentLoaded", () => {
    const categoryButtons = document.querySelectorAll(".category-chip");
    const featuredGrid = document.getElementById("featuredGrid");
    const featuredTitle = document.getElementById("featuredTitle");

    if (!categoryButtons.length || !featuredGrid) {
        return;
    }

    let currentRequest = null;

    const setActiveCategory = (button) => {
        categoryButtons.forEach((item) => {
            item.classList.remove("active");
        });

        button.classList.add("active");
    };

    const setLoading = () => {
        featuredGrid.classList.add("is-loading");

        featuredGrid.innerHTML = `
            <div class="col-12">
                <div class="category-loading">
                    <span class="loading-spinner"></span>
                    <p>Memuat kuliner...</p>
                </div>
            </div>
        `;
    };

    const loadCategory = async (kategoriId) => {
        if (currentRequest) {
            currentRequest.abort();
        }

        currentRequest = new AbortController();

        setLoading();

        try {
            const response = await fetch(
                `index.php?ajax=kuliner&kategori=${encodeURIComponent(kategoriId)}`,
                {
                    method: "GET",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    signal: currentRequest.signal
                }
            );

            if (!response.ok) {
                throw new Error("Gagal mengambil data kuliner.");
            }

            const html = await response.text();

            featuredGrid.innerHTML = html;

            featuredGrid.classList.remove("is-loading");

            requestAnimationFrame(() => {
                featuredGrid.classList.add("category-updated");

                setTimeout(() => {
                    featuredGrid.classList.remove("category-updated");
                }, 350);
            });

            if (kategoriId === "0") {
                featuredTitle.textContent = "Kuliner Unggulan";
            } else {
                const activeButton = document.querySelector(
                    `.category-chip[data-kategori="${CSS.escape(kategoriId)}"]`
                );

                if (activeButton) {
                    featuredTitle.textContent =
                        activeButton.textContent.trim();
                }
            }
        } catch (error) {
            if (error.name === "AbortError") {
                return;
            }

            featuredGrid.classList.remove("is-loading");

            featuredGrid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-exclamation-circle"></i>
                        <h3>Data gagal dimuat</h3>
                        <p>
                            Silakan coba pilih kategori lagi.
                        </p>
                    </div>
                </div>
            `;
        }
    };

    categoryButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const kategoriId =
                button.dataset.kategori || "0";

            setActiveCategory(button);

            loadCategory(kategoriId);
        });
    });
});

const prepareHeroSlide = (slide) => {
    if (!slide) return;
    const background = slide.getAttribute("data-bg");
    if (!background || slide.dataset.bgLoaded === "1") return;
    const image = new Image();
    image.decoding = "async";
    image.onload = () => {
        slide.style.backgroundImage = `url("${background}")`;
        slide.dataset.bgLoaded = "1";
    };
    image.src = background;
};

const prepareSecondaryHeroSlides = () => {
    document.querySelectorAll(".hero-slide[data-bg]").forEach(prepareHeroSlide);
};

const heroCarousel = document.getElementById("heroCarousel");

if (heroCarousel) {
    heroCarousel.addEventListener("slide.bs.carousel", (event) => {
        prepareHeroSlide(event.relatedTarget);
    }, { passive: true });
    if ("requestIdleCallback" in window) {
        requestIdleCallback(prepareSecondaryHeroSlides, { timeout: 1800 });
    } else {
        setTimeout(prepareSecondaryHeroSlides, 1400);
    }
}

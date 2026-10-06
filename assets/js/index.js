document.addEventListener("DOMContentLoaded", () => {
    const carousel = document.getElementById("heroCarousel");
    const featuredGrid = document.getElementById("featuredGrid");
    const featuredTitle = document.getElementById("featuredTitle");
    const categoryButtons = document.querySelectorAll(".category-chip");

    let controller = null;

    if (carousel && window.bootstrap) {
        const firstSlideImage =
            carousel.querySelector(".hero-slide-image");

        if (firstSlideImage) {
            const firstSlide = firstSlideImage.closest(".hero-slide");
            const finishLoading = () => {
                firstSlide?.classList.remove("is-loading");
            };

            firstSlideImage.addEventListener("load", finishLoading, { once: true });
            firstSlideImage.addEventListener("error", finishLoading, { once: true });

            if (firstSlideImage.complete) {
                finishLoading();
            } else {
                firstSlide?.classList.add("is-loading");
            }
        }

        const instance = bootstrap.Carousel.getOrCreateInstance(carousel, {
            interval: 4500,
            ride: "carousel",
            pause: "hover",
            touch: true,
            wrap: true
        });

        const loadUpcomingImages = () => {
            const nextItems = carousel.querySelectorAll(
                ".carousel-item:not(.active) [data-bg]"
            );

            nextItems.forEach((element, index) => {
                if (index > 0) return;

                const src = element.dataset.bg;

                if (!src) return;

                const image = new Image();

                image.onload = () => {
                    element.style.backgroundImage = `url("${src}")`;
                    element.removeAttribute("data-bg");
                    element.classList.remove("is-loading");
                };

                image.onerror = () => {
                    element.classList.remove("is-loading");
                };

                image.src = src;
            });
        };

        carousel.addEventListener("slid.bs.carousel", () => {
            requestAnimationFrame(loadUpcomingImages);
        });

        requestAnimationFrame(loadUpcomingImages);

        window.addEventListener("pageshow", () => {
            instance.cycle();
        });
    }

    const setLoading = () => {
        if (!featuredGrid) return;

        featuredGrid.classList.add("is-loading");

        featuredGrid.innerHTML = `
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="food-skeleton"></div>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="food-skeleton"></div>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="food-skeleton"></div>
            </div>
        `;
    };

    const loadCategory = async (categoryId) => {
        if (!featuredGrid) return;

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();

        setLoading();

        try {
            const url = new URL(window.location.href);

            url.searchParams.set("ajax", "kuliner");
            url.searchParams.set("kategori", categoryId);

            const response = await fetch(url.toString(), {
                method: "GET",
                signal: controller.signal,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                cache: "no-store"
            });

            if (!response.ok) {
                throw new Error("Gagal mengambil data.");
            }

            const html = await response.text();

            featuredGrid.innerHTML = html;
            featuredGrid.classList.remove("is-loading");

            if (featuredTitle) {
                const activeButton = document.querySelector(
                    ".category-chip.active"
                );

                featuredTitle.textContent =
                    activeButton && categoryId !== "0"
                        ? activeButton.textContent.trim()
                        : "Kuliner Unggulan";
            }

            featuredGrid.querySelectorAll("img").forEach((img) => {
                img.loading = "lazy";
                img.decoding = "async";
            });

            const historyUrl = new URL(window.location.href);

            historyUrl.searchParams.delete("ajax");
            historyUrl.searchParams.set("kategori", categoryId);

            history.replaceState(
                {},
                "",
                historyUrl.toString()
            );
        } catch (error) {
            if (error.name === "AbortError") {
                return;
            }

            featuredGrid.classList.remove("is-loading");

            featuredGrid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <h3>Data gagal dimuat</h3>
                        <p>Silakan coba lagi.</p>
                    </div>
                </div>
            `;
        }
    };

    categoryButtons.forEach((button) => {
        button.addEventListener("click", () => {
            categoryButtons.forEach((item) => {
                item.classList.remove("active");
            });

            button.classList.add("active");

            const categoryId =
                button.dataset.kategori || "0";

            loadCategory(categoryId);
        });
    });

    if (featuredGrid) {
        featuredGrid.querySelectorAll("img").forEach((img) => {
            img.loading = "lazy";
            img.decoding = "async";

            if (!img.width) {
                img.width = 1200;
            }

            if (!img.height) {
                img.height = 800;
            }
        });
    }
});

(function () {
    "use strict";

    var form = document.getElementById("catalogFilter");
    var searchInput = document.getElementById("catalogRealtimeSearch");
    var categoryFilter = document.getElementById("catalogCategoryFilter");
    var regionFilter = document.getElementById("catalogRegionFilter");
    var results = document.getElementById("catalogResults");
    var pagination = document.getElementById("catalogPagination");
    var count = document.getElementById("catalogCount");
    var filterStatus = document.getElementById("catalogFilterStatus");

    if (!form || !searchInput || !categoryFilter || !regionFilter || !results) {
        return;
    }

    var timer = null;
    var controller = null;
    var requestId = 0;

    function updateUrl(page) {
        var params = new URLSearchParams();
        var q = searchInput.value.trim();
        var kategori = categoryFilter.value;
        var daerah = regionFilter.value;

        updateFilterStatus();

        if (q !== "") {
            params.set("q", q);
        }

        if (kategori !== "") {
            params.set("kategori", kategori);
        }

        if (daerah !== "") {
            params.set("daerah", daerah);
        }

        if (page > 1) {
            params.set("page", String(page));
        }

        var query = params.toString();
        var target = "katalog.php" + (query ? "?" + query : "");

        window.history.replaceState(
            null,
            "",
            target
        );
    }

    function skeletonHtml() {
        var cards = [];
        var total = window.matchMedia("(max-width: 767.98px)").matches ? 4 : 8;

        for (var i = 0; i < total; i++) {
            cards.push(
                '<article class="catalog-skeleton-card">' +
                    '<div class="catalog-skeleton-image"></div>' +
                    '<div class="catalog-skeleton-body">' +
                        '<div class="catalog-skeleton-line short"></div>' +
                        '<div class="catalog-skeleton-line medium"></div>' +
                        '<div class="catalog-skeleton-line long"></div>' +
                        '<div class="catalog-skeleton-line medium"></div>' +
                    '</div>' +
                '</article>'
            );
        }

        return '<div class="catalog-grid catalog-skeleton-grid" aria-hidden="true">' + cards.join("") + '</div>';
    }

    function setLoading(isLoading) {
        if (isLoading) {
            results.classList.add("catalog-search-loading");
            results.setAttribute("aria-busy", "true");
            results.innerHTML = skeletonHtml();

            if (pagination) {
                pagination.innerHTML = "";
            }
        } else {
            results.classList.remove("catalog-search-loading");
            results.setAttribute("aria-busy", "false");
        }
    }

    function updateFilterStatus() {
        if (!filterStatus) {
            return;
        }

        var hasFilter =
            searchInput.value.trim() !== "" ||
            categoryFilter.value !== "" ||
            regionFilter.value !== "";

        filterStatus.hidden = !hasFilter;
    }

    function requestCatalog(page) {
        page = Math.max(1, Number(page) || 1);

        var myRequest = ++requestId;
        var params = new URLSearchParams();
        var q = searchInput.value.trim();
        var kategori = categoryFilter.value;
        var daerah = regionFilter.value;

        if (q !== "") {
            params.set("q", q);
        }

        if (kategori !== "") {
            params.set("kategori", kategori);
        }

        if (daerah !== "") {
            params.set("daerah", daerah);
        }

        params.set("page", String(page));
        params.set("ajax", "1");

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        setLoading(true);

        fetch("katalog.php?" + params.toString(), {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            signal: controller.signal,
            cache: "no-store"
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("HTTP " + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                if (myRequest !== requestId) {
                    return;
                }

                if (!data || data.success !== true) {
                    throw new Error("Respons katalog tidak valid.");
                }

                results.innerHTML = data.html || "";

                if (pagination) {
                    pagination.innerHTML = data.pagination || "";
                }

                if (count) {
                    count.textContent = String(data.total || 0);
                }

                updateUrl(Number(data.page) || 1);
                setLoading(false);

                if (window.dispatchEvent) {
                    window.dispatchEvent(
                        new CustomEvent("gokaltara-catalog-updated")
                    );
                }
            })
            .catch(function (error) {
                if (error && error.name === "AbortError") {
                    return;
                }

                if (myRequest !== requestId) {
                    return;
                }

                setLoading(false);

                results.innerHTML =
                    '<div class="catalog-empty" role="alert">' +
                        '<div class="catalog-empty-icon">' +
                            '<i class="bi bi-wifi-off"></i>' +
                        '</div>' +
                        '<h2>Gagal Memuat Katalog</h2>' +
                        '<p>Periksa koneksi internet lalu coba lagi.</p>' +
                        '<button type="button" class="catalog-retry-button">Coba Lagi</button>' +
                    '</div>';

                if (pagination) {
                    pagination.innerHTML = "";
                }
            });
    }

    function scheduleRequest() {
        window.clearTimeout(timer);

        timer = window.setTimeout(function () {
            requestCatalog(1);
        }, 220);
    }

    function syncFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var q = params.get("q") || "";
        var kategori = params.get("kategori") || "";
        var daerah = params.get("daerah") || "";
        var page = Number(params.get("page") || 1);

        searchInput.value = q;
        categoryFilter.value = kategori;
        regionFilter.value = daerah;
        updateFilterStatus();

        return page > 0 ? page : 1;
    }

    searchInput.addEventListener("input", scheduleRequest);
    categoryFilter.addEventListener("change", scheduleRequest);
    regionFilter.addEventListener("change", scheduleRequest);

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        window.clearTimeout(timer);
        requestCatalog(1);
    });

    document.addEventListener("click", function (event) {
        var paginationLink = event.target.closest(
            "#catalogPagination a"
        );

        if (paginationLink) {
            event.preventDefault();

            var url = new URL(
                paginationLink.href,
                window.location.origin
            );

            var page = Number(url.searchParams.get("page") || 1);
            requestCatalog(page);
            return;
        }

        var retryButton = event.target.closest(
            ".catalog-retry-button"
        );

        if (retryButton) {
            requestCatalog(1);
        }
    });

    window.addEventListener("popstate", function () {
        var page = syncFromUrl();
        requestCatalog(page);
    });

    syncFromUrl();
})();

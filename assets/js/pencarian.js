document.addEventListener("DOMContentLoaded", function () {
    const searchInput =
        document.getElementById("realtimeSearch");

    const categoryFilter =
        document.getElementById("filterKategori");

    const regionFilter =
        document.getElementById("filterDaerah");

    const applyFilter =
        document.getElementById("applyFilter");

    const clearSearch =
        document.getElementById("clearSearch");

    const resultCount =
        document.getElementById("resultCount");

    const resultTitle =
        document.getElementById("resultTitle");

    const results =
        document.getElementById("searchResults");

    const noResult =
        document.getElementById("noRealtimeResult");

    if (
        !searchInput ||
        !categoryFilter ||
        !regionFilter ||
        !results
    ) {
        return;
    }

    function normalize(value) {
        return value
            .toLowerCase()
            .trim();
    }

    function filterCards() {
        const keyword =
            normalize(searchInput.value);

        const category =
            categoryFilter.value;

        const region =
            normalize(regionFilter.value);

        const cards =
            results.querySelectorAll(
                ".search-card-item"
            );

        let visibleCount = 0;

        cards.forEach(function (card) {
            const name =
                normalize(
                    card.dataset.name || ""
                );

            const cardRegion =
                normalize(
                    card.dataset.region || ""
                );

            const cardCategory =
                card.dataset.category || "";

            const description =
                normalize(
                    card.dataset.description || ""
                );

            const keywordMatch =
                keyword === "" ||
                name.includes(keyword) ||
                cardRegion.includes(keyword) ||
                description.includes(keyword);

            const categoryMatch =
                category === "" ||
                cardCategory === category;

            const regionMatch =
                region === "" ||
                cardRegion === region;

            const visible =
                keywordMatch &&
                categoryMatch &&
                regionMatch;

            card.style.display =
                visible
                    ? ""
                    : "none";

            if (visible) {
                visibleCount++;
            }
        });

        resultCount.textContent =
            visibleCount;

        if (
            keyword === "" &&
            category === "" &&
            region === ""
        ) {
            resultTitle.textContent =
                "Semua Kuliner";
        } else if (keyword !== "") {
            resultTitle.textContent =
                'Hasil untuk "' +
                searchInput.value.trim() +
                '"';
        } else {
            resultTitle.textContent =
                "Hasil Filter";
        }

        if (visibleCount === 0) {
            noResult.style.display =
                "block";
        } else {
            noResult.style.display =
                "none";
        }
    }

    searchInput.addEventListener(
        "input",
        function () {
            filterCards();
        }
    );

    categoryFilter.addEventListener(
        "change",
        function () {
            filterCards();
        }
    );

    regionFilter.addEventListener(
        "change",
        function () {
            filterCards();
        }
    );

    if (applyFilter) {
        applyFilter.addEventListener(
            "click",
            function () {
                filterCards();
            }
        );
    }

    if (clearSearch) {
        clearSearch.addEventListener(
            "click",
            function () {
                searchInput.value = "";

                categoryFilter.value = "";

                regionFilter.value = "";

                filterCards();

                searchInput.focus();
            }
        );
    }

    filterCards();
});
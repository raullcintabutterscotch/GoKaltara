(() => {
    const storageKey = "gokaltara.profile.updated";
    const channel = "BroadcastChannel" in window
        ? new BroadcastChannel("gokaltara-profile")
        : null;

    function addFreshVersion(url) {
        if (!url) {
            return "";
        }

        const imageUrl = new URL(url, window.location.href);
        imageUrl.searchParams.set("v", Date.now().toString());

        return imageUrl.toString();
    }

    function updateImage(image, preferredUrl, fallbackUrl) {
        if (!image || !preferredUrl) {
            return;
        }

        const primary = addFreshVersion(preferredUrl);
        const fallback = fallbackUrl
            ? addFreshVersion(fallbackUrl)
            : "";
        const primaryBase = new URL(preferredUrl, window.location.href);
        const fallbackBase = fallbackUrl
            ? new URL(fallbackUrl, window.location.href)
            : null;

        primaryBase.searchParams.delete("v");
        fallbackBase?.searchParams.delete("v");

        const hasDistinctFallback =
            fallback && fallbackBase && primaryBase.href !== fallbackBase.href;
        let triedFallback = false;
        const wrapper = image.closest(".admin-profile, .mobile-header-profile");

        const finishLoading = () => {
            wrapper?.classList.remove("is-avatar-loading");
        };

        image.hidden = false;
        wrapper?.classList.add("is-avatar-loading");
        image.addEventListener("load", finishLoading, { once: true });
        image.onerror = () => {
            if (hasDistinctFallback && !triedFallback) {
                triedFallback = true;
                image.src = fallback;
                return;
            }

            finishLoading();
            image.onerror = null;
            image.hidden = true;
        };
        image.src = primary;
    }

    function ensureImage(container, preferredUrl, fallbackUrl) {
        if (!container || !preferredUrl) {
            return;
        }

        let image = container.querySelector("img.avatar-image, img");

        if (!image) {
            image = document.createElement("img");
            image.className = "avatar avatar-image";
            image.alt = "Foto Profil";
            image.width = 42;
            image.height = 42;

            const placeholder = container.querySelector(".avatar");

            if (placeholder) {
                placeholder.replaceWith(image);
            } else {
                container.prepend(image);
            }
        }

        updateImage(image, preferredUrl, fallbackUrl);
    }

    function apply(profile) {
        if (!profile || typeof profile !== "object") {
            return;
        }

        const fullUrl = profile.foto_profil_url || "";
        const thumbnailUrl = profile.foto_profil_thumbnail_url || fullUrl;

        document.querySelectorAll(".admin-profile").forEach((container) => {
            if (thumbnailUrl) {
                ensureImage(container, thumbnailUrl, fullUrl);
            }

            if (profile.nama_lengkap) {
                const name = container.querySelector(".admin-name");

                if (name) {
                    name.textContent = profile.nama_lengkap;
                }
            }
        });

        document.querySelectorAll(".mobile-header-profile").forEach((container) => {
            if (!thumbnailUrl) {
                return;
            }

            let image = container.querySelector("img");

            if (!image) {
                image = document.createElement("img");
                image.alt = "Foto Profil";
                image.width = 42;
                image.height = 42;
                container.replaceChildren(image);
            }

            updateImage(image, thumbnailUrl, fullUrl);
        });
    }

    function publish(profile) {
        if (!profile || typeof profile !== "object") {
            return;
        }

        const message = {
            foto_profil_url: profile.foto_profil_url || "",
            foto_profil_thumbnail_url: profile.foto_profil_thumbnail_url || "",
            nama_lengkap: profile.nama_lengkap || "",
            username: profile.username || "",
            level: profile.level || "",
            updated_at: Date.now()
        };

        if (channel) {
            channel.postMessage(message);
            return;
        }

        try {
            localStorage.setItem(storageKey, JSON.stringify(message));
        } catch (error) {
            // The current page still updates even when storage is unavailable.
        }
    }

    if (channel) {
        channel.addEventListener("message", (event) => apply(event.data));
    } else {
        window.addEventListener("storage", (event) => {
            if (event.key !== storageKey || !event.newValue) {
                return;
            }

            try {
                apply(JSON.parse(event.newValue));
            } catch (error) {
                // Ignore stale or malformed cross-tab update data.
            }
        });
    }

    window.GokaltaraProfileLive = { apply, publish };
})();

(function () {
    "use strict";

    var reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;

    function prepareImage(img) {
        if (
            !img ||
            img.nodeType !== 1 ||
            img.getAttribute("data-perf-prepared") === "1"
        ) {
            return;
        }

        img.setAttribute("data-perf-prepared", "1");

        var src = img.getAttribute("src") || "";
        var isSvg = /\.svg(?:\?|$)/i.test(src);
        var isLogo = img.closest(
            ".brand-logo, .brand-title, .mobile-brand-logo, .login-brand"
        );
        var isAvatar = img.closest(
            ".avatar, .mobile-header-profile, .comment-avatar, .notification-avatar"
        );
        var isCritical =
            img.hasAttribute("data-no-lazy") ||
            img.classList.contains("detail-photo") ||
            img.classList.contains("hero-image") ||
            img.getAttribute("fetchpriority") === "high";

        img.setAttribute("decoding", "async");

        if (!isSvg && !isLogo && !isAvatar && !isCritical) {
            if (!img.hasAttribute("loading")) {
                img.setAttribute("loading", "lazy");
            }
        }

        if (
            isSvg ||
            isLogo ||
            isAvatar ||
            img.hasAttribute("data-no-skeleton")
        ) {
            return;
        }

        img.classList.add("perf-image");

        var markLoaded = function () {
            img.classList.add("is-loaded");
        };

        img.addEventListener("load", markLoaded, {
            once: true,
            passive: true
        });

        img.addEventListener("error", markLoaded, {
            once: true,
            passive: true
        });

        if (img.complete && img.naturalWidth > 0) {
            markLoaded();
        }
    }

    function prepareImages(root) {
        if (!root) {
            return;
        }

        if (root.matches && root.matches("img")) {
            prepareImage(root);
        }

        var images = root.querySelectorAll
            ? root.querySelectorAll("img")
            : [];

        images.forEach(prepareImage);
    }

    function observeNewImages() {
        if (!document.body || !window.MutationObserver) {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        prepareImages(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    function initLenis() {
        if (reduceMotion) {
            return;
        }

        if (!window.matchMedia("(min-width: 992px)").matches) {
            return;
        }

        if (typeof window.Lenis !== "undefined") {
            startLenis();
            return;
        }

        var script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/lenis@latest/dist/lenis.min.js";
        script.async = true;

        script.onload = function () {
            startLenis();
        };

        document.head.appendChild(script);
    }

    function startLenis() {
        if (
            reduceMotion ||
            !window.matchMedia("(min-width: 992px)").matches ||
            typeof window.Lenis === "undefined" ||
            window.__gokaltaraLenis
        ) {
            return;
        }

        var lenis = new window.Lenis({
            lerp: 0.08,
            duration: 1.05,
            smoothWheel: true,
            syncTouch: false,
            wheelMultiplier: 1,
            touchMultiplier: 1
        });

        window.__gokaltaraLenis = lenis;

        function raf(time) {
            lenis.raf(time);
            window.requestAnimationFrame(raf);
        }

        window.requestAnimationFrame(raf);
    }

    function start() {
        prepareImages(document);
        observeNewImages();
        initLenis();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", start, {
            once: true
        });
    } else {
        start();
    }
})();

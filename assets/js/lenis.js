(function () {
    "use strict";

    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    if (window.matchMedia && !window.matchMedia("(min-width: 992px)").matches) {
        return;
    }

    function initLenis() {
        if (typeof Lenis !== "function") {
            return;
        }

        if (document.documentElement.scrollHeight <= window.innerHeight + 2) {
            return;
        }

        if (window.gokaltaraLenis) {
            return;
        }

        window.gokaltaraLenis = new Lenis({
            autoRaf: true,
            anchors: true,
            autoToggle: true,
            stopInertiaOnNavigate: true,
            naiveDimensions: true,
            syncTouch: false
        });
    }

    function loadLenis() {
        if (typeof Lenis === "function") {
            initLenis();
            return;
        }

        if (document.querySelector('script[data-gokaltara-lenis]')) {
            return;
        }

        var script = document.createElement("script");
        script.src = "https://unpkg.com/lenis@1.3.26/dist/lenis.min.js";
        script.async = true;
        script.dataset.gokaltaraLenis = "true";
        script.onload = initLenis;
        document.head.appendChild(script);
    }

    function schedule() {
        if ("requestIdleCallback" in window) {
            window.requestIdleCallback(loadLenis, { timeout: 2400 });
        } else {
            window.setTimeout(loadLenis, 1200);
        }
    }

    if (document.readyState === "complete") {
        schedule();
    } else {
        window.addEventListener("load", schedule, { once: true });
    }
})();

(function () {
    "use strict";

    if (
        window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
        return;
    }

    if (typeof Lenis !== "function") {
        return;
    }

    window.gokaltaraLenis = new Lenis({
        autoRaf: true,
        anchors: true,
        autoToggle: true,
        stopInertiaOnNavigate: true,
        naiveDimensions: true
    });
})();

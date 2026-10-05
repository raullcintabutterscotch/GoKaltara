(function () {

    "use strict";

    let notificationPanel = null;
    let activeTrigger = null;
    let panelController = null;
    let badgeInterval = null;

    const isMobile = () => {
        return window.matchMedia(
            "(max-width: 767.98px)"
        ).matches;
    };

    const getCurrentUrl = () => {
        return (
            window.location.pathname +
            window.location.search +
            window.location.hash
        );
    };

    const getNotificationUrl = () => {
        const url = new URL(
            "notifikasi.php",
            window.location.href
        );

        url.searchParams.set(
            "from",
            getCurrentUrl()
        );

        return url.toString();
    };

    const ensureBadge = (trigger) => {

        if (!trigger) {
            return null;
        }

        let badge = trigger.querySelector(
            ".notification-badge"
        );

        if (!badge) {

            badge = document.createElement(
                "span"
            );

            badge.className =
                "notification-badge";

            badge.hidden = true;
            badge.textContent = "0";

            trigger.appendChild(badge);
        }

        badge.style.pointerEvents = "none";

        return badge;
    };

    const updateBadge = async () => {

        const triggers =
            document.querySelectorAll(
                ".notification-nav"
            );

        if (!triggers.length) {
            return;
        }

        triggers.forEach(
            ensureBadge
        );

        try {

            const response =
                await fetch(
                    "notifikasi.php?ajax=count",
                    {
                        method: "GET",
                        cache: "no-store",
                        credentials: "same-origin",
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                );

            if (!response.ok) {
                return;
            }

            const data =
                await response.json();

            const total =
                Number(
                    data.total || 0
                );

            document
                .querySelectorAll(
                    ".notification-nav"
                )
                .forEach(
                    (trigger) => {

                        const badge =
                            ensureBadge(
                                trigger
                            );

                        if (!badge) {
                            return;
                        }

                        if (total > 0) {

                            badge.hidden =
                                false;

                            badge.textContent =
                                total > 99
                                    ? "99+"
                                    : String(
                                        total
                                    );

                        } else {

                            badge.hidden =
                                true;

                            badge.textContent =
                                "0";
                        }
                    }
                );

        } catch (error) {
            return;
        }
    };

    const createPanel = () => {

        if (notificationPanel) {
            return;
        }

        notificationPanel =
            document.createElement(
                "aside"
            );

        notificationPanel.className =
            "notification-panel";

        notificationPanel.innerHTML = `
            <div class="notification-panel-header">

                <div class="notification-panel-title">

                    <div class="notification-panel-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>

                    <div class="notification-panel-heading">

                        <span>
                            AKTIVITAS
                        </span>

                        <h2>
                            Notifikasi
                        </h2>

                    </div>

                </div>

                <button
                    type="button"
                    class="notification-panel-close"
                    aria-label="Tutup notifikasi"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

            <div class="notification-panel-filters">

                <button
                    type="button"
                    class="notification-panel-filter active"
                    data-filter="all"
                >
                    Semua
                </button>

                <button
                    type="button"
                    class="notification-panel-filter"
                    data-filter="like"
                >
                    <i class="bi bi-heart"></i>
                    Suka
                </button>

                <button
                    type="button"
                    class="notification-panel-filter"
                    data-filter="comment"
                >
                    <i class="bi bi-chat"></i>
                    Komentar
                </button>

                <button
                    type="button"
                    class="notification-panel-filter"
                    data-filter="favorite"
                >
                    <i class="bi bi-bookmark"></i>
                    Favorit
                </button>

            </div>

            <div class="notification-panel-content">

                <div class="notification-panel-loading">

                    <span></span>

                    Memuat notifikasi...

                </div>

            </div>

            <div class="notification-panel-footer">

                <a
                    href="notifikasi.php"
                    class="notification-view-all"
                >
                    Lihat semua notifikasi
                    <i class="bi bi-arrow-right"></i>
                </a>

            </div>
        `;

        document.body.appendChild(
            notificationPanel
        );

        const closeButton =
            notificationPanel.querySelector(
                ".notification-panel-close"
            );

        if (closeButton) {

            closeButton.addEventListener(
                "click",
                (event) => {

                    event.preventDefault();
                    event.stopPropagation();

                    closePanel();
                }
            );
        }

        const filterButtons =
            notificationPanel.querySelectorAll(
                ".notification-panel-filter"
            );

        filterButtons.forEach(
            (button) => {

                button.addEventListener(
                    "click",
                    (event) => {

                        event.preventDefault();
                        event.stopPropagation();

                        filterButtons.forEach(
                            (item) => {
                                item.classList.remove(
                                    "active"
                                );
                            }
                        );

                        button.classList.add(
                            "active"
                        );

                        filterPanel(
                            button.dataset.filter ||
                            "all"
                        );
                    }
                );
            }
        );

        const viewAll =
            notificationPanel.querySelector(
                ".notification-view-all"
            );

        if (viewAll) {

            viewAll.addEventListener(
                "click",
                (event) => {

                    event.preventDefault();
                    event.stopPropagation();

                    window.location.href =
                        getNotificationUrl();
                }
            );
        }
    };

    const positionPanel = () => {

        if (
            !notificationPanel ||
            !activeTrigger ||
            !notificationPanel.classList.contains(
                "open"
            ) ||
            isMobile()
        ) {
            return;
        }

        const triggerRect =
            activeTrigger.getBoundingClientRect();

        const width =
            Math.min(
                390,
                window.innerWidth - 24
            );

        const height =
            Math.min(
                650,
                window.innerHeight - 90
            );

        let right =
            window.innerWidth -
            triggerRect.right;

        let top =
            triggerRect.bottom +
            9;

        if (right < 12) {
            right = 12;
        }

        if (
            right + width >
            window.innerWidth - 12
        ) {
            right =
                window.innerWidth -
                width -
                12;
        }

        if (
            top + height >
            window.innerHeight - 12
        ) {
            top =
                triggerRect.top -
                height -
                9;
        }

        if (top < 12) {
            top = 12;
        }

        notificationPanel.style.width =
            `${width}px`;

        notificationPanel.style.maxHeight =
            `${height}px`;

        notificationPanel.style.right =
            `${right}px`;

        notificationPanel.style.top =
            `${top}px`;
    };

    const openPanel = async (trigger) => {

        if (
            isMobile() ||
            !trigger
        ) {
            return;
        }

        createPanel();

        activeTrigger =
            trigger;

        positionPanel();

        notificationPanel.classList.add(
            "open"
        );

        const icon =
            trigger.querySelector(
                "i"
            );

        if (icon) {

            icon.classList.add(
                "active"
            );
        }

        await loadPanel();
    };

    const closePanel = () => {

        if (!notificationPanel) {
            return;
        }

        notificationPanel.classList.remove(
            "open"
        );

        if (activeTrigger) {

            const icon =
                activeTrigger.querySelector(
                    "i"
                );

            if (icon) {

                icon.classList.remove(
                    "active"
                );
            }
        }

        activeTrigger = null;
    };

    const loadPanel = async () => {

        if (!notificationPanel) {
            return;
        }

        if (panelController) {
            panelController.abort();
        }

        panelController =
            new AbortController();

        const content =
            notificationPanel.querySelector(
                ".notification-panel-content"
            );

        content.innerHTML = `
            <div class="notification-panel-loading">
                <span></span>
                Memuat notifikasi...
            </div>
        `;

        try {

            const response =
                await fetch(
                    "notifikasi.php?ajax=panel",
                    {
                        method: "GET",
                        cache: "no-store",
                        credentials: "same-origin",
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        },
                        signal:
                            panelController.signal
                    }
                );

            if (
                response.status ===
                401
            ) {

                window.location.href =
                    "login.php";

                return;
            }

            if (!response.ok) {

                throw new Error(
                    "Gagal memuat notifikasi"
                );
            }

            const html =
                await response.text();

            content.innerHTML =
                html;

            bindNotificationItems(
                content
            );

            filterPanel(
                "all"
            );

        } catch (error) {

            if (
                error.name ===
                "AbortError"
            ) {
                return;
            }

            content.innerHTML = `
                <div class="notification-empty">

                    <div class="notification-empty-icon">
                        <i class="bi bi-bell"></i>
                    </div>

                    <h3>
                        Gagal memuat notifikasi
                    </h3>

                    <p>
                        Silakan coba lagi.
                    </p>

                </div>
            `;
        }
    };

    const filterPanel = (filter) => {

        if (!notificationPanel) {
            return;
        }

        notificationPanel
            .querySelectorAll(
                ".notification-group"
            )
            .forEach(
                (group) => {

                    let visible = 0;

                    group
                        .querySelectorAll(
                            ".notification-item"
                        )
                        .forEach(
                            (item) => {

                                const type =
                                    item.dataset.type ||
                                    "activity";

                                const match =
                                    filter === "all" ||
                                    type === filter;

                                item.style.display =
                                    match
                                        ? ""
                                        : "none";

                                if (match) {
                                    visible++;
                                }
                            }
                        );

                    group.style.display =
                        visible > 0
                            ? ""
                            : "none";
                }
            );
    };

    const markAsRead = async (id) => {

        if (!id) {
            return;
        }

        const body =
            new URLSearchParams();

        body.append(
            "id_notifikasi",
            id
        );

        try {

            await fetch(
                "notifikasi.php?ajax=read",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type":
                            "application/x-www-form-urlencoded",
                        "X-Requested-With":
                            "XMLHttpRequest"
                    },
                    body
                }
            );

            updateBadge();

        } catch (error) {
            return;
        }
    };

    const bindNotificationItems =
        (scope) => {

            if (!scope) {
                return;
            }

            scope
                .querySelectorAll(
                    ".notification-item"
                )
                .forEach(
                    (item) => {

                        if (
                            item.dataset
                                .notificationBound ===
                            "1"
                        ) {
                            return;
                        }

                        item.dataset
                            .notificationBound =
                            "1";

                        item.addEventListener(
                            "click",
                            () => {

                                const id =
                                    item.dataset
                                        .notificationId;

                                item.classList.remove(
                                    "unread"
                                );

                                markAsRead(
                                    id
                                );
                            }
                        );
                    }
                );
        };

    const filterList = (
        scope,
        filter
    ) => {

        if (!scope) {
            return;
        }

        scope
            .querySelectorAll(
                ".notification-group"
            )
            .forEach(
                (group) => {

                    let visible = 0;

                    group
                        .querySelectorAll(
                            ".notification-item"
                        )
                        .forEach(
                            (item) => {

                                const type =
                                    item.dataset.type ||
                                    "activity";

                                const match =
                                    filter === "all" ||
                                    type === filter;

                                item.style.display =
                                    match
                                        ? ""
                                        : "none";

                                if (match) {
                                    visible++;
                                }
                            }
                        );

                    group.style.display =
                        visible > 0
                            ? ""
                            : "none";
                }
            );
    };

    const handleNotificationTrigger =
        (event) => {

            let trigger = null;

            if (
                event.target &&
                typeof event.target.closest ===
                    "function"
            ) {

                trigger =
                    event.target.closest(
                        ".notification-nav"
                    );
            }

            if (!trigger) {
                return;
            }

            if (
                !document.body.contains(
                    trigger
                )
            ) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (
                typeof event.stopImmediatePropagation ===
                "function"
            ) {
                event.stopImmediatePropagation();
            }

            if (isMobile()) {

                window.location.href =
                    getNotificationUrl();

                return;
            }

            if (
                notificationPanel &&
                notificationPanel.classList.contains(
                    "open"
                ) &&
                activeTrigger ===
                    trigger
            ) {

                closePanel();

                return;
            }

            openPanel(
                trigger
            );
        };

    const handleOutsideClick =
        (event) => {

            if (
                !notificationPanel ||
                !notificationPanel.classList.contains(
                    "open"
                )
            ) {
                return;
            }

            if (
                notificationPanel.contains(
                    event.target
                )
            ) {
                return;
            }

            if (
                activeTrigger &&
                activeTrigger.contains(
                    event.target
                )
            ) {
                return;
            }

            closePanel();
        };

    const handleEscape =
        (event) => {

            if (
                event.key ===
                "Escape"
            ) {
                closePanel();
            }
        };

    const bindPageControls = () => {

        const notificationList =
            document.getElementById(
                "notificationList"
            );

        if (notificationList) {

            bindNotificationItems(
                notificationList
            );

            const filterButtons =
                document.querySelectorAll(
                    ".notification-filter"
                );

            filterButtons.forEach(
                (button) => {

                    if (
                        button.dataset
                            .filterBound ===
                        "1"
                    ) {
                        return;
                    }

                    button.dataset
                        .filterBound =
                        "1";

                    button.addEventListener(
                        "click",
                        () => {

                            filterButtons.forEach(
                                (item) => {
                                    item.classList.remove(
                                        "active"
                                    );
                                }
                            );

                            button.classList.add(
                                "active"
                            );

                            filterList(
                                notificationList,
                                button.dataset
                                    .filter ||
                                    "all"
                            );
                        }
                    );
                }
            );
        }

        const backButton =
            document.getElementById(
                "notificationBack"
            );

        if (
            backButton &&
            backButton.dataset
                .backBound !==
            "1"
        ) {

            backButton.dataset
                .backBound =
                "1";

            backButton.addEventListener(
                "click",
                (event) => {

                    event.preventDefault();
                    event.stopPropagation();

                    const returnUrl =
                        backButton.dataset
                            .returnUrl;

                    if (
                        returnUrl &&
                        returnUrl !==
                        window.location.href
                    ) {

                        window.location.replace(
                            returnUrl
                        );

                        return;
                    }

                    if (
                        document.referrer &&
                        !document.referrer.includes(
                            "notifikasi.php"
                        )
                    ) {

                        window.location.replace(
                            document.referrer
                        );

                        return;
                    }

                    window.location.replace(
                        "index.php"
                    );
                }
            );
        }

        const pageClose =
            document.getElementById(
                "notificationPageClose"
            );

        if (
            pageClose &&
            pageClose.dataset
                .closeBound !==
            "1"
        ) {

            pageClose.dataset
                .closeBound =
                "1";

            pageClose.addEventListener(
                "click",
                (event) => {

                    event.preventDefault();
                    event.stopPropagation();

                    const returnUrl =
                        pageClose.dataset
                            .returnUrl ||
                        pageClose.getAttribute(
                            "href"
                        );

                    if (returnUrl) {

                        window.location.replace(
                            returnUrl
                        );
                    }
                }
            );
        }
    };

    document.addEventListener(
        "click",
        handleNotificationTrigger,
        true
    );

    document.addEventListener(
        "mousedown",
        handleOutsideClick
    );

    document.addEventListener(
        "keydown",
        handleEscape
    );

    window.addEventListener(
        "resize",
        positionPanel
    );

    window.addEventListener(
        "scroll",
        positionPanel,
        true
    );

    const initialize = () => {

        document
            .querySelectorAll(
                ".notification-nav"
            )
            .forEach(
                ensureBadge
            );

        bindPageControls();

        updateBadge();

        if (badgeInterval) {
            clearInterval(
                badgeInterval
            );
        }

        badgeInterval =
            setInterval(
                updateBadge,
                10000
            );
    };

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize,
            {
                once: true
            }
        );

    } else {

        initialize();
    }

})();
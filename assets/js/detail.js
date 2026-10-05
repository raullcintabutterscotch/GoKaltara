document.addEventListener("DOMContentLoaded", function () {

    const body = document.body;

    const idKuliner =
        Number(body.dataset.kulinerId || 0);

    const idUser =
        Number(body.dataset.userId || 0);

    const isLoggedIn =
        body.dataset.loggedIn === "1";

    const favoriteButton =
        document.getElementById(
            "favoriteButton"
        );

    const favoriteText =
        document.getElementById(
            "favoriteText"
        );

    const favoriteCount =
        document.getElementById(
            "favoriteCount"
        );

    const ratingInput =
        document.getElementById(
            "ratingInput"
        );

    const ratingMessage =
        document.getElementById(
            "ratingMessage"
        );

    const commentInput =
        document.getElementById(
            "commentInput"
        );

    const submitComment =
        document.getElementById(
            "submitComment"
        );

    const commentMessage =
        document.getElementById(
            "commentMessage"
        );

    const commentList =
        document.getElementById(
            "commentList"
        );

    if (!idKuliner) {
        return;
    }

    async function request(
        action,
        data = {}
    ) {

        const formData =
            new FormData();

        formData.append(
            "ajax",
            "1"
        );

        formData.append(
            "action",
            action
        );

        Object.keys(data).forEach(
            function (key) {

                formData.append(
                    key,
                    data[key]
                );

            }
        );

        const response =
            await fetch(
                "detail.php?id=" +
                encodeURIComponent(
                    idKuliner
                ),
                {
                    method: "POST",
                    body: formData
                }
            );

        return await response.json();
    }

    if (
        favoriteButton &&
        isLoggedIn
    ) {

        favoriteButton.addEventListener(
            "click",
            async function () {

                favoriteButton.disabled =
                    true;

                try {

                    const result =
                        await request(
                            "favorit"
                        );

                    if (!result.success) {

                        alert(
                            result.message
                        );

                        return;
                    }

                    favoriteCount.textContent =
                        result.total;

                    const icon =
                        favoriteButton.querySelector(
                            "i"
                        );

                    if (result.favorit) {

                        favoriteButton.classList.add(
                            "active"
                        );

                        favoriteText.textContent =
                            "Hapus Favorit";

                        icon.className =
                            "bi bi-heart-fill";

                    } else {

                        favoriteButton.classList.remove(
                            "active"
                        );

                        favoriteText.textContent =
                            "Tambah Favorit";

                        icon.className =
                            "bi bi-heart";

                    }

                } catch (error) {

                    alert(
                        "Gagal mengubah favorit."
                    );

                } finally {

                    favoriteButton.disabled =
                        false;
                }

            }
        );

    }

    function updateRatingVisual(
        value
    ) {

        if (!ratingInput) {
            return;
        }

        ratingInput
            .querySelectorAll(
                ".rating-star"
            )
            .forEach(
                function (
                    star,
                    index
                ) {

                    star.classList.toggle(
                        "active",
                        index + 1 <= value
                    );

                }
            );
    }

    function updateRatingSummary(
        average,
        total
    ) {

        const averageElement =
            document.getElementById(
                "ratingAverage"
            );

        const totalElement =
            document.getElementById(
                "ratingTotal"
            );

        const stars =
            document.querySelector(
                ".rating-stars-static"
            );

        if (averageElement) {

            averageElement.textContent =
                Number(
                    average || 0
                ).toFixed(1);
        }

        if (totalElement) {

            totalElement.textContent =
                "(" +
                Number(
                    total || 0
                ) +
                " rating)";
        }

        if (stars) {

            stars.innerHTML = "";

            const rounded =
                Math.round(
                    Number(
                        average || 0
                    )
                );

            for (
                let i = 1;
                i <= 5;
                i++
            ) {

                const icon =
                    document.createElement(
                        "i"
                    );

                icon.className =
                    i <= rounded
                        ? "bi bi-star-fill"
                        : "bi bi-star";

                stars.appendChild(
                    icon
                );
            }
        }
    }

    if (
        ratingInput &&
        isLoggedIn
    ) {

        ratingInput
            .querySelectorAll(
                ".rating-star"
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        "mouseenter",
                        function () {

                            updateRatingVisual(
                                Number(
                                    this.dataset.rating
                                )
                            );

                        }
                    );

                    button.addEventListener(
                        "click",
                        async function () {

                            const value =
                                Number(
                                    this.dataset.rating
                                );

                            const current =
                                Number(
                                    ratingInput.dataset.current ||
                                    0
                                );

                            const next =
                                current === value
                                    ? 0
                                    : value;

                            button.disabled =
                                true;

                            try {

                                const result =
                                    await request(
                                        "rating",
                                        {
                                            rating:
                                                next
                                        }
                                    );

                                if (
                                    !result.success
                                ) {

                                    ratingMessage.textContent =
                                        result.message;

                                    return;
                                }

                                ratingInput.dataset.current =
                                    String(
                                        next
                                    );

                                updateRatingVisual(
                                    next
                                );

                                updateRatingSummary(
                                    result.average,
                                    result.total
                                );

                                ratingMessage.textContent =
                                    next === 0
                                        ? "Rating dibatalkan."
                                        : "Rating berhasil disimpan.";

                            } catch (error) {

                                ratingMessage.textContent =
                                    "Rating gagal diperbarui.";

                            } finally {

                                button.disabled =
                                    false;
                            }

                        }
                    );

                }
            );

        ratingInput.addEventListener(
            "mouseleave",
            function () {

                updateRatingVisual(
                    Number(
                        ratingInput.dataset.current ||
                        0
                    )
                );

            }
        );
    }

    function createAvatar(
        data
    ) {

        const foto =
            data.foto_url || "";

        const nama =
            data.nama_lengkap ||
            data.username ||
            "Pengguna";

        if (foto) {

            const image =
                document.createElement(
                    "img"
                );

            image.src =
                foto;

            image.alt =
                "Foto profil";

            image.className =
                "comment-avatar";

            return image;
        }

        const fallback =
            document.createElement(
                "div"
            );

        fallback.className =
            "comment-avatar comment-avatar-fallback";

        fallback.textContent =
            nama
                .substring(
                    0,
                    1
                )
                .toUpperCase();

        return fallback;
    }

    function createCommentElement(
        data
    ) {

        const item =
            document.createElement(
                "article"
            );

        item.className =
            "comment-item";

        item.id =
            "comment-" +
            data.id_komentar;

        item.dataset.commentId =
            data.id_komentar;

        const main =
            document.createElement(
                "div"
            );

        main.className =
            "comment-main";

        main.appendChild(
            createAvatar(
                data
            )
        );

        const body =
            document.createElement(
                "div"
            );

        body.className =
            "comment-body";

        const top =
            document.createElement(
                "div"
            );

        top.className =
            "comment-top";

        const user =
            document.createElement(
                "div"
            );

        user.className =
            "comment-user";

        const name =
            document.createElement(
                "strong"
            );

        name.textContent =
            data.nama_lengkap ||
            data.username ||
            "Pengguna";

        const role =
            document.createElement(
                "span"
            );

        role.className =
            "comment-role";

        role.textContent =
            String(
                data.level ||
                "user"
            ).toLowerCase() ===
            "admin"
                ? "Admin"
                : "Pengguna";

        user.appendChild(
            name
        );

        user.appendChild(
            role
        );

        const date =
            document.createElement(
                "small"
            );

        date.textContent =
            data.created_at ||
            "";

        top.appendChild(
            user
        );

        top.appendChild(
            date
        );

        const text =
            document.createElement(
                "p"
            );

        text.className =
            "comment-text";

        text.textContent =
            data.komentar ||
            "";

        const actions =
            document.createElement(
                "div"
            );

        actions.className =
            "comment-actions";

        const like =
            document.createElement(
                "button"
            );

        like.type =
            "button";

        like.className =
            "comment-like" +
            (
                data.user_like
                    ? " liked"
                    : ""
            );

        like.dataset.id =
            data.id_komentar;

        like.innerHTML =
            `
            <i class="bi ${
                data.user_like
                    ? "bi-heart-fill"
                    : "bi-heart"
            }"></i>
            <span class="like-count">
                ${Number(
                    data.total_like ||
                    0
                )}
            </span>
            `;

        actions.appendChild(
            like
        );

        if (isLoggedIn) {

            const reply =
                document.createElement(
                    "button"
                );

            reply.type =
                "button";

            reply.className =
                "comment-reply";

            reply.dataset.id =
                data.id_komentar;

            reply.innerHTML =
                '<i class="bi bi-reply"></i> Balas';

            actions.appendChild(
                reply
            );

            if (
                Number(
                    data.id_user
                ) ===
                idUser
            ) {

                const edit =
                    document.createElement(
                        "button"
                    );

                edit.type =
                    "button";

                edit.className =
                    "comment-edit";

                edit.dataset.id =
                    data.id_komentar;

                edit.innerHTML =
                    '<i class="bi bi-pencil"></i> Edit';

                actions.appendChild(
                    edit
                );

                const remove =
                    document.createElement(
                        "button"
                    );

                remove.type =
                    "button";

                remove.className =
                    "comment-delete";

                remove.dataset.id =
                    data.id_komentar;

                remove.innerHTML =
                    '<i class="bi bi-trash3"></i> Hapus';

                actions.appendChild(
                    remove
                );
            }
        }

        const slot =
            document.createElement(
                "div"
            );

        slot.className =
            "reply-form-slot";

        body.appendChild(
            top
        );

        body.appendChild(
            text
        );

        body.appendChild(
            actions
        );

        body.appendChild(
            slot
        );

        main.appendChild(
            body
        );

        item.appendChild(
            main
        );

        return item;
    }

    function bindActions(
        element
    ) {

        const like =
            element.querySelector(
                ".comment-like"
            );

        const reply =
            element.querySelector(
                ".comment-reply"
            );

        const edit =
            element.querySelector(
                ".comment-edit"
            );

        const remove =
            element.querySelector(
                ".comment-delete"
            );

        if (like) {

            like.addEventListener(
                "click",
                function () {

                    likeComment(
                        like
                    );

                }
            );
        }

        if (reply) {

            reply.addEventListener(
                "click",
                function () {

                    showReplyForm(
                        element,
                        Number(
                            this.dataset.id
                        )
                    );

                }
            );
        }

        if (edit) {

            edit.addEventListener(
                "click",
                function () {

                    editComment(
                        element,
                        Number(
                            this.dataset.id
                        )
                    );

                }
            );
        }

        if (remove) {

            remove.addEventListener(
                "click",
                function () {

                    deleteComment(
                        element,
                        Number(
                            this.dataset.id
                        )
                    );

                }
            );
        }
    }

    async function likeComment(
        button
    ) {

        button.disabled =
            true;

        try {

            const result =
                await request(
                    "komentar_like",
                    {
                        id_komentar:
                            button.dataset.id
                    }
                );

            if (!result.success) {

                alert(
                    result.message
                );

                return;
            }

            button.classList.toggle(
                "liked",
                result.liked
            );

            const icon =
                button.querySelector(
                    "i"
                );

            const count =
                button.querySelector(
                    ".like-count"
                );

            icon.className =
                result.liked
                    ? "bi bi-heart-fill"
                    : "bi bi-heart";

            count.textContent =
                result.total;

        } catch (error) {

            alert(
                "Gagal memberi like."
            );

        } finally {

            button.disabled =
                false;
        }
    }

    function showReplyForm(
        element,
        parentId
    ) {

        const slot =
            element.querySelector(
                ".reply-form-slot"
            );

        if (!slot) {
            return;
        }

        if (
            slot.querySelector(
                ".reply-form"
            )
        ) {

            slot.innerHTML =
                "";

            return;
        }

        const form =
            document.createElement(
                "div"
            );

        form.className =
            "reply-form";

        form.innerHTML =
            `
            <textarea
                maxlength="1000"
                placeholder="Tulis balasan..."
            ></textarea>

            <div class="reply-form-footer">

                <span>
                    Membalas komentar
                </span>

                <div>

                    <button
                        type="button"
                        class="reply-cancel"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="reply-submit"
                    >
                        Kirim
                    </button>

                </div>

            </div>
            `;

        slot.appendChild(
            form
        );

        const textarea =
            form.querySelector(
                "textarea"
            );

        const cancel =
            form.querySelector(
                ".reply-cancel"
            );

        const submit =
            form.querySelector(
                ".reply-submit"
            );

        cancel.addEventListener(
            "click",
            function () {

                slot.innerHTML =
                    "";

            }
        );

        submit.addEventListener(
            "click",
            async function () {

                const komentar =
                    textarea.value.trim();

                if (
                    komentar.length < 3
                ) {

                    alert(
                        "Balasan minimal 3 karakter."
                    );

                    return;
                }

                submit.disabled =
                    true;

                try {

                    const result =
                        await request(
                            "komentar_tambah",
                            {
                                komentar:
                                    komentar,

                                parent_id:
                                    parentId
                            }
                        );

                    if (!result.success) {

                        alert(
                            result.message
                        );

                        return;
                    }

                    slot.innerHTML =
                        "";

                    addReply(
                        result.data
                    );

                } catch (error) {

                    alert(
                        "Balasan gagal dikirim."
                    );

                } finally {

                    submit.disabled =
                        false;
                }

            }
        );

        textarea.focus();
    }

    function removeEmpty() {

        const empty =
            commentList.querySelector(
                ".comment-empty"
            );

        if (empty) {
            empty.remove();
        }
    }

    function getRootThread(
        parentId
    ) {

        const parent =
            commentList.querySelector(
                '[data-comment-id="' +
                parentId +
                '"]'
            );

        if (!parent) {
            return null;
        }

        return parent.closest(
            ".comment-thread"
        );
    }

    function addReply(
        data
    ) {

        removeEmpty();

        const parentId =
            Number(
                data.parent_id ||
                0
            );

        const thread =
            getRootThread(
                parentId
            );

        if (!thread) {
            return;
        }

        let replies =
            thread.querySelector(
                ".comment-replies"
            );

        if (!replies) {

            replies =
                document.createElement(
                    "div"
                );

            replies.className =
                "comment-replies open";

            replies.id =
                "replies-" +
                thread.dataset.commentId;

            thread.appendChild(
                replies
            );
        }

        replies.classList.add(
            "open"
        );

        const reply =
            createCommentElement(
                data
            );

        replies.appendChild(
            reply
        );

        bindActions(
            reply
        );

        let toggle =
            thread.querySelector(
                ".reply-toggle"
            );

        const total =
            replies.querySelectorAll(
                ".reply-item, .comment-item"
            ).length;

        if (!toggle) {

            const wrapper =
                document.createElement(
                    "div"
                );

            wrapper.className =
                "reply-toggle-wrap";

            toggle =
                document.createElement(
                    "button"
                );

            toggle.type =
                "button";

            toggle.className =
                "reply-toggle";

            toggle.dataset.target =
                thread.dataset.commentId;

            toggle.dataset.open =
                "1";

            toggle.textContent =
                "Sembunyikan balasan";

            wrapper.appendChild(
                toggle
            );

            const root =
                thread.querySelector(
                    ":scope > .comment-item"
                );

            root.after(
                wrapper
            );

            toggle.addEventListener(
                "click",
                toggleReplies
            );

        } else {

            toggle.dataset.open =
                "1";

            toggle.textContent =
                "Sembunyikan balasan";
        }

        reply.scrollIntoView({
            behavior: "smooth",
            block: "nearest"
        });
    }

    function toggleReplies(
        event
    ) {

        const button =
            event.currentTarget;

        const rootId =
            button.dataset.target;

        const replies =
            document.getElementById(
                "replies-" +
                rootId
            );

        if (!replies) {
            return;
        }

        const open =
            button.dataset.open ===
            "1";

        if (open) {

            replies.classList.remove(
                "open"
            );

            const total =
                replies.querySelectorAll(
                    ".comment-item"
                ).length;

            button.textContent =
                "Lihat " +
                total +
                " balasan";

            button.dataset.open =
                "0";

        } else {

            replies.classList.add(
                "open"
            );

            button.textContent =
                "Sembunyikan balasan";

            button.dataset.open =
                "1";
        }
    }

    document
        .querySelectorAll(
            ".reply-toggle"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    toggleReplies
                );

            }
        );

    if (
        submitComment &&
        commentInput &&
        isLoggedIn
    ) {

        submitComment.addEventListener(
            "click",
            async function () {

                const komentar =
                    commentInput.value.trim();

                if (
                    komentar.length < 3
                ) {

                    commentMessage.textContent =
                        "Komentar minimal 3 karakter.";

                    return;
                }

                submitComment.disabled =
                    true;

                try {

                    const result =
                        await request(
                            "komentar_tambah",
                            {
                                komentar:
                                    komentar,

                                parent_id:
                                    0
                            }
                        );

                    if (!result.success) {

                        commentMessage.textContent =
                            result.message;

                        return;
                    }

                    commentInput.value =
                        "";

                    commentMessage.textContent =
                        result.message;

                    removeEmpty();

                    const thread =
                        document.createElement(
                            "article"
                        );

                    thread.className =
                        "comment-thread";

                    thread.id =
                        "comment-" +
                        result.data.id_komentar;

                    thread.dataset.commentId =
                        result.data.id_komentar;

                    const comment =
                        createCommentElement(
                            result.data
                        );

                    thread.appendChild(
                        comment
                    );

                    commentList.prepend(
                        thread
                    );

                    bindActions(
                        comment
                    );

                } catch (error) {

                    commentMessage.textContent =
                        "Komentar gagal dikirim.";

                } finally {

                    submitComment.disabled =
                        false;
                }

            }
        );
    }

    async function editComment(
        element,
        idKomentar
    ) {

        const text =
            element.querySelector(
                ".comment-text"
            );

        if (!text) {
            return;
        }

        const oldText =
            text.textContent.trim();

        const value =
            window.prompt(
                "Edit komentar:",
                oldText
            );

        if (value === null) {
            return;
        }

        const komentar =
            value.trim();

        if (
            komentar.length < 3
        ) {

            alert(
                "Komentar minimal 3 karakter."
            );

            return;
        }

        try {

            const result =
                await request(
                    "komentar_edit",
                    {
                        id_komentar:
                            idKomentar,

                        komentar:
                            komentar
                    }
                );

            if (!result.success) {

                alert(
                    result.message
                );

                return;
            }

            text.textContent =
                result.komentar;

        } catch (error) {

            alert(
                "Komentar gagal diedit."
            );
        }
    }

    async function deleteComment(
        element,
        idKomentar
    ) {

        if (
            !window.confirm(
                "Hapus komentar ini?"
            )
        ) {
            return;
        }

        try {

            const result =
                await request(
                    "komentar_hapus",
                    {
                        id_komentar:
                            idKomentar
                    }
                );

            if (!result.success) {

                alert(
                    result.message
                );

                return;
            }

            const thread =
                element.closest(
                    ".comment-thread"
                );

            if (
                element.classList.contains(
                    "reply-item"
                )
            ) {

                element.remove();

                const replies =
                    thread.querySelector(
                        ".comment-replies"
                    );

                if (
                    replies &&
                    replies.querySelectorAll(
                        ".comment-item"
                    ).length === 0
                ) {

                    const toggle =
                        thread.querySelector(
                            ".reply-toggle-wrap"
                        );

                    if (toggle) {
                        toggle.remove();
                    }

                    replies.remove();
                }

            } else {

                thread.remove();
            }

            if (
                !commentList.querySelector(
                    ".comment-thread"
                )
            ) {

                commentList.innerHTML =
                    `
                    <div class="comment-empty">

                        <i class="bi bi-chat-left-text"></i>

                        <span>
                            Belum ada komentar.
                        </span>

                    </div>
                    `;
            }

        } catch (error) {

            alert(
                "Komentar gagal dihapus."
            );
        }
    }

    document
        .querySelectorAll(
            ".comment-item"
        )
        .forEach(
            function (element) {

                bindActions(
                    element
                );

            }
        );

    const notificationModal =
        document.getElementById(
            "notificationModal"
        );

    const notificationAllow =
        document.getElementById(
            "notificationAllow"
        );

    const notificationLater =
        document.getElementById(
            "notificationLater"
        );

    const notificationKey =
        "gokaltara_notification_prompt_" +
        idUser;

    function closeNotification() {

        if (notificationModal) {

            notificationModal.classList.remove(
                "show"
            );
        }
    }

    if (
        isLoggedIn &&
        notificationModal &&
        "Notification" in window
    ) {

        const asked =
            localStorage.getItem(
                notificationKey
            );

        if (
            !asked &&
            Notification.permission !==
            "granted"
        ) {

            setTimeout(
                function () {

                    notificationModal.classList.add(
                        "show"
                    );

                },
                800
            );
        }
    }

    if (notificationLater) {

        notificationLater.addEventListener(
            "click",
            function () {

                localStorage.setItem(
                    notificationKey,
                    "1"
                );

                closeNotification();

            }
        );
    }

    if (notificationAllow) {

        notificationAllow.addEventListener(
            "click",
            async function () {

                localStorage.setItem(
                    notificationKey,
                    "1"
                );

                if (
                    "Notification" in window
                ) {

                    try {

                        await Notification.requestPermission();

                    } catch (error) {
                    }
                }

                closeNotification();

            }
        );
    }

    let lastNotificationId =
        Number(
            localStorage.getItem(
                "gokaltara_notification_last_" +
                idUser
            ) || 0
        );

    async function pollNotifications(
        firstLoad
    ) {

        if (
            !isLoggedIn ||
            idUser <= 0
        ) {
            return;
        }

        try {

            const response =
                await fetch(
                    "notifikasi.php?ajax=poll&after=" +
                    lastNotificationId,
                    {
                        cache: "no-store"
                    }
                );

            if (!response.ok) {
                return;
            }

            const result =
                await response.json();

            if (
                !result.success ||
                !Array.isArray(
                    result.data
                )
            ) {
                return;
            }

            result.data.forEach(
                function (item) {

                    const currentId =
                        Number(
                            item.id_notifikasi
                        );

                    if (
                        currentId >
                        lastNotificationId
                    ) {

                        lastNotificationId =
                            currentId;
                    }

                    if (
                        !firstLoad &&
                        "Notification" in window &&
                        Notification.permission ===
                        "granted"
                    ) {

                        const n =
                            new Notification(
                                "GoKaltara Kuliner",
                                {
                                    body:
                                        item.pesan,

                                    icon:
                                        "assets/images/logo.svg"
                                }
                            );

                        n.onclick =
                            function () {

                                window.focus();

                                window.location.href =
                                    "detail.php?id=" +
                                    encodeURIComponent(
                                        item.id_kuliner
                                    ) +
                                    "#comment-" +
                                    encodeURIComponent(
                                        item.id_komentar
                                    );

                            };
                    }

                }
            );

            localStorage.setItem(
                "gokaltara_notification_last_" +
                idUser,
                String(
                    lastNotificationId
                )
            );

        } catch (error) {
        }
    }

    if (isLoggedIn) {

        pollNotifications(
            true
        );

        setInterval(
            function () {

                pollNotifications(
                    false
                );

            },
            5000
        );
    }

});
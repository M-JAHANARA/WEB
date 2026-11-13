/* =========================================================
                           GLOBAL
========================================================= */

document.addEventListener("DOMContentLoaded", () => {


    /* =====================================================
                        ACTIVE PAGE
    ===================================================== */

    const currentPath = window.location.pathname;

    let currentPage = currentPath
        .substring(currentPath.lastIndexOf("/") + 1)
        .toLowerCase();


    if (currentPage === "") {
        currentPage = "index.php";
    }


    const activeMenuLinks =
        document.querySelectorAll(".menu a");


    activeMenuLinks.forEach(link => {

        const href = link.getAttribute("href");


        if (!href || href === "#") {

            link.classList.remove("active");

            return;

        }


        const linkPage = href
            .split("/")
            .pop()
            .split("?")[0]
            .split("#")[0]
            .toLowerCase();


        link.classList.remove("active");


        if (linkPage === currentPage) {

            link.classList.add("active");

        }

    });


    /* =====================================================
                SECOND NAV SCROLL BEHAVIOR
    ===================================================== */

    const siteHeader =
        document.querySelector(".site-header");


    if (siteHeader) {

        const secondNav =
            siteHeader.querySelector(
                ".header-nav-container"
            );


        /*
         * در login و register نوار دوم
         * توسط PHP مخفی شده است.
         */

        const navIsHiddenByPage =
            secondNav &&
            secondNav.classList.contains(
                "page-nav-hidden"
            );


        if (secondNav && !navIsHiddenByPage) {

            let hideNavTimer = null;


            /* ---------------------------------------------
                        نمایش نوار دوم
            --------------------------------------------- */

            function showHeaderNav() {

                clearTimeout(hideNavTimer);

                siteHeader.classList.remove(
                    "nav-hidden"
                );

            }


            /* ---------------------------------------------
                        مخفی کردن نوار دوم
            --------------------------------------------- */

            function hideHeaderNav() {

                /*
                 * در ابتدای صفحه هرگز مخفی نشود.
                 */

                if (window.scrollY <= 5) {

                    showHeaderNav();

                    return;

                }


                siteHeader.classList.add(
                    "nav-hidden"
                );

            }


            /* ---------------------------------------------
                        مدیریت اسکرول
            --------------------------------------------- */

            function handleHeaderScroll() {

                /*
                 * ابتدای صفحه
                 */

                if (window.scrollY <= 5) {

                    clearTimeout(hideNavTimer);

                    showHeaderNav();

                    return;

                }


                /*
                 * با شروع اسکرول
                 * نوار دوم فوراً ظاهر شود.
                 */

                showHeaderNav();


                /*
                 * تایمر قبلی پاک شود.
                 */

                clearTimeout(hideNavTimer);


                /*
                 * بعد از 700 میلی‌ثانیه
                 * توقف اسکرول، مخفی شود.
                 */

                hideNavTimer = setTimeout(() => {

                    hideHeaderNav();

                }, 700);

            }


            window.addEventListener(
                "scroll",
                handleHeaderScroll,
                {
                    passive: true
                }
            );


            /* ---------------------------------------------
                        حالت اولیه
            --------------------------------------------- */

            if (window.scrollY <= 5) {

                showHeaderNav();

            } else {

                hideNavTimer = setTimeout(() => {

                    hideHeaderNav();

                }, 700);

            }

        }

    }


    /* =====================================================
                        MOBILE MENU
    ===================================================== */

    const mobileMenu =
        document.querySelector(".mobile-menu");


    const menu =
        document.querySelector(".menu");


    if (mobileMenu && menu) {

        mobileMenu.addEventListener(
            "click",
            () => {

                /*
                 * اگر صفحه login/register نیست،
                 * هنگام باز کردن منوی موبایل
                 * نوار دوم نمایش داده شود.
                 */

                const hiddenByPage =
                    document.querySelector(
                        ".header-nav-container.page-nav-hidden"
                    );


                if (siteHeader && !hiddenByPage) {

                    siteHeader.classList.remove(
                        "nav-hidden"
                    );

                }


                menu.classList.toggle("show");

                mobileMenu.classList.toggle(
                    "active"
                );

            }
        );


        const mobileMenuLinks =
            menu.querySelectorAll("a");


        mobileMenuLinks.forEach(link => {

            link.addEventListener(
                "click",
                () => {

                    menu.classList.remove(
                        "show"
                    );

                    mobileMenu.classList.remove(
                        "active"
                    );

                }
            );

        });

    }

    /* =====================================================
                    USER PROFILE DROPDOWN
===================================================== */

const userProfile =
    document.querySelector(".user-profile");

const userProfileButton =
    document.querySelector("#userProfileButton");

const userDropdown =
    document.querySelector("#userDropdown");


if (
    userProfile &&
    userProfileButton &&
    userDropdown
) {

    userProfileButton.addEventListener(
        "click",
        (event) => {

            event.stopPropagation();

            const isOpen =
                userProfile.classList.toggle("open");

            userProfileButton.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

        }
    );


    /* ---------------------------------------------
                    CLICK OUTSIDE
    --------------------------------------------- */

    document.addEventListener(
        "click",
        (event) => {

            if (
                !userProfile.contains(event.target)
            ) {

                userProfile.classList.remove(
                    "open"
                );

                userProfileButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }
    );


    /* ---------------------------------------------
                    ESCAPE
    --------------------------------------------- */

    document.addEventListener(
        "keydown",
        (event) => {

            if (event.key === "Escape") {

                userProfile.classList.remove(
                    "open"
                );

                userProfileButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }
    );

}

/* =========================================================
   PROFILE PAGE
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | فقط فارسی برای نام و نام خانوادگی
    |--------------------------------------------------------------------------
    */

    const persianInputs = document.querySelectorAll(
        "input[data-persian-only]"
    );

    persianInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            this.value = this.value.replace(
                /[^\u0600-\u06FF\uFB8A\u067E\u0686\u0698\u06AF\s]/g,
                ""
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | نمایش / مخفی کردن رمز
    |--------------------------------------------------------------------------
    */

    const passwordToggles = document.querySelectorAll(
        "[data-password-toggle]"
    );

    passwordToggles.forEach(function (button) {

        button.addEventListener("click", function () {

            const wrapper = this.closest(".profile-input-wrap");

            if (!wrapper) {
                return;
            }

            const input = wrapper.querySelector("input");

            if (!input) {
                return;
            }

            if (input.type === "password") {

                input.type = "text";

                this.classList.add("password-visible");

            } else {

                input.type = "password";

                this.classList.remove("password-visible");
            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | انتخاب تصویر پروفایل
    |--------------------------------------------------------------------------
    */

    const profileImageInput =
        document.getElementById("profileImageInput");

    const profileFileName =
        document.getElementById("profileFileName");

    const profileFormAvatar =
        document.getElementById("profileFormAvatar");

    const profileFormAvatarPlaceholder =
        document.getElementById(
            "profileFormAvatarPlaceholder"
        );

    const profileAvatarPreview =
        document.getElementById(
            "profileAvatarPreview"
        );

    const profileAvatarPlaceholder =
        document.getElementById(
            "profileAvatarPlaceholder"
        );


    if (profileImageInput) {

        profileImageInput.addEventListener(
            "change",
            function () {

                const file = this.files[0];

                if (!file) {
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | محدودیت حجم
                |--------------------------------------------------------------------------
                */

                const maxSize =
                    2 * 1024 * 1024;

                if (file.size > maxSize) {

                    alert(
                        "حجم تصویر نباید بیشتر از ۲ مگابایت باشد."
                    );

                    this.value = "";

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | فرمت
                |--------------------------------------------------------------------------
                */

                const allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                if (
                    !allowedTypes.includes(
                        file.type
                    )
                ) {

                    alert(
                        "فرمت تصویر باید JPG، PNG یا WEBP باشد."
                    );

                    this.value = "";

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | نام فایل
                |--------------------------------------------------------------------------
                */

                if (profileFileName) {

                    profileFileName.textContent =
                        file.name;
                }


                /*
                |--------------------------------------------------------------------------
                | Preview
                |--------------------------------------------------------------------------
                */

                const reader =
                    new FileReader();

                reader.onload = function (event) {

                    const imageUrl =
                        event.target.result;


                    if (profileFormAvatar) {

                        profileFormAvatar.src =
                            imageUrl;

                        profileFormAvatar.classList.remove(
                            "profile-avatar-preview-hidden"
                        );
                    }


                    if (profileFormAvatarPlaceholder) {

                        profileFormAvatarPlaceholder.style.display =
                            "none";
                    }


                    if (profileAvatarPreview) {

                        profileAvatarPreview.src =
                            imageUrl;

                        profileAvatarPreview.classList.remove(
                            "profile-avatar-preview-hidden"
                        );
                    }


                    if (profileAvatarPlaceholder) {

                        profileAvatarPlaceholder.style.display =
                            "none";
                    }

                };

                reader.readAsDataURL(file);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | منوی داخلی پروفایل
    |--------------------------------------------------------------------------
    */

    const profileMenuItems =
        document.querySelectorAll(
            "[data-profile-target]"
        );

    profileMenuItems.forEach(function (item) {

        item.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const targetId =
                    this.getAttribute(
                        "data-profile-target"
                    );

                const target =
                    document.getElementById(
                        targetId
                    );

                if (!target) {
                    return;
                }


                /*
                | Active menu
                */

                profileMenuItems.forEach(
                    function (menuItem) {

                        menuItem.classList.remove(
                            "active"
                        );

                    }
                );

                this.classList.add("active");


                /*
                | Scroll
                */

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | تشخیص Section هنگام Scroll
    |--------------------------------------------------------------------------
    */

    const profileSections =
        document.querySelectorAll(
            ".profile-section[id]"
        );

    if (
        profileSections.length &&
        profileMenuItems.length
    ) {

        const observer =
            new IntersectionObserver(
                function (entries) {

                    entries.forEach(
                        function (entry) {

                            if (!entry.isIntersecting) {
                                return;
                            }

                            const id =
                                entry.target.id;

                            profileMenuItems.forEach(
                                function (item) {

                                    item.classList.toggle(
                                        "active",
                                        item.getAttribute(
                                            "data-profile-target"
                                        ) === id
                                    );

                                }
                            );

                        }
                    );

                },
                {
                    rootMargin:
                        "-25% 0px -60% 0px",
                    threshold: 0
                }
            );


        profileSections.forEach(
            function (section) {

                observer.observe(section);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | بررسی یکسان بودن رمزها قبل از Submit
    |--------------------------------------------------------------------------
    */

    const profileForm =
        document.getElementById(
            "profileUpdateForm"
        );

    if (profileForm) {

        profileForm.addEventListener(
            "submit",
            function (event) {

                const newPassword =
                    document.getElementById(
                        "newPassword"
                    );

                const confirmPassword =
                    document.getElementById(
                        "confirmPassword"
                    );


                /*
                | اگر کاربر رمز جدید وارد کرده
                */

                if (
                    newPassword &&
                    confirmPassword &&
                    (
                        newPassword.value !== "" ||
                        confirmPassword.value !== ""
                    )
                ) {

                    if (
                        newPassword.value.length < 8
                    ) {

                        event.preventDefault();

                        alert(
                            "رمز عبور جدید باید حداقل ۸ کاراکتر باشد."
                        );

                        newPassword.focus();

                        return;
                    }


                    if (
                        newPassword.value !==
                        confirmPassword.value
                    ) {

                        event.preventDefault();

                        alert(
                            "رمز عبور جدید و تکرار آن یکسان نیست."
                        );

                        confirmPassword.focus();

                        return;
                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Loading button
                |--------------------------------------------------------------------------
                */

                const saveButton =
                    document.getElementById(
                        "profileSaveButton"
                    );

                if (saveButton) {

                    saveButton.classList.add(
                        "loading"
                    );

                    const text =
                        saveButton.querySelector(
                            "span"
                        );

                    if (text) {

                        text.textContent =
                            "در حال ذخیره...";
                    }

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | جلوگیری از Submit دوباره فرم رمز
    |--------------------------------------------------------------------------
    */

    const authForm =
        document.querySelector(
            ".profile-auth-form"
        );

    if (authForm) {

        authForm.addEventListener(
            "submit",
            function () {

                const button =
                    this.querySelector(
                        "button[type='submit']"
                    );

                if (!button) {
                    return;
                }

                button.disabled = true;

                const span =
                    button.querySelector(
                        "span"
                    );

                if (span) {

                    span.textContent =
                        "در حال بررسی...";
                }

            }
        );

    }

});

/* =========================================================
   PERSIAN ONLY INPUT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const persianInputs = document.querySelectorAll(
        'input[data-persian-only]'
    );

    persianInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            this.value = this.value.replace(
                /[^\u0600-\u06FF\s]/g,
                ""
            );

        });

    });

});


    /* =====================================================
                        REGISTER PAGE
    ===================================================== */

    const registerForm =
        document.querySelector(
            ".register-form"
        );


    const sendBtn =
        document.getElementById("sendOtp");


    const otpBox =
        document.getElementById("otpBox");


    const firstnameInput =
        document.getElementById(
            "firstname"
        );


    const lastnameInput =
        document.getElementById(
            "lastname"
        );


    const mobileInput =
        document.getElementById(
            "mobile"
        );


    const otpInput =
        document.getElementById(
            "otp"
        );


    const passwordInput =
        document.getElementById(
            "password"
        );


    const password2Input =
        document.getElementById(
            "password2"
        );


    /* =====================================================
                    FIRST NAME
                    ONLY ENGLISH
    ===================================================== */

    if (firstnameInput) {

        firstnameInput.addEventListener(
            "input",
            () => {

                firstnameInput.value =
                    firstnameInput.value.replace(
                        /[^A-Za-z]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    LAST NAME
                    ONLY ENGLISH
    ===================================================== */

    if (lastnameInput) {

        lastnameInput.addEventListener(
            "input",
            () => {

                lastnameInput.value =
                    lastnameInput.value.replace(
                        /[^A-Za-z]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    MOBILE
                    ONLY ENGLISH NUMBERS
    ===================================================== */

    if (mobileInput) {

        mobileInput.addEventListener(
            "input",
            () => {

                mobileInput.value =
                    mobileInput.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    OTP
                    ONLY ENGLISH NUMBERS
    ===================================================== */

    if (otpInput) {

        otpInput.addEventListener(
            "input",
            () => {

                otpInput.value =
                    otpInput.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    PASSWORD
                    ONLY ENGLISH NUMBERS
    ===================================================== */

    if (passwordInput) {

        passwordInput.addEventListener(
            "input",
            () => {

                passwordInput.value =
                    passwordInput.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    PASSWORD 2
                    ONLY ENGLISH NUMBERS
    ===================================================== */

    if (password2Input) {

        password2Input.addEventListener(
            "input",
            () => {

                password2Input.value =
                    password2Input.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    SEND REGISTER OTP
    ===================================================== */

    if (
        sendBtn &&
        otpBox &&
        mobileInput
    ) {

        let otpTimer = null;


        sendBtn.addEventListener(
            "click",
            () => {

                const mobile =
                    mobileInput.value.trim();


                /* -----------------------------------------
                        بررسی شماره موبایل
                ----------------------------------------- */

                if (
                    !/^09[0-9]{9}$/.test(
                        mobile
                    )
                ) {

                    alert(
                        "شماره موبایل باید ۱۱ رقم انگلیسی و با 09 شروع شود."
                    );

                    mobileInput.focus();

                    return;

                }


                /* -----------------------------------------
                        نمایش OTP
                ----------------------------------------- */

                otpBox.classList.add(
                    "show"
                );


                if (otpInput) {

                    otpInput.focus();

                }


                /* -----------------------------------------
                        پاک کردن تایمر قبلی
                ----------------------------------------- */

                clearInterval(
                    otpTimer
                );


                sendBtn.disabled =
                    true;


                let time = 120;


                sendBtn.textContent =
                    `${time} ثانیه`;


                otpTimer = setInterval(
                    () => {

                        time--;


                        sendBtn.textContent =
                            `${time} ثانیه`;


                        if (time <= 0) {

                            clearInterval(
                                otpTimer
                            );


                            sendBtn.disabled =
                                false;


                            sendBtn.textContent =
                                "ارسال مجدد";

                        }

                    },
                    1000
                );

            }
        );

    }


    /* =====================================================
                    REGISTER FORM VALIDATION
    ===================================================== */

    if (registerForm) {

        registerForm.addEventListener(
            "submit",
            event => {

                const firstname =
                    firstnameInput
                        ? firstnameInput.value.trim()
                        : "";


                const lastname =
                    lastnameInput
                        ? lastnameInput.value.trim()
                        : "";


                const mobile =
                    mobileInput
                        ? mobileInput.value.trim()
                        : "";


                const password =
                    passwordInput
                        ? passwordInput.value
                        : "";


                const password2 =
                    password2Input
                        ? password2Input.value
                        : "";


                const otp =
                    otpInput
                        ? otpInput.value.trim()
                        : "";


                const termsCheckbox =
                    registerForm.querySelector(
                        '.terms input[type="checkbox"]'
                    );


                /* -----------------------------------------
                        FIRST NAME
                ----------------------------------------- */

                if (
                    !/^[A-Za-z]+$/.test(
                        firstname
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "نام فقط باید شامل حروف انگلیسی باشد."
                    );

                    if (firstnameInput) {
                        firstnameInput.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        LAST NAME
                ----------------------------------------- */

                if (
                    !/^[A-Za-z]+$/.test(
                        lastname
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "نام خانوادگی فقط باید شامل حروف انگلیسی باشد."
                    );

                    if (lastnameInput) {
                        lastnameInput.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        MOBILE
                ----------------------------------------- */

                if (
                    !/^09[0-9]{9}$/.test(
                        mobile
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "شماره موبایل باید ۱۱ رقم انگلیسی و با 09 شروع شود."
                    );

                    if (mobileInput) {
                        mobileInput.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        PASSWORD
                ----------------------------------------- */

                if (
                    !/^[0-9]+$/.test(
                        password
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور فقط باید شامل اعداد انگلیسی باشد."
                    );

                    if (passwordInput) {
                        passwordInput.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        PASSWORD LENGTH
                ----------------------------------------- */

                if (
                    password.length < 6
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور باید حداقل ۶ رقم باشد."
                    );

                    if (passwordInput) {
                        passwordInput.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        PASSWORD CONFIRMATION
                ----------------------------------------- */

                if (
                    password !== password2
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور و تکرار آن یکسان نیستند."
                    );

                    if (password2Input) {
                        password2Input.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        TERMS
                ----------------------------------------- */

                if (
                    termsCheckbox &&
                    !termsCheckbox.checked
                ) {

                    event.preventDefault();

                    alert(
                        "برای ثبت نام باید قوانین سایت را بپذیرید."
                    );

                    termsCheckbox.focus();

                    return;

                }


                /* -----------------------------------------
                        OTP
                ----------------------------------------- */

                if (
                    otpBox &&
                    otpBox.classList.contains(
                        "show"
                    )
                ) {

                    if (
                        !/^[0-9]{6}$/.test(
                            otp
                        )
                    ) {

                        event.preventDefault();

                        alert(
                            "کد تایید باید ۶ رقم انگلیسی باشد."
                        );

                        if (otpInput) {
                            otpInput.focus();
                        }

                        return;

                    }

                }

            }
        );

    }


    /* =====================================================
                         LOGIN PAGE
                    MOBILE + PASSWORD
    ===================================================== */

    const loginForm =
        document.querySelector(
            ".login-form"
        );


    const loginMobile =
        document.getElementById(
            "loginMobile"
        );


    const loginPassword =
        document.getElementById(
            "loginPassword"
        );


    /* =====================================================
                    LOGIN MOBILE
                    ONLY ENGLISH NUMBERS
    ===================================================== */

    if (loginMobile) {

        loginMobile.addEventListener(
            "input",
            () => {

                loginMobile.value =
                    loginMobile.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    LOGIN PASSWORD
                    ONLY ENGLISH NUMBERS
    ===================================================== */

    if (loginPassword) {

        loginPassword.addEventListener(
            "input",
            () => {

                loginPassword.value =
                    loginPassword.value.replace(
                        /[^0-9]/g,
                        ""
                    );

            }
        );

    }


    /* =====================================================
                    LOGIN VALIDATION
    ===================================================== */

    if (loginForm) {

        loginForm.addEventListener(
            "submit",
            event => {

                const mobile =
                    loginMobile
                        ? loginMobile.value.trim()
                        : "";


                const password =
                    loginPassword
                        ? loginPassword.value
                        : "";


                /* -----------------------------------------
                        MOBILE
                ----------------------------------------- */

                if (
                    !/^09[0-9]{9}$/.test(
                        mobile
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "شماره موبایل باید ۱۱ رقم انگلیسی و با 09 شروع شود."
                    );

                    if (loginMobile) {
                        loginMobile.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        PASSWORD
                ----------------------------------------- */

                if (
                    !/^[0-9]+$/.test(
                        password
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور فقط باید شامل اعداد انگلیسی باشد."
                    );

                    if (loginPassword) {
                        loginPassword.focus();
                    }

                    return;

                }


                /* -----------------------------------------
                        PASSWORD LENGTH
                ----------------------------------------- */

                if (
                    password.length < 6
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور باید حداقل ۶ رقم باشد."
                    );

                    if (loginPassword) {
                        loginPassword.focus();
                    }

                    return;

                }

            }
        );

    }


    /* =====================================================
                        BANNER SLIDER
    ===================================================== */

    const slider =
        document.querySelector(
            ".banner-slider"
        );


    if (slider) {

        const slides =
            slider.querySelectorAll(
                ".slide"
            );


        const dotsContainer =
            slider.querySelector(
                ".dots"
            );


        const nextBtn =
            slider.querySelector(
                ".next"
            );


        const prevBtn =
            slider.querySelector(
                ".prev"
            );


        if (slides.length) {

            let current = 0;

            let interval = null;


            /* ---------------------------------------------
                        ساخت نقاط
            --------------------------------------------- */

            if (dotsContainer) {

                /*
                 * حذف نقاط قبلی در صورت وجود
                 */

                dotsContainer.innerHTML = "";


                slides.forEach(
                    (slide, index) => {

                        const dot =
                            document.createElement(
                                "span"
                            );


                        dot.classList.add(
                            "dot"
                        );


                        if (index === 0) {

                            dot.classList.add(
                                "active"
                            );

                        }


                        dot.addEventListener(
                            "click",
                            () => {

                                current =
                                    index;


                                showSlide(
                                    current
                                );


                                restartAuto();

                            }
                        );


                        dotsContainer.appendChild(
                            dot
                        );

                    }
                );

            }


            const dots =
                dotsContainer
                    ? dotsContainer.querySelectorAll(
                        ".dot"
                    )
                    : [];


            /* ---------------------------------------------
                        نمایش اسلاید
            --------------------------------------------- */

            function showSlide(index) {

                slides.forEach(
                    slide => {

                        slide.classList.remove(
                            "active"
                        );

                    }
                );


                dots.forEach(
                    dot => {

                        dot.classList.remove(
                            "active"
                        );

                    }
                );


                slides[index].classList.add(
                    "active"
                );


                if (dots[index]) {

                    dots[index].classList.add(
                        "active"
                    );

                }

            }


            /* ---------------------------------------------
                        اسلاید بعدی
            --------------------------------------------- */

            function nextSlide() {

                current++;


                if (
                    current >=
                    slides.length
                ) {

                    current = 0;

                }


                showSlide(
                    current
                );

            }


            /* ---------------------------------------------
                        اسلاید قبلی
            --------------------------------------------- */

            function prevSlide() {

                current--;


                if (current < 0) {

                    current =
                        slides.length - 1;

                }


                showSlide(
                    current
                );

            }


            /* ---------------------------------------------
                        دکمه بعدی
            --------------------------------------------- */

            if (nextBtn) {

                nextBtn.addEventListener(
                    "click",
                    () => {

                        nextSlide();

                        restartAuto();

                    }
                );

            }


            /* ---------------------------------------------
                        دکمه قبلی
            --------------------------------------------- */

            if (prevBtn) {

                prevBtn.addEventListener(
                    "click",
                    () => {

                        prevSlide();

                        restartAuto();

                    }
                );

            }


            /* ---------------------------------------------
                        شروع خودکار
            --------------------------------------------- */

            function startAuto() {

                clearInterval(
                    interval
                );


                interval =
                    setInterval(
                        () => {

                            nextSlide();

                        },
                        4000
                    );

            }


            /* ---------------------------------------------
                        شروع مجدد
            --------------------------------------------- */

            function restartAuto() {

                clearInterval(
                    interval
                );


                startAuto();

            }


            /* ---------------------------------------------
                        توقف هنگام Hover
            --------------------------------------------- */

            slider.addEventListener(
                "mouseenter",
                () => {

                    clearInterval(
                        interval
                    );

                }
            );


            slider.addEventListener(
                "mouseleave",
                () => {

                    startAuto();

                }
            );


            /* ---------------------------------------------
                        شروع اولیه
            --------------------------------------------- */

            showSlide(0);

            startAuto();

        }

    }

});

/* =====================================================
                    THEME SYSTEM
===================================================== */

const themeToggle =
    document.getElementById("themeToggle");

const body =
    document.body;

const isLoggedIn =
    body.dataset.loggedIn === "true";

const userId =
    body.dataset.userId || "";


/* -----------------------------------------------------
                کلید ذخیره Theme
----------------------------------------------------- */

function getThemeKey() {

    if (!userId) {
        return null;
    }

    return `devmarket_theme_user_${userId}`;

}


/* -----------------------------------------------------
                اعمال Theme
----------------------------------------------------- */

function applyTheme(theme) {

    if (theme === "light") {

        body.classList.add(
            "light-theme"
        );

    } else {

        body.classList.remove(
            "light-theme"
        );

    }

}


/* -----------------------------------------------------
                Theme اولیه
----------------------------------------------------- */

if (isLoggedIn && userId) {

    const themeKey =
        getThemeKey();

    const savedTheme =
        localStorage.getItem(themeKey);


    /*
     * اگر کاربر قبلاً انتخابی داشته:
     * همان انتخاب استفاده شود.
     *
     * در غیر این صورت Dark.
     */

    if (savedTheme === "light") {

        applyTheme("light");

    } else {

        applyTheme("dark");

    }

} else {

    /*
     * کاربر مهمان همیشه Dark.
     *
     * هیچ Themeای برای مهمان
     * ذخیره یا بازیابی نمی‌شود.
     */

    applyTheme("dark");

}


/* -----------------------------------------------------
                    Theme Toggle
----------------------------------------------------- */

if (themeToggle) {

    themeToggle.addEventListener(
        "click",
        () => {

            /*
             * مهمان اجازه ذخیره Theme ندارد.
             */

            if (!isLoggedIn || !userId) {

                return;

            }


            const isLight =
                body.classList.contains(
                    "light-theme"
                );


            if (isLight) {

                /*
                 * Light → Dark
                 */

                applyTheme("dark");


                localStorage.setItem(
                    getThemeKey(),
                    "dark"
                );

            } else {

                /*
                 * Dark → Light
                 */

                applyTheme("light");


                localStorage.setItem(
                    getThemeKey(),
                    "light"
                );

            }

        }
    );

}

/* =====================================================
                    PAGE LOADER
===================================================== */

(function () {

    const loader =
        document.getElementById("pageLoader");

    if (!loader) {
        return;
    }


    /* =================================================
                        HIDE LOADER
    ================================================= */

    function hideLoader() {

        loader.classList.add("is-hidden");

        setTimeout(() => {

            loader.style.display = "none";

        }, 400);

    }


    /* =================================================
                    INITIAL PAGE LOAD
    ================================================= */

    window.addEventListener("load", function () {

        hideLoader();

    });


    /* =================================================
                    PAGE NAVIGATION
    ================================================= */

    document.addEventListener("click", function (event) {

        const link =
            event.target.closest("a");

        if (!link) {
            return;
        }


        const href =
            link.getAttribute("href");

        if (!href) {
            return;
        }


        /* لینک # */

        if (href === "#") {
            return;
        }


        /* لینک JavaScript */

        if (
            href.toLowerCase().startsWith(
                "javascript:"
            )
        ) {
            return;
        }


        /* تب جدید */

        if (
            link.target === "_blank" ||
            event.ctrlKey ||
            event.shiftKey ||
            event.metaKey ||
            event.altKey
        ) {
            return;
        }


        /* دانلود */

        if (link.hasAttribute("download")) {
            return;
        }


        let targetURL;

        try {

            targetURL =
                new URL(
                    href,
                    window.location.href
                );

        } catch {

            return;

        }


        /* لینک خارجی */

        if (
            targetURL.origin !==
            window.location.origin
        ) {
            return;
        }


        /* لینک همان صفحه */

        const currentURL =
            window.location.href
                .split("#")[0];

        const nextURL =
            targetURL.href
                .split("#")[0];


        if (currentURL === nextURL) {
            return;
        }


        /* =================================================
                نمایش لودر قبل از navigation
        ================================================= */

        event.preventDefault();


        loader.style.display = "flex";


        requestAnimationFrame(() => {

            loader.classList.remove(
                "is-hidden"
            );

        });


        /*
            کمی فرصت می‌دهیم که
            لودر واقعاً روی صفحه paint شود.
        */

        requestAnimationFrame(() => {

            requestAnimationFrame(() => {

                window.location.href =
                    targetURL.href;

            });

        });

    });

})();

/* =====================================================
                    DEVMARKET SCRIPT
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    /* =================================================
                        HELPERS
    ================================================= */

    const $ = (selector, parent = document) => {
        return parent.querySelector(selector);
    };

    const $$ = (selector, parent = document) => {
        return Array.from(parent.querySelectorAll(selector));
    };


    /* =================================================
                    ACTIVE NAVIGATION
    ================================================= */

    const currentPage =
        window.location.pathname
            .split("/")
            .pop() || "index.php";

    const navLinks =
        $$(".header-nav .menu a");

    navLinks.forEach(function (link) {

        const href =
            link.getAttribute("href");

        if (!href || href === "#") {
            return;
        }

        const linkPage =
            href.split("/").pop();

        if (linkPage === currentPage) {
            link.classList.add("active");
        }

    });


    /* =================================================
                    SECOND NAVIGATION
                    SHOW / HIDE ON SCROLL
    ================================================= */

    const siteHeader =
        $(".site-header");

    const secondNav =
        $(".header-nav-container");

    if (siteHeader && secondNav) {

        let hideNavTimer = null;
        let lastScrollY = window.scrollY;

        function showSecondNav() {

            siteHeader.classList.remove("nav-hidden");

        }


        function hideSecondNav() {

            /*
                در بالای صفحه هیچ‌وقت مخفی نشود
            */

            if (window.scrollY <= 5) {
                showSecondNav();
                return;
            }

            siteHeader.classList.add("nav-hidden");

        }


        function handlePageScroll() {

            const currentScrollY =
                window.scrollY;


            /*
                بالای صفحه
            */

            if (currentScrollY <= 5) {

                showSecondNav();

                if (hideNavTimer) {
                    clearTimeout(hideNavTimer);
                    hideNavTimer = null;
                }

                lastScrollY = currentScrollY;

                return;
            }


            /*
                هر بار که اسکرول شروع شد
                نوار فوراً نمایش داده شود
            */

            if (
                Math.abs(
                    currentScrollY - lastScrollY
                ) > 0
            ) {

                showSecondNav();

            }


            /*
                بعد از 700ms بدون اسکرول
                نوار مخفی شود
            */

            if (hideNavTimer) {
                clearTimeout(hideNavTimer);
            }

            hideNavTimer = setTimeout(
                hideSecondNav,
                700
            );


            lastScrollY = currentScrollY;

        }


        window.addEventListener(
            "scroll",
            handlePageScroll,
            { passive: true }
        );


        /*
            وضعیت اولیه
        */

        if (window.scrollY <= 5) {
            showSecondNav();
        }

    }


    /* =================================================
                        MOBILE MENU
    ================================================= */

    const mobileMenu =
        $(".mobile-menu");

    const headerNav =
        $(".header-nav");

    if (mobileMenu && headerNav) {

        mobileMenu.addEventListener(
            "click",
            function () {

                mobileMenu.classList.toggle(
                    "active"
                );

                headerNav.classList.toggle(
                    "mobile-open"
                );

            }
        );


        /*
            بستن منو بعد از انتخاب لینک
        */

        $$(".header-nav a").forEach(
            function (link) {

                link.addEventListener(
                    "click",
                    function () {

                        mobileMenu.classList.remove(
                            "active"
                        );

                        headerNav.classList.remove(
                            "mobile-open"
                        );

                    }
                );

            }
        );

    }


    /* =================================================
                        THEME SYSTEM
    ================================================= */

    const themeToggle =
        $("#themeToggle");

    const body =
        document.body;

    const isLoggedIn =
        body.dataset.loggedIn === "true";

    const userId =
        body.dataset.userId || "";


    function getThemeKey() {

        if (!userId) {
            return null;
        }

        return `devmarket_theme_user_${userId}`;

    }


    function applyTheme(theme) {

        if (theme === "light") {

            body.classList.add(
                "light-theme"
            );

        } else {

            body.classList.remove(
                "light-theme"
            );

        }

    }


    /*
        کاربر وارد شده:
        تنظیمات خودش را بخوان
    */

    if (isLoggedIn && userId) {

        const themeKey =
            getThemeKey();

        const savedTheme =
            localStorage.getItem(
                themeKey
            );


        if (savedTheme === "light") {

            applyTheme("light");

        } else {

            applyTheme("dark");

        }

    } else {

        /*
            مهم:
            مهمان همیشه Dark
        */

        applyTheme("dark");

    }


    /*
        تغییر تم
    */

    if (themeToggle) {

        themeToggle.addEventListener(
            "click",
            function () {

                /*
                    مهمان اجازه ذخیره تم ندارد
                */

                if (!isLoggedIn || !userId) {
                    return;
                }


                const isLight =
                    body.classList.contains(
                        "light-theme"
                    );


                if (isLight) {

                    applyTheme("dark");

                    localStorage.setItem(
                        getThemeKey(),
                        "dark"
                    );

                } else {

                    applyTheme("light");

                    localStorage.setItem(
                        getThemeKey(),
                        "light"
                    );

                }

            }
        );

    }


    /* =================================================
                        SEARCH
    ================================================= */

    const searchInput =
        $(".search-box input");

    const searchButton =
        $(".search-icon");


    if (searchInput && searchButton) {

        function performSearch() {

            const value =
                searchInput.value.trim();


            if (!value) {
                return;
            }


            /*
                فعلاً فقط آماده‌سازی شده.
                بعداً می‌توانیم به search.php
                متصلش کنیم.
            */

            console.log(
                "Search:",
                value
            );

        }


        searchButton.addEventListener(
            "click",
            performSearch
        );


        searchInput.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Enter") {

                    event.preventDefault();

                    performSearch();

                }

            }
        );

    }


    /* =================================================
                    REGISTER FORM
    ================================================= */

    const registerForm =
        $("#registerForm");


    if (registerForm) {

        const firstname =
            $("#firstname");

        const lastname =
            $("#lastname");

        const mobile =
            $("#mobile");

        const password =
            $("#password");

        const password2 =
            $("#password2");

        const otp =
            $("#otp");

        const terms =
            $("#terms");


        /*
            فقط حروف انگلیسی
        */

        if (firstname) {

            firstname.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^A-Za-z]/g,
                            ""
                        );

                }
            );

        }


        if (lastname) {

            lastname.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^A-Za-z]/g,
                            ""
                        );

                }
            );

        }


        /*
            موبایل فقط عدد انگلیسی
        */

        if (mobile) {

            mobile.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );

                }
            );

        }


        /*
            رمز عبور فقط عدد انگلیسی
        */

        if (password) {

            password.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );

                }
            );

        }


        if (password2) {

            password2.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );

                }
            );

        }


        /*
            OTP فقط عدد
        */

        if (otp) {

            otp.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );

                }
            );

        }


        /* =============================================
                        OTP BUTTON
        ============================================= */

        const sendOtpButton =
            $("#sendOtp");


        const otpBox =
            $("#otpBox");


        const otpTimer =
            $("#otpTimer");


        if (
            sendOtpButton &&
            otpBox
        ) {

            let otpInterval = null;


            sendOtpButton.addEventListener(
                "click",
                function () {

                    if (!mobile) {
                        return;
                    }


                    const mobileValue =
                        mobile.value.trim();


                    if (
                        !/^09[0-9]{9}$/.test(
                            mobileValue
                        )
                    ) {

                        alert(
                            "شماره موبایل را صحیح وارد کنید."
                        );

                        mobile.focus();

                        return;

                    }


                    /*
                        نمایش بخش OTP
                    */

                    otpBox.style.display =
                        "block";


                    if (otp) {
                        otp.focus();
                    }


                    /*
                        تایمر آزمایشی
                    */

                    let remaining = 120;


                    if (otpTimer) {

                        otpTimer.textContent =
                            "02:00";

                    }


                    if (otpInterval) {

                        clearInterval(
                            otpInterval
                        );

                    }


                    otpInterval =
                        setInterval(
                            function () {

                                remaining--;


                                const minutes =
                                    Math.floor(
                                        remaining / 60
                                    );


                                const seconds =
                                    remaining % 60;


                                if (otpTimer) {

                                    otpTimer.textContent =
                                        String(minutes)
                                            .padStart(2, "0")
                                        +
                                        ":"
                                        +
                                        String(seconds)
                                            .padStart(2, "0");

                                }


                                if (remaining <= 0) {

                                    clearInterval(
                                        otpInterval
                                    );

                                    otpInterval = null;

                                }

                            },
                            1000
                        );

                }
            );

        }


        /* =============================================
                    REGISTER VALIDATION
        ============================================= */

        registerForm.addEventListener(
            "submit",
            function (event) {

                const firstnameValue =
                    firstname
                        ? firstname.value.trim()
                        : "";


                const lastnameValue =
                    lastname
                        ? lastname.value.trim()
                        : "";


                const mobileValue =
                    mobile
                        ? mobile.value.trim()
                        : "";


                const passwordValue =
                    password
                        ? password.value
                        : "";


                const password2Value =
                    password2
                        ? password2.value
                        : "";


                const otpValue =
                    otp
                        ? otp.value.trim()
                        : "";


                if (
                    !/^[A-Za-z]+$/.test(
                        firstnameValue
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "نام فقط باید شامل حروف انگلیسی باشد."
                    );

                    if (firstname) {
                        firstname.focus();
                    }

                    return;

                }


                if (
                    !/^[A-Za-z]+$/.test(
                        lastnameValue
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "نام خانوادگی فقط باید شامل حروف انگلیسی باشد."
                    );

                    if (lastname) {
                        lastname.focus();
                    }

                    return;

                }


                if (
                    !/^09[0-9]{9}$/.test(
                        mobileValue
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "شماره موبایل صحیح نیست."
                    );

                    if (mobile) {
                        mobile.focus();
                    }

                    return;

                }


                if (
                    !/^[0-9]+$/.test(
                        passwordValue
                    ) ||
                    passwordValue.length < 6
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور باید حداقل ۶ رقم و فقط شامل اعداد انگلیسی باشد."
                    );

                    if (password) {
                        password.focus();
                    }

                    return;

                }


                if (
                    passwordValue !==
                    password2Value
                ) {

                    event.preventDefault();

                    alert(
                        "تکرار رمز عبور صحیح نیست."
                    );

                    if (password2) {
                        password2.focus();
                    }

                    return;

                }


                /*
                    OTP در صورت وجود
                */

                if (
                    otp &&
                    otpBox &&
                    otpBox.style.display !== "none" &&
                    otpValue !== "" &&
                    !/^[0-9]{6}$/.test(
                        otpValue
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "کد تأیید باید ۶ رقم باشد."
                    );

                    otp.focus();

                    return;

                }


                if (
                    terms &&
                    !terms.checked
                ) {

                    event.preventDefault();

                    alert(
                        "برای ثبت‌نام باید قوانین فروشگاه را بپذیرید."
                    );

                    terms.focus();

                    return;

                }

            }
        );

    }


    /* =================================================
                        LOGIN FORM
    ================================================= */

    const loginForm =
        $("#loginForm");


    if (loginForm) {

        const loginMobile =
            $("#loginMobile");

        const loginPassword =
            $("#loginPassword");


        /*
            فقط اعداد انگلیسی
        */

        if (loginMobile) {

            loginMobile.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );

                }
            );

        }


        if (loginPassword) {

            loginPassword.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );

                }
            );

        }


        loginForm.addEventListener(
            "submit",
            function (event) {

                const mobileValue =
                    loginMobile
                        ? loginMobile.value.trim()
                        : "";


                const passwordValue =
                    loginPassword
                        ? loginPassword.value
                        : "";


                if (
                    !/^09[0-9]{9}$/.test(
                        mobileValue
                    )
                ) {

                    event.preventDefault();

                    alert(
                        "شماره موبایل صحیح نیست."
                    );

                    if (loginMobile) {
                        loginMobile.focus();
                    }

                    return;

                }


                if (
                    !/^[0-9]+$/.test(
                        passwordValue
                    ) ||
                    passwordValue.length < 6
                ) {

                    event.preventDefault();

                    alert(
                        "رمز عبور باید حداقل ۶ رقم باشد."
                    );

                    if (loginPassword) {
                        loginPassword.focus();
                    }

                    return;

                }

            }
        );

    }


    /* =================================================
                    BANNER SLIDER
    ================================================= */

    const bannerSlider =
        $(".banner-slider");


    if (bannerSlider) {

        const slides =
            $$(".banner-slide", bannerSlider);

        const prev =
            $(".prev", bannerSlider);

        const next =
            $(".next", bannerSlider);

        const dots =
            $$(".dots button, .dots span", bannerSlider);


        if (slides.length > 0) {

            let currentSlide = 0;
            let autoSlide = null;


            function showSlide(index) {

                if (index < 0) {

                    index =
                        slides.length - 1;

                }


                if (
                    index >=
                    slides.length
                ) {

                    index = 0;

                }


                slides.forEach(
                    function (slide, i) {

                        slide.classList.toggle(
                            "active",
                            i === index
                        );

                    }
                );


                dots.forEach(
                    function (dot, i) {

                        dot.classList.toggle(
                            "active",
                            i === index
                        );

                    }
                );


                currentSlide = index;

            }


            function nextSlide() {

                showSlide(
                    currentSlide + 1
                );

            }


            function previousSlide() {

                showSlide(
                    currentSlide - 1
                );

            }


            if (next) {

                next.addEventListener(
                    "click",
                    nextSlide
                );

            }


            if (prev) {

                prev.addEventListener(
                    "click",
                    previousSlide
                );

            }


            dots.forEach(
                function (dot, index) {

                    dot.addEventListener(
                        "click",
                        function () {

                            showSlide(index);

                        }
                    );

                }
            );


            function startAutoSlide() {

                if (autoSlide) {
                    clearInterval(autoSlide);
                }

                autoSlide =
                    setInterval(
                        nextSlide,
                        5000
                    );

            }


            showSlide(0);

            startAutoSlide();


            /*
                وقتی کاربر روی اسلایدر کلیک کرد
                تایمر دوباره از اول شروع شود.
            */

            bannerSlider.addEventListener(
                "mouseenter",
                function () {

                    if (autoSlide) {
                        clearInterval(
                            autoSlide
                        );
                    }

                }
            );


            bannerSlider.addEventListener(
                "mouseleave",
                function () {

                    startAutoSlide();

                }
            );

        }

    }

/* =================================================
                SPECIAL DISCOUNTS SLIDER
    ================================================= */

    const discountProducts =
        $("#discountProducts");


    const discountPrev =
        $(".discount-arrow-prev");


    const discountNext =
        $(".discount-arrow-next");


    if (
        discountProducts &&
        discountPrev &&
        discountNext
    ) {

        /*
            اندازه حرکت:
            دقیقاً به اندازه یک محصول
        */

        function getDiscountScrollAmount() {

            const product =
                discountProducts.querySelector(
                    ".discount-product"
                );


            if (!product) {
                return 200;
            }


            const productStyle =
                window.getComputedStyle(product);


            const marginRight =
                parseFloat(
                    productStyle.marginRight
                ) || 0;


            const marginLeft =
                parseFloat(
                    productStyle.marginLeft
                ) || 0;


            return (
                product.offsetWidth +
                marginRight +
                marginLeft
            );

        }


        /*
            تشخیص اینکه اسلایدر RTL است یا نه
        */

        function isDiscountRTL() {

            return (
                window.getComputedStyle(
                    discountProducts
                ).direction === "rtl"
            );

        }


        /*
            حد نهایی اسکرول
        */

        function getDiscountMaxScroll() {

            return Math.max(
                0,
                discountProducts.scrollWidth -
                discountProducts.clientWidth
            );

        }


        /*
            وضعیت دکمه‌ها
        */

        function updateDiscountArrows() {

            const maxScroll =
                getDiscountMaxScroll();


            /*
                اگر اصلاً اسکرول وجود نداشته باشد
            */

            if (maxScroll <= 5) {

                discountPrev.style.opacity =
                    "0.45";

                discountPrev.disabled =
                    true;


                discountNext.style.opacity =
                    "0.45";

                discountNext.disabled =
                    true;


                return;

            }


            /*
                =====================================
                RTL
                =====================================
            */

            if (isDiscountRTL()) {

                const currentScroll =
                    discountProducts.scrollLeft;


                /*
                    در RTL معمولاً:

                    شروع = 0
                    انتهای محتوا = مقدار منفی
                */


                const atRightStart =
                    currentScroll >= -5;


                const atLeftEnd =
                    Math.abs(
                        currentScroll
                    ) >=
                    maxScroll - 5;


                /*
                    دکمه سمت راست:
                    PREVIOUS

                    وقتی در ابتدای سمت راست هستیم
                    غیرفعال است.
                */

                if (atRightStart) {

                    discountPrev.style.opacity =
                        "0.45";

                    discountPrev.disabled =
                        true;

                } else {

                    discountPrev.style.opacity =
                        "1";

                    discountPrev.disabled =
                        false;

                }


                /*
                    دکمه سمت چپ:
                    NEXT

                    وقتی به انتهای سمت چپ رسیده‌ایم
                    غیرفعال است.
                */

                if (atLeftEnd) {

                    discountNext.style.opacity =
                        "0.45";

                    discountNext.disabled =
                        true;

                } else {

                    discountNext.style.opacity =
                        "1";

                    discountNext.disabled =
                        false;

                }


                return;

            }


            /*
                =====================================
                LTR
                =====================================
            */

            const currentScroll =
                discountProducts.scrollLeft;


            const atLeftStart =
                currentScroll <= 5;


            const atRightEnd =
                currentScroll >=
                maxScroll - 5;


            if (atLeftStart) {

                discountPrev.style.opacity =
                    "0.45";

                discountPrev.disabled =
                    true;

            } else {

                discountPrev.style.opacity =
                    "1";

                discountPrev.disabled =
                    false;

            }


            if (atRightEnd) {

                discountNext.style.opacity =
                    "0.45";

                discountNext.disabled =
                    true;

            } else {

                discountNext.style.opacity =
                    "1";

                discountNext.disabled =
                    false;

            }

        }


        /*
            =====================================
            حرکت اسلایدر
            =====================================
        */

        function moveDiscountSlider(direction) {

            const amount =
                getDiscountScrollAmount();


            if (!amount) {
                return;
            }


            /*
                RTL
            */

            if (isDiscountRTL()) {

                const current =
                    discountProducts.scrollLeft;


                /*
                    NEXT:
                    حرکت از راست به چپ

                    در RTL مقدار scrollLeft
                    منفی‌تر می‌شود.
                */

                if (direction === "next") {

                    const target =
                        Math.max(
                            current - amount,
                            -getDiscountMaxScroll()
                        );


                    discountProducts.scrollTo({

                        left:
                            target,

                        behavior:
                            "smooth"

                    });

                }


                /*
                    PREVIOUS:
                    حرکت از چپ به راست
                */

                else {

                    const target =
                        Math.min(
                            current + amount,
                            0
                        );


                    discountProducts.scrollTo({

                        left:
                            target,

                        behavior:
                            "smooth"

                    });

                }


                return;

            }


            /*
                =====================================
                LTR
                =====================================
            */

            const current =
                discountProducts.scrollLeft;


            if (direction === "next") {

                const target =
                    Math.min(
                        current + amount,
                        getDiscountMaxScroll()
                    );


                discountProducts.scrollTo({

                    left:
                        target,

                    behavior:
                        "smooth"

                });

            } else {

                const target =
                    Math.max(
                        current - amount,
                        0
                    );


                discountProducts.scrollTo({

                    left:
                        target,

                    behavior:
                        "smooth"

                });

            }

        }


        /*
            =====================================
            NEXT
            =====================================
        */

        discountNext.addEventListener(
            "click",
            function () {

                moveDiscountSlider(
                    "next"
                );

            }
        );


        /*
            =====================================
            PREVIOUS
            =====================================
        */

        discountPrev.addEventListener(
            "click",
            function () {

                moveDiscountSlider(
                    "prev"
                );

            }
        );


        /*
            =====================================
            وقتی کاربر خودش اسکرول می‌کند
            =====================================
        */

        discountProducts.addEventListener(
            "scroll",
            updateDiscountArrows,
            {
                passive: true
            }
        );


        /*
            =====================================
            تغییر اندازه صفحه
            =====================================
        */

        window.addEventListener(
            "resize",
            updateDiscountArrows
        );


        /*
            =====================================
            وضعیت اولیه
            =====================================
        */

        updateDiscountArrows();

    }


    /* =================================================
                SPECIAL DISCOUNT COUNTDOWN
    ================================================= */

    const discountCountdown =
        $("#discountCountdown");


    if (discountCountdown) {

        const daysElement =
            $("#discountDays");


        const hoursElement =
            $("#discountHours");


        const minutesElement =
            $("#discountMinutes");


        const secondsElement =
            $("#discountSeconds");


        const endValue =
            discountCountdown.dataset.end;


        const endDate =
            new Date(endValue).getTime();


        /*
            اگر تاریخ اشتباه باشد
        */

        if (
            Number.isNaN(endDate)
        ) {

            console.warn(
                "Discount countdown date is invalid."
            );

        } else {

            let discountTimer = null;


            function pad(number) {

                return String(number)
                    .padStart(2, "0");

            }


            function updateDiscountCountdown() {

                const now =
                    Date.now();


                const distance =
                    endDate - now;


                /*
                    تمام شدن تخفیف
                */

                if (distance <= 0) {

                    if (daysElement) {

                        daysElement.textContent =
                            "00";

                    }


                    if (hoursElement) {

                        hoursElement.textContent =
                            "00";

                    }


                    if (minutesElement) {

                        minutesElement.textContent =
                            "00";

                    }


                    if (secondsElement) {

                        secondsElement.textContent =
                            "00";

                    }


                    discountCountdown.classList.add(
                        "discount-expired"
                    );


                    if (discountTimer) {

                        clearInterval(
                            discountTimer
                        );

                        discountTimer = null;

                    }


                    return;

                }


                /*
                    محاسبه زمان
                */

                const totalSeconds =
                    Math.floor(
                        distance / 1000
                    );


                const days =
                    Math.floor(
                        totalSeconds /
                        86400
                    );


                const hours =
                    Math.floor(
                        (totalSeconds %
                            86400) /
                        3600
                    );


                const minutes =
                    Math.floor(
                        (totalSeconds %
                            3600) /
                        60
                    );


                const seconds =
                    totalSeconds %
                    60;


                if (daysElement) {

                    daysElement.textContent =
                        pad(days);

                }


                if (hoursElement) {

                    hoursElement.textContent =
                        pad(hours);

                }


                if (minutesElement) {

                    minutesElement.textContent =
                        pad(minutes);

                }


                if (secondsElement) {

                    secondsElement.textContent =
                        pad(seconds);

                }

            }


            /*
                اولین اجرا بدون تأخیر
            */

            updateDiscountCountdown();


            /*
                سپس هر یک ثانیه
            */

            discountTimer =
                setInterval(
                    updateDiscountCountdown,
                    1000
                );

        }

    }


    /* =================================================
                    PAGE LOADER
    ================================================= */

    const pageLoader =
        $("#pageLoader");


    if (pageLoader) {

        let loaderHidden = false;


        function hidePageLoader() {

            if (loaderHidden) {
                return;
            }


            loaderHidden = true;


            pageLoader.classList.add(
                "is-hidden"
            );


            setTimeout(
                function () {

                    pageLoader.style.display =
                        "none";

                },
                400
            );

        }


        /*
            وقتی صفحه کاملاً Load شد
        */

        window.addEventListener(
            "load",
            function () {

                hidePageLoader();

            }
        );


        /*
            نمایش Loader هنگام رفتن
            به صفحات داخلی
        */

        document.addEventListener(
            "click",
            function (event) {

                const link =
                    event.target.closest("a");


                if (!link) {
                    return;
                }


                const href =
                    link.getAttribute("href");


                if (!href) {
                    return;
                }


                /*
                    لینک خالی
                */

                if (href === "#") {
                    return;
                }


                /*
                    javascript:
                */

                if (
                    href
                        .toLowerCase()
                        .startsWith(
                            "javascript:"
                        )
                ) {

                    return;

                }


                /*
                    تب جدید
                */

                if (
                    link.target === "_blank" ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.metaKey ||
                    event.altKey
                ) {

                    return;

                }


                /*
                    دانلود
                */

                if (
                    link.hasAttribute(
                        "download"
                    )
                ) {

                    return;

                }


                let targetURL;


                try {

                    targetURL =
                        new URL(
                            href,
                            window.location.href
                        );

                } catch (error) {

                    return;

                }


                /*
                    لینک خارجی
                */

                if (
                    targetURL.origin !==
                    window.location.origin
                ) {

                    return;

                }


                /*
                    فقط تغییر صفحه
                    نه تغییر #anchor
                */

                const currentURL =
                    window.location.href
                        .split("#")[0];


                const nextURL =
                    targetURL.href
                        .split("#")[0];


                if (
                    currentURL ===
                    nextURL
                ) {

                    return;

                }


                /*
                    اگر لینک به صفحه فعلی
                    با Query متفاوت باشد،
                    همچنان Loader نمایش داده شود.
                */

                event.preventDefault();


                /*
                    نمایش Loader
                */

                pageLoader.style.display =
                    "flex";


                /*
                    کمی فرصت برای Render
                */

                requestAnimationFrame(
                    function () {

                        pageLoader.classList.remove(
                            "is-hidden"
                        );


                        requestAnimationFrame(
                            function () {

                                window.location.href =
                                    targetURL.href;

                            }
                        );

                    }
                );

            }
        );

    }


    /* =================================================
                    MOBILE MENU OUTSIDE CLICK
    ================================================= */

    document.addEventListener(
        "click",
        function (event) {

            if (
                !mobileMenu ||
                !headerNav
            ) {

                return;

            }


            const clickedInsideMenu =
                headerNav.contains(
                    event.target
                );


            const clickedButton =
                mobileMenu.contains(
                    event.target
                );


            if (
                !clickedInsideMenu &&
                !clickedButton
            ) {

                mobileMenu.classList.remove(
                    "active"
                );


                headerNav.classList.remove(
                    "mobile-open"
                );

            }

        }
    );


    /* =================================================
                    ESC KEY
    ================================================= */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key !== "Escape") {
                return;
            }


            /*
                بستن منوی موبایل
            */

            if (mobileMenu) {

                mobileMenu.classList.remove(
                    "active"
                );

            }


            if (headerNav) {

                headerNav.classList.remove(
                    "mobile-open"
                );

            }

        }
    );


});

/* =========================================================
                    COMING SOON
            مستقل از سایر بخش‌های سایت
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
                    ELEMENTS
    ===================================================== */

    const products =
        document.getElementById("comingProducts");

    const prevButton =
        document.querySelector(".coming-arrow-prev");

    const nextButton =
        document.querySelector(".coming-arrow-next");

    const countdown =
        document.getElementById("comingSoonCountdown");


    /* =====================================================
                    SLIDER
    ===================================================== */

    if (
        products &&
        prevButton &&
        nextButton
    ) {

        /* ================================================
                    GET PRODUCT WIDTH
        ================================================ */

        function getProductScrollAmount() {

            const product =
                products.querySelector(
                    ".coming-product"
                );


            if (!product) {
                return 200;
            }


            const style =
                window.getComputedStyle(product);


            const marginRight =
                parseFloat(style.marginRight) || 0;


            const marginLeft =
                parseFloat(style.marginLeft) || 0;


            return (
                product.offsetWidth +
                marginRight +
                marginLeft
            );

        }


        /* ================================================
                    MAXIMUM SCROLL
        ================================================ */

        function getMaxScroll() {

            return Math.max(
                0,
                products.scrollWidth -
                products.clientWidth
            );

        }


        /* ================================================
                    RTL CHECK
        ================================================ */

        function isRTL() {

            return (
                window.getComputedStyle(
                    products
                ).direction === "rtl"
            );

        }


        /* ================================================
                    UPDATE BUTTONS
        ================================================ */

        function updateButtons() {

            const maxScroll =
                getMaxScroll();


            /*
                اگر تعداد محصولات به اندازه‌ای نیست
                که اسکرول ایجاد شود
            */

            if (maxScroll <= 5) {

                prevButton.disabled = true;
                nextButton.disabled = true;

                return;
            }


            /* ============================================
                            RTL
            ============================================ */

            if (isRTL()) {

                const currentScroll =
                    products.scrollLeft;


                /*
                    ابتدای سمت راست
                */

                const atRightStart =
                    currentScroll >= -5;


                /*
                    انتهای سمت چپ
                */

                const atLeftEnd =
                    Math.abs(currentScroll) >=
                    maxScroll - 5;


                /*
                    دکمه راست = قبلی
                */

                prevButton.disabled =
                    atRightStart;


                /*
                    دکمه چپ = بعدی
                */

                nextButton.disabled =
                    atLeftEnd;


                return;
            }


            /* ============================================
                            LTR
            ============================================ */

            const currentScroll =
                products.scrollLeft;


            const atLeftStart =
                currentScroll <= 5;


            const atRightEnd =
                currentScroll >=
                maxScroll - 5;


            prevButton.disabled =
                atLeftStart;


            nextButton.disabled =
                atRightEnd;

        }


        /* ================================================
                    MOVE SLIDER
        ================================================ */

        function moveSlider(direction) {

            const amount =
                getProductScrollAmount();


            const maxScroll =
                getMaxScroll();


            if (
                !amount ||
                maxScroll <= 0
            ) {
                return;
            }


            /* ============================================
                            RTL
            ============================================ */

            if (isRTL()) {

                const current =
                    products.scrollLeft;


                /*
                    NEXT
                    حرکت راست به چپ
                */

                if (direction === "next") {

                    const target =
                        Math.max(
                            current - amount,
                            -maxScroll
                        );


                    products.scrollTo({

                        left: target,

                        behavior: "smooth"

                    });

                }


                /*
                    PREVIOUS
                    حرکت چپ به راست
                */

                else {

                    const target =
                        Math.min(
                            current + amount,
                            0
                        );


                    products.scrollTo({

                        left: target,

                        behavior: "smooth"

                    });

                }


                return;
            }


            /* ============================================
                            LTR
            ============================================ */

            const current =
                products.scrollLeft;


            if (direction === "next") {

                const target =
                    Math.min(
                        current + amount,
                        maxScroll
                    );


                products.scrollTo({

                    left: target,

                    behavior: "smooth"

                });

            }

            else {

                const target =
                    Math.max(
                        current - amount,
                        0
                    );


                products.scrollTo({

                    left: target,

                    behavior: "smooth"

                });

            }

        }


        /* ================================================
                    PREVIOUS BUTTON
        ================================================ */

        prevButton.addEventListener(
            "click",
            function () {

                if (prevButton.disabled) {
                    return;
                }


                moveSlider("prev");

            }
        );


        /* ================================================
                    NEXT BUTTON
        ================================================ */

        nextButton.addEventListener(
            "click",
            function () {

                if (nextButton.disabled) {
                    return;
                }


                moveSlider("next");

            }
        );


        /* ================================================
                    USER SCROLL
        ================================================ */

        products.addEventListener(
            "scroll",
            function () {

                updateButtons();

            },
            {
                passive: true
            }
        );


        /* ================================================
                    WINDOW RESIZE
        ================================================ */

        window.addEventListener(
            "resize",
            function () {

                updateButtons();

            }
        );


        /* ================================================
                    INITIAL STATE
        ================================================ */

        updateButtons();

    }


    /* =====================================================
                    COUNTDOWN
    ===================================================== */

    if (countdown) {

        const daysElement =
            document.getElementById("comingDays");

        const hoursElement =
            document.getElementById("comingHours");

        const minutesElement =
            document.getElementById("comingMinutes");

        const secondsElement =
            document.getElementById("comingSeconds");


        /* ================================================
                    GET END DATE
        ================================================ */

        const endValue =
            countdown.dataset.end;


        const endDate =
            new Date(endValue).getTime();


        /* ================================================
                    INVALID DATE
        ================================================ */

        if (Number.isNaN(endDate)) {

            console.warn(
                "Coming soon countdown date is invalid."
            );

        }

        else {

            let countdownTimer = null;


            /* ============================================
                        FORMAT NUMBER
            ============================================ */

            function pad(number) {

                return String(number)
                    .padStart(2, "0");

            }


            /* ============================================
                    UPDATE COUNTDOWN
            ============================================ */

            function updateCountdown() {

                const now =
                    Date.now();


                const distance =
                    endDate - now;


                /* ========================================
                        COUNTDOWN FINISHED
                ======================================== */

                if (distance <= 0) {

                    if (daysElement) {
                        daysElement.textContent = "00";
                    }


                    if (hoursElement) {
                        hoursElement.textContent = "00";
                    }


                    if (minutesElement) {
                        minutesElement.textContent = "00";
                    }


                    if (secondsElement) {
                        secondsElement.textContent = "00";
                    }


                    countdown.classList.add(
                        "coming-expired"
                    );


                    if (countdownTimer !== null) {

                        clearInterval(
                            countdownTimer
                        );

                        countdownTimer = null;

                    }


                    return;
                }


                /* ========================================
                        CALCULATE TIME
                ======================================== */

                const totalSeconds =
                    Math.floor(
                        distance / 1000
                    );


                const days =
                    Math.floor(
                        totalSeconds / 86400
                    );


                const hours =
                    Math.floor(
                        (totalSeconds % 86400) /
                        3600
                    );


                const minutes =
                    Math.floor(
                        (totalSeconds % 3600) /
                        60
                    );


                const seconds =
                    totalSeconds % 60;


                /* ========================================
                        UPDATE HTML
                ======================================== */

                if (daysElement) {

                    daysElement.textContent =
                        pad(days);

                }


                if (hoursElement) {

                    hoursElement.textContent =
                        pad(hours);

                }


                if (minutesElement) {

                    minutesElement.textContent =
                        pad(minutes);

                }


                if (secondsElement) {

                    secondsElement.textContent =
                        pad(seconds);

                }

            }


            /* ============================================
                    FIRST EXECUTION
            ============================================ */

            updateCountdown();


            /* ============================================
                    UPDATE EVERY SECOND
            ============================================ */

            countdownTimer =
                setInterval(
                    updateCountdown,
                    1000
                );

        }

    }

});

/* =========================================================
   REVIEWS
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const reviewForm =
            document.getElementById(
                "reviewForm"
            );

        if (!reviewForm) {
            return;
        }


        const mobileInput =
            document.getElementById(
                "reviewMobile"
            );

        const ratingInput =
            document.getElementById(
                "reviewRating"
            );

        const reviewText =
            document.getElementById(
                "reviewText"
            );

        const counter =
            document.getElementById(
                "reviewCounter"
            );

        const submitButton =
            document.getElementById(
                "reviewSubmit"
            );

        const message =
            document.getElementById(
                "reviewMessage"
            );

        const reviewsList =
            document.getElementById(
                "reviewsList"
            );

        const starButtons =
            document.querySelectorAll(
                "#starSelector button"
            );


        /* =====================================================
           Persian Numbers
        ===================================================== */

        function toPersianNumbers(number) {

            const numbers = [
                "۰",
                "۱",
                "۲",
                "۳",
                "۴",
                "۵",
                "۶",
                "۷",
                "۸",
                "۹"
            ];

            return String(number).replace(
                /\d/g,
                function (digit) {

                    return numbers[
                        Number(digit)
                    ];

                }
            );
        }


        /* =====================================================
           Star Rating
        ===================================================== */

        function updateStars(rating) {

            starButtons.forEach(
                function (button) {

                    const value =
                        Number(
                            button.dataset.rating
                        );

                    if (value <= rating) {

                        button.classList.add(
                            "active"
                        );

                        button.textContent =
                            "★";

                    } else {

                        button.classList.remove(
                            "active"
                        );

                        button.textContent =
                            "☆";
                    }

                }
            );
        }


        starButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const rating =
                            Number(
                                this.dataset.rating
                            );

                        ratingInput.value =
                            rating;

                        updateStars(
                            rating
                        );

                    }
                );

            }
        );


        /* =====================================================
           Counter
        ===================================================== */

        function updateCounter() {

            const length =
                reviewText.value.length;

            counter.textContent =
                toPersianNumbers(
                    length
                ) + " / ۱۰۰۰";
        }


        reviewText.addEventListener(
            "input",
            updateCounter
        );

        updateCounter();


        /* =====================================================
           Mobile Input
        ===================================================== */

        mobileInput.addEventListener(
            "input",
            function () {

                this.value =
                    this.value
                        .replace(
                            /\D/g,
                            ""
                        )
                        .slice(
                            0,
                            11
                        );

            }
        );


        /* =====================================================
           Message
        ===================================================== */

        function showMessage(
            text,
            type
        ) {

            message.textContent =
                text;

            message.className =
                "review-message " +
                type;
        }


        /* =====================================================
           Submit
        ===================================================== */

        reviewForm.addEventListener(
            "submit",
            async function (event) {

                event.preventDefault();


                const mobile =
                    mobileInput.value.trim();

                const rating =
                    Number(
                        ratingInput.value
                    );

                const comment =
                    reviewText.value.trim();


                /* شماره موبایل */

                if (
                    !/^09\d{9}$/.test(
                        mobile
                    )
                ) {

                    showMessage(
                        "شماره موبایل صحیح نیست.",
                        "error"
                    );

                    return;
                }


                /* امتیاز */

                if (
                    rating < 1 ||
                    rating > 5
                ) {

                    showMessage(
                        "لطفاً امتیاز خود را انتخاب کنید.",
                        "error"
                    );

                    return;
                }


                /* متن */

                if (
                    comment.length < 3
                ) {

                    showMessage(
                        "نظر باید حداقل ۳ کاراکتر باشد.",
                        "error"
                    );

                    return;
                }


                if (
                    comment.length > 1000
                ) {

                    showMessage(
                        "نظر نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.",
                        "error"
                    );

                    return;
                }


                /* قفل دکمه */

                submitButton.disabled =
                    true;

                submitButton.textContent =
                    "در حال ثبت...";


                /* FormData */

                const formData =
                    new FormData();


                formData.append(
                    "mobile",
                    mobile
                );


                formData.append(
                    "rating",
                    rating
                );


                formData.append(
                    "comment",
                    comment
                );


                try {

                    const response =
                        await fetch(
                            "./submit_comment.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const data =
                        await response.json();


                    if (
                        !data.success
                    ) {

                        showMessage(
                            data.message ||
                            "ثبت نظر انجام نشد.",
                            "error"
                        );

                        return;
                    }


                    /* اضافه کردن نظر */

                    addReviewToList(
                        data.review
                    );


                    /* پاک کردن فرم */

                    reviewForm.reset();


                    ratingInput.value =
                        "0";


                    updateStars(
                        0
                    );


                    updateCounter();


                    showMessage(
                        "نظر شما با موفقیت ثبت شد.",
                        "success"
                    );


                } catch (error) {

                    showMessage(
                        "خطا در ارتباط با سرور.",
                        "error"
                    );


                } finally {

                    submitButton.disabled =
                        false;

                    submitButton.textContent =
                        "ثبت نظر";
                }

            }
        );


        /* =====================================================
           Add Review
        ===================================================== */

        function addReviewToList(
            review
        ) {

            if (!reviewsList) {
                return;
            }


            /* حذف پیام بدون نظر */

            const noReviews =
                document.getElementById(
                    "noReviews"
                );


            if (noReviews) {
                noReviews.remove();
            }


            /* Article */

            const article =
                document.createElement(
                    "article"
                );

            article.className =
                "review-item";


            article.dataset.rating =
                review.rating;


            /* Top */

            const top =
                document.createElement(
                    "div"
                );

            top.className =
                "review-item-top";


            /* Stars */

            const stars =
                document.createElement(
                    "div"
                );

            stars.className =
                "review-stars";


            for (
                let i = 1;
                i <= 5;
                i++
            ) {

                stars.textContent +=
                    i <= Number(
                        review.rating
                    )
                        ? "★"
                        : "☆";
            }


            /* Mobile */

            const mobile =
                document.createElement(
                    "div"
                );

            mobile.className =
                "review-mobile";

            mobile.textContent =
                review.mobile;


            top.appendChild(
                stars
            );

            top.appendChild(
                mobile
            );


            /* Text */

            const text =
                document.createElement(
                    "div"
                );

            text.className =
                "review-text";

            text.textContent =
                review.comment;


            /* Date */

            const date =
                document.createElement(
                    "div"
                );

            date.className =
                "review-date";

            date.textContent =
                review.date;


            article.appendChild(
                top
            );

            article.appendChild(
                text
            );

            article.appendChild(
                date
            );


            /* =================================================
               قرار دادن در جای درست بر اساس امتیاز
            ================================================= */

            const existingReviews =
                reviewsList.querySelectorAll(
                    ".review-item"
                );


            let inserted =
                false;


            for (
                const existingReview
                of existingReviews
            ) {

                const existingRating =
                    Number(
                        existingReview
                            .dataset
                            .rating
                    );


                if (
                    Number(
                        review.rating
                    ) > existingRating
                ) {

                    reviewsList.insertBefore(
                        article,
                        existingReview
                    );

                    inserted =
                        true;

                    break;
                }

            }


            /*
             * اگر امتیاز جدید از همه
             * کمتر یا مساوی باشد،
             * انتهای لیست قرار می‌گیرد.
             */

            if (!inserted) {

                reviewsList.appendChild(
                    article
                );
            }

        }

    }
);

/* =========================================================
   PRODUCTS PAGE
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const productsPage =
        document.querySelector(".products-page");

    if (!productsPage) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER ACCORDIONS
    |--------------------------------------------------------------------------
    */

    const filterSections =
        document.querySelectorAll(
            ".products-filter-section"
        );


    filterSections.forEach(function (section) {

        const button =
            section.querySelector(
                "[data-filter-toggle]"
            );

        if (!button) {
            return;
        }


        button.addEventListener(
            "click",
            function () {

                section.classList.toggle(
                    "is-closed"
                );

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | PRICE INPUTS
    |--------------------------------------------------------------------------
    */

    const minPrice =
        document.querySelector(
            "#min_price"
        );

    const maxPrice =
        document.querySelector(
            "#max_price"
        );


    function cleanPriceInput(input) {

        if (!input) {
            return;
        }


        input.addEventListener(
            "input",
            function () {

                if (
                    this.value !== "" &&
                    Number(this.value) < 0
                ) {

                    this.value = 0;

                }

            }
        );

    }


    cleanPriceInput(minPrice);
    cleanPriceInput(maxPrice);


    /*
    |--------------------------------------------------------------------------
    | FILTER FORM
    |--------------------------------------------------------------------------
    */

    const filterForm =
        document.querySelector(
            ".products-filter-form"
        );


    if (filterForm) {

        filterForm.addEventListener(
            "submit",
            function (event) {

                const min =
                    Number(
                        minPrice?.value || 0
                    );

                const max =
                    Number(
                        maxPrice?.value || 0
                    );


                if (
                    min > 0 &&
                    max > 0 &&
                    min > max
                ) {

                    event.preventDefault();


                    /*
                    | جابه‌جایی خودکار
                    */

                    const oldMin =
                        minPrice.value;

                    minPrice.value =
                        maxPrice.value;

                    maxPrice.value =
                        oldMin;

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | AUTO SUBMIT CATEGORY
    |--------------------------------------------------------------------------
    */

    const categoryInputs =
        document.querySelectorAll(
            'input[name="category"]'
        );


    categoryInputs.forEach(
        function (input) {

            input.addEventListener(
                "change",
                function () {

                    submitProductsFilter();

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | AUTO SUBMIT SORT
    |--------------------------------------------------------------------------
    */

    const sortInputs =
        document.querySelectorAll(
            'input[name="sort"]'
        );


    sortInputs.forEach(
        function (input) {

            input.addEventListener(
                "change",
                function () {

                    submitProductsFilter();

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SUBMIT FILTER
    |--------------------------------------------------------------------------
    */

    function submitProductsFilter() {

        if (!filterForm) {
            return;
        }


        /*
        | هنگام تغییر فیلتر از صفحه اول شروع شود.
        */

        const oldPage =
            filterForm.querySelector(
                'input[name="page"]'
            );


        if (oldPage) {
            oldPage.remove();
        }


        /*
        | کمی تأخیر برای طبیعی‌تر شدن رفتار UI
        */

        filterForm.classList.add(
            "is-loading"
        );


        setTimeout(
            function () {

                filterForm.submit();

            },
            120
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CARD ENTRY ANIMATION
    |--------------------------------------------------------------------------
    */

    const productCards =
        document.querySelectorAll(
            ".product-card"
        );


    productCards.forEach(
        function (card, index) {

            card.style.opacity = "0";

            card.style.transform =
                "translateY(10px)";


            card.style.transition =
                "opacity .35s ease, transform .35s ease";


            setTimeout(
                function () {

                    card.style.opacity = "1";

                    card.style.transform =
                        "translateY(0)";

                },
                40 + (index * 30)
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | PREVENT DOUBLE CLICK
    |--------------------------------------------------------------------------
    */

    const applyButton =
        document.querySelector(
            ".products-apply-button"
        );


    if (filterForm && applyButton) {

        filterForm.addEventListener(
            "submit",
            function () {

                applyButton.disabled = true;

                applyButton.style.opacity =
                    "0.7";

                applyButton.textContent =
                    "در حال اعمال...";

            }
        );

    }

});
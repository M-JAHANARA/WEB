document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* =========================================================
       ELEMENTS
    ========================================================= */

    const filterForm =
        document.querySelector(".offers-filter-form");

    const categoryRadios =
        document.querySelectorAll(
            'input[name="category"]'
        );

    const minPriceInput =
        document.getElementById("minPrice");

    const maxPriceInput =
        document.getElementById("maxPrice");

    const countdownElements =
        document.querySelectorAll(
            ".offer-card-countdown"
        );

    const priceLimit = 10000000;


    /* =========================================================
       PERSIAN NUMBER
    ========================================================= */

    function toPersianNumber(value) {

        const numbers = {
            "0": "۰",
            "1": "۱",
            "2": "۲",
            "3": "۳",
            "4": "۴",
            "5": "۵",
            "6": "۶",
            "7": "۷",
            "8": "۸",
            "9": "۹"
        };

        return String(value).replace(
            /[0-9]/g,
            function (digit) {
                return numbers[digit];
            }
        );
    }


    /* =========================================================
       ENGLISH NUMBER
    ========================================================= */

    function toEnglishNumber(value) {

        const numbers = {
            "۰": "0",
            "۱": "1",
            "۲": "2",
            "۳": "3",
            "۴": "4",
            "۵": "5",
            "۶": "6",
            "۷": "7",
            "۸": "8",
            "۹": "9"
        };

        return String(value)
            .replace(
                /[۰-۹]/g,
                function (digit) {
                    return numbers[digit];
                }
            )
            .replace(/,/g, "")
            .replace(/٬/g, "")
            .replace(/\s/g, "");
    }


    /* =========================================================
       FORMAT PRICE
    ========================================================= */

    function formatPrice(value) {

        const englishValue =
            toEnglishNumber(value);

        if (
            englishValue === "" ||
            Number.isNaN(Number(englishValue))
        ) {
            return "";
        }

        const number =
            Math.min(
                Number(englishValue),
                priceLimit
            );

        return toPersianNumber(
            number.toLocaleString("en-US")
        );
    }


    /* =========================================================
       CLEAN PRICE INPUT
    ========================================================= */

    function cleanPriceInput(input) {

        if (!input) return;

        let value =
            toEnglishNumber(input.value);

        value =
            value.replace(/[^0-9]/g, "");

        if (value === "") {
            input.value = "";
            return;
        }

        let number =
            Number(value);

        if (number > priceLimit) {
            number = priceLimit;
        }

        /*
         * چون input از نوع number است،
         * مقدار واقعی باید انگلیسی باقی بماند.
         */
        input.value = number;
    }


    /* =========================================================
       VALIDATE PRICE
    ========================================================= */

    function validatePriceRange() {

        if (!minPriceInput || !maxPriceInput) {
            return true;
        }

        cleanPriceInput(minPriceInput);
        cleanPriceInput(maxPriceInput);

        const minValue =
            Number(minPriceInput.value) || 0;

        const maxValue =
            Number(maxPriceInput.value) || 0;


        /*
         * اگر هر دو مقدار وجود داشته باشند
         * حداقل نباید از حداکثر بیشتر باشد.
         */

        if (
            minValue > 0 &&
            maxValue > 0 &&
            minValue > maxValue
        ) {

            showPriceError(
                "حداقل قیمت نمی‌تواند بیشتر از حداکثر قیمت باشد."
            );

            return false;
        }


        /*
         * بررسی سقف
         */

        if (minValue > priceLimit) {

            showPriceError(
                "حداقل قیمت نمی‌تواند بیشتر از ۱۰,۰۰۰,۰۰۰ تومان باشد."
            );

            minPriceInput.focus();

            return false;
        }


        if (maxValue > priceLimit) {

            showPriceError(
                "حداکثر قیمت نمی‌تواند بیشتر از ۱۰,۰۰۰,۰۰۰ تومان باشد."
            );

            maxPriceInput.focus();

            return false;
        }


        clearPriceError();

        return true;
    }


    /* =========================================================
       PRICE ERROR
    ========================================================= */

    function showPriceError(message) {

        let error =
            document.querySelector(
                ".offers-price-error"
            );


        if (!error) {

            error =
                document.createElement("div");

            error.className =
                "offers-price-error";

            error.style.width = "100%";
            error.style.marginTop = "9px";
            error.style.padding = "8px 9px";
            error.style.borderRadius = "7px";
            error.style.background =
                "rgba(239, 51, 64, 0.08)";
            error.style.color = "#ef3340";
            error.style.fontFamily =
                "Vazirmatn, sans-serif";
            error.style.fontSize = "9px";
            error.style.lineHeight = "1.8";

            const priceFields =
                document.querySelector(
                    ".offers-price-fields"
                );

            if (priceFields) {
                priceFields.appendChild(error);
            }
        }

        error.textContent = message;
    }


    /* =========================================================
       CLEAR PRICE ERROR
    ========================================================= */

    function clearPriceError() {

        const error =
            document.querySelector(
                ".offers-price-error"
            );

        if (error) {
            error.remove();
        }
    }


    /* =========================================================
       PRICE INPUT EVENTS
    ========================================================= */

    if (minPriceInput) {

        minPriceInput.addEventListener(
            "input",
            function () {

                cleanPriceInput(this);

                clearPriceError();

            }
        );
    }


    if (maxPriceInput) {

        maxPriceInput.addEventListener(
            "input",
            function () {

                cleanPriceInput(this);

                clearPriceError();

            }
        );
    }


    /* =========================================================
       FORM SUBMIT
    ========================================================= */

    if (filterForm) {

        filterForm.addEventListener(
            "submit",
            function (event) {

                /*
                 * اول محدوده قیمت بررسی شود.
                 */

                if (!validatePriceRange()) {

                    event.preventDefault();

                    return;
                }


                /*
                 * اعداد واقعی برای PHP
                 * به صورت انگلیسی ارسال شوند.
                 */

                if (minPriceInput) {
                    minPriceInput.value =
                        toEnglishNumber(
                            minPriceInput.value
                        );
                }

                if (maxPriceInput) {
                    maxPriceInput.value =
                        toEnglishNumber(
                            maxPriceInput.value
                        );
                }

            }
        );
    }


    /* =========================================================
       CATEGORY RADIO
       ========================================================= */

    categoryRadios.forEach(
        function (radio) {

            radio.addEventListener(
                "change",
                function () {

                    /*
                     * با انتخاب دسته‌بندی،
                     * فرم فوراً ارسال می‌شود.
                     */

                    if (!filterForm) {
                        return;
                    }


                    /*
                     * قیمت‌های قبلی حفظ می‌شوند.
                     * بنابراین اگر کاربر همزمان
                     * بازه قیمت داشته باشد،
                     * هر دو فیلتر اعمال خواهند شد.
                     */

                    if (!validatePriceRange()) {
                        return;
                    }


                    filterForm.submit();

                }
            );

        }
    );


    /* =========================================================
       COUNTDOWN
    ========================================================= */

    let globalEndDate = null;


    /*
     * از اولین کارت تاریخ پایان را می‌گیریم.
     */

    if (countdownElements.length > 0) {

        const firstCountdown =
            countdownElements[0];

        const endDate =
            firstCountdown.dataset.end;


        if (endDate) {

            const parsedDate =
                new Date(endDate).getTime();


            if (!Number.isNaN(parsedDate)) {

                globalEndDate =
                    parsedDate;

            }

        }

    }


    /*
     * اگر تاریخ از HTML دریافت نشد،
     * تاریخ پیش‌فرض.
     */

    if (!globalEndDate) {

        globalEndDate =
            new Date(
                "2026-09-30T23:59:59+03:30"
            ).getTime();

    }


    /* =========================================================
       FORMAT TIME
    ========================================================= */

    function formatTime(value) {

        return String(value)
            .padStart(2, "0");

    }


    /* =========================================================
       UPDATE COUNTDOWN
    ========================================================= */

    function updateCountdown() {

        const now =
            Date.now();

        const remaining =
            globalEndDate - now;


        /*
         * اگر زمان تمام شده باشد.
         */

        if (remaining <= 0) {

            countdownElements.forEach(
                function (countdown) {

                    const strongElements =
                        countdown.querySelectorAll(
                            "strong"
                        );


                    if (
                        strongElements.length >= 3
                    ) {

                        strongElements[0]
                            .textContent = "۰۰";

                        strongElements[1]
                            .textContent = "۰۰";

                        strongElements[2]
                            .textContent = "۰۰";

                    }


                    countdown.classList.add(
                        "countdown-expired"
                    );

                }
            );

            return false;
        }


        /*
         * تبدیل میلی‌ثانیه به ثانیه
         */

        const totalSeconds =
            Math.floor(
                remaining / 1000
            );


        /*
         * ساعت
         */

        const hours =
            Math.floor(
                totalSeconds / 3600
            );


        /*
         * دقیقه
         */

        const minutes =
            Math.floor(
                (totalSeconds % 3600) / 60
            );


        /*
         * ثانیه
         */

        const seconds =
            totalSeconds % 60;


        /*
         * اعداد فارسی
         */

        const hourText =
            toPersianNumber(
                formatTime(hours)
            );

        const minuteText =
            toPersianNumber(
                formatTime(minutes)
            );

        const secondText =
            toPersianNumber(
                formatTime(seconds)
            );


        /*
         * اعمال روی تمام کارت‌ها
         */

        countdownElements.forEach(
            function (countdown) {

                const strongElements =
                    countdown.querySelectorAll(
                        "strong"
                    );


                if (
                    strongElements.length >= 3
                ) {

                    strongElements[0]
                        .textContent = hourText;

                    strongElements[1]
                        .textContent = minuteText;

                    strongElements[2]
                        .textContent = secondText;

                }


                countdown.classList.remove(
                    "countdown-expired"
                );

            }
        );


        return true;
    }


    /* =========================================================
       START COUNTDOWN
    ========================================================= */

    updateCountdown();


    let countdownInterval =
        setInterval(
            function () {

                const active =
                    updateCountdown();


                /*
                 * اگر زمان تمام شده،
                 * interval را متوقف کن.
                 */

                if (!active) {

                    clearInterval(
                        countdownInterval
                    );

                    countdownInterval = null;

                }

            },
            1000
        );


});
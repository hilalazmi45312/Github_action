// Global variables
let activeTab = 'mobile';
let txId = null;
const otpInputs = document.querySelectorAll('.otp-inputs input');
const enterTac = document.querySelector('#enter-tac');
const loginMobile = document.querySelector('#login-mobile');
const loginIc = document.querySelector('#login-ic');
const loginEmail = document.querySelector('#login-email');
const enterPassword = document.querySelector('#enter-password');
const phoneNumber = document.getElementById('phone_number');
const countryCode = document.getElementById('country_code');
const icNumber = document.getElementById('ic_number');
const email = document.getElementById('custom-email');
const password = document.getElementById('custom-password');
const resendCode = document.getElementById('resend-code');
const maskedPhone = document.getElementById('masked_phone');
const loginMobileButton = document.querySelector('.login-mobile-button');
const loginIcButton = document.querySelector('.login-ic-button');
const loginEmailButton = document.querySelector('.login-email-button');
const submitPasswordButton = document.querySelector('.submit-password-button');
const loginCard = document.querySelector('.login-card');
const registerCard = document.querySelector('.register-card');
const registerPhone = document.querySelector('#register-phone');
const enterTacRegister = document.querySelector('#enter-tac-register');
const enterDetailsRegister = document.querySelector('#enter-details-register');
const fullNameReg = document.getElementById('full_name_reg');
const emailReg = document.getElementById('email_reg');
const icNumberReg = document.getElementById('ic_number_reg');
const phoneNumberReg = document.getElementById('phone_number_reg');
const passwordReg = document.getElementById('password_reg');
const cPasswordReg = document.getElementById('c_password_reg');
const otpInputsRegister = document.querySelectorAll('.otp-inputs-register input');
let loginTurnstileRendered = false;
let registerTurnstileRendered = false;
let loginCfToken = "";
let registerCfToken = "";
let loginTurnstileWidgetId = null;
let registerTurnstileWidgetId = null;

// Utility Functions

function closeLoginPopup() {
    jQuery('.login-container').removeClass('active').fadeOut(300);
    jQuery('.login-overlay').fadeOut(300);

    destroyTurnstile(); // 🔥 Stop Turnstile
}

function filterPhoneNumber(countryCode, phoneNumber) {
    let filtered = phoneNumber.replace(/\D/g, '');
    if (filtered.startsWith('0')) filtered = filtered.slice(1);
    if (filtered.startsWith('60')) filtered = filtered.slice(2).replace(/^0/, '');
    return `${countryCode}${filtered}`;
}

function validateIC(input) {
    input.value = input.value.replace(/[^0-9a-zA-Z]/g, '');
    if (input.value.length > 12 || input.value.length < 12) {
        input.classList.add('invalid-input-popup');
    } else {
        input.classList.remove('invalid-input-popup');
    }
    // if (/^\d/.test(input.value)) {
    //     input.maxLength = 12;
    // } else {
    //     input.maxLength = 10;
    // }
}

function maskNumber(num) {
    // Convert to string to handle both numeric and string inputs
    const str = num.toString();

    // Ensure number starts with +60
    const formatted = str.startsWith('+') ? str : '+' + str;

    // Mask everything after +60 except the last 3 digits
    return formatted.slice(0, 3) + '****' + formatted.slice(-3);
}

function manageButtonState(button, enable, text = '') {
    button.disabled = !enable;
    button.innerHTML = enable ? text : '<i class="fa fa-circle-o-notch fa-spin" style="font-size:16px"></i>';
}

function showSwalError(title, message) {
    const alertBox = document.getElementById("customAlert");
    const alertText = document.getElementById("customAlertText");

    alertText.textContent = message || title;
    alertBox.classList.remove("hidden");

    // Auto hide after 3 seconds
    setTimeout(() => {
        alertBox.classList.add("hidden");
    }, 3000);
}

function showLoading() {
    const loadingBox = document.getElementById("customLoading");
    loadingBox.classList.remove("hidden");
}

function hideLoading() {
    document.getElementById("customLoading").classList.add("hidden");
}

function makeAjaxRequest(url, data, onSuccess) {
    jQuery.ajax({
        url,
        type: 'POST',
        dataType: 'json',
        data,
        success: onSuccess,
        error: () => {
            hideLoading();
            showSwalError('Error', 'An error occurred. Please contact support.');
        }
    });
}

function phoneNumberValidation(input) {
    // Remove anything that is not a digit
    input.value = input.value.replace(/\D/g, '');

    if (input.value.length < 8 || input.value.length > 10) {
        input.classList.add('invalid-input-popup');
    } else {
        input.classList.remove('invalid-input-popup');
    }
}

function emailValidation(e) {
    const value = e.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(value)) {
        e.classList.add('invalid-input-popup');
    } else {
        e.classList.remove('invalid-input-popup');
    }
}

function passwordValidation(e) {
    const value = e.value.trim();

    // Validation conditions
    const hasLength = value.length >= 8;
    const hasUppercase = /[A-Z]/.test(value);
    const hasNumber = /\d/.test(value);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(value);

    if (hasLength && hasUppercase && hasNumber && hasSpecial) {
        password.classList.remove('invalid-input-popup');
    } else {
        password.classList.add('invalid-input-popup');
    }
}

// UI Manipulation
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.querySelector(`#login-${tab}`).style.display = 'block';
    event.currentTarget.classList.add('active');
    activeTab = tab;
}

function togglePassword() {
    const isPasswordVisible = password.type === 'text';
    password.type = isPasswordVisible ? 'password' : 'text';
    const toggleIcon = document.getElementById('toggle-password');
    toggleIcon.classList.toggle('fa-eye', !isPasswordVisible);
    toggleIcon.classList.toggle('fa-eye-slash', isPasswordVisible);
}

function showOtpScreen() {
    otpInputs.forEach(input => input.value = '');
    enterTac.style.display = 'block';
    document.querySelector(`#login-${activeTab}`).style.display = 'none';
}

function goBack() {
    enterTac.style.display = 'none';
    document.querySelector(`#login-${activeTab}`).style.display = 'block';
}

// Authentication Functions
function requestOtp(identifier, type) {
    // let maskNumberText = maskNumber(identifier);
    // maskedPhone.textContent = maskNumberText;
    showOtpScreen();
    manageButtonState(resendCode, false);

    makeAjaxRequest(ajaxUrl, { action: 'request_otp', phone: identifier, type }, response => {
        if (response.success && response.data.flag === 1) {
            txId = response.data.tx_id || '';
            maskedPhone.textContent = `A verification code has been sent to ${response.data.masked_phone}`;
        } else {
            showSwalError('Error', response.data.message || 'Failed to send OTP. Please try again.');
            if (response.data.flag === 2) goBack();
        }
        manageButtonState(resendCode, true, 'Resend');
    });
}

function verifyLogin(identifier, credential, type) {
    if (!loginCfToken) {
        return showSwalError(
            'Verification required',
            'Please complete the human verification.'
        );
    }
    showLoading();
    makeAjaxRequest(ajaxUrl, { action: 'login', phone: identifier, otp: credential, tx_id: txId, type, cf_token: loginCfToken }, response => {
        // Swal.close();
        hideLoading();
        if (response.success) {
            jQuery('.login-container').removeClass('active');
            jQuery(this).fadeOut(300);
            setTimeout(() => {
                jQuery('.login-container').css('display', 'none');
                window.location.href = response.data.redirect_url;
            }, 400);
            popupVisible = false;
        } else {
            showSwalError('Error', response.data.message || 'Invalid OTP. Please try again.');
            resetTurnstile("login");
        }
    });
}

function checkingPhone(identifier, type = 'CONTACT', button) {
    manageButtonState(button, false);

    makeAjaxRequest(ajaxUrl, { action: 'checking_phone', phone: identifier, type }, response => {
        if (response.success) {
            if (response.data.flag === 1) {
                if (response.data.message === 'Contact no exist.') {
                    response.data.message = 'Please register a new account';
                }
                showSwalError('Account Not Found', response.data.message);
                goToRegisterTab();
            } else {
                requestOtp(identifier, type);
            }
        } else {
            showSwalError('Error', 'Failed to verify the provided data. Please try again.');
        }
        manageButtonState(button, true, 'Continue');
    });
}

// Popup handling
jQuery(document).ready(() => {
    const side = jQuery('.cart-widget-side');
    if (side.length) {
        side.after('<div class="login-overlay"></div>');
    }

    let popupVisible = false;
    const loginHeaderButton = jQuery('.wd-header-my-account a');

    loginHeaderButton.on('click', function (e) {
        e.preventDefault();
        const popup = jQuery('.login-container');

        if (popupVisible) {
            popup.removeClass('active');
            jQuery('.login-overlay').fadeOut(300);
            setTimeout(() => {
                popup.css('display', 'none');
            }, 400);
            popupVisible = false;
            return;
        }

        // popup.css('display', 'block');
        // setTimeout(() => {
        //     popup.addClass('active');
        //     jQuery('.login-overlay').fadeIn(300);
        // }, 0);
        // popupVisible = true;
        if (!popupVisible) {
            popup.css('display', 'block');
            setTimeout(() => {
                popup.addClass('active');
                jQuery('.login-overlay').fadeIn(300);

                // Load the Turnstile only now
                renderLoginTurnstile();
            }, 0);

            popupVisible = true;
            return;
        }
    });

    jQuery(document).on('click', '.login-overlay', function () {
        jQuery('.login-container').removeClass('active');
        jQuery(this).fadeOut(300);
        setTimeout(() => {
            jQuery('.login-container').css('display', 'none');
        }, 400);
        popupVisible = false;

        destroyTurnstile(); // 🔥 Stop Turnstile
    });
});

// Login button handlers
loginMobileButton.addEventListener('click', () => {
    if (!loginCfToken) {
        return showSwalError(
            'Verification required',
            'Please complete the human verification first.'
        );
    }
    if (!phoneNumber.value) return showSwalError('Required', 'Phone number cannot be empty.');
    if (phoneNumber.value.length < 8 || phoneNumber.value.length > 10) return showSwalError('Required', 'Please enter a valid phone number');
    const fullPhone = filterPhoneNumber(countryCode.value, phoneNumber.value);
    checkingPhone(fullPhone, 'CONTACT', loginMobileButton);
});

loginIcButton.addEventListener('click', () => {
    if (!icNumber.value) return showSwalError('Required', 'IC / MyKad number cannot be empty.');
    if (icNumber.value.length !== 12) return showSwalError('Required', 'Please enter a valid IC number');
    checkingPhone(icNumber.value, 'ICNO', loginIcButton);
});

loginEmailButton.addEventListener('click', () => {
    if (!email.value) return showSwalError('Required', 'Email cannot be empty.');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) return showSwalError('Required', 'Please enter a valid email');
    enterPassword.style.display = 'block';
    loginEmail.style.display = 'none';
});

submitPasswordButton.addEventListener('click', () => {
    if (!email.value || !password.value) {
        return showSwalError('Required', 'Email and password cannot be empty.');
    }
    verifyLogin(email.value, password.value, 'EMAIL');
});

// OTP input handling
otpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        if (input.value.length === 1 && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
        } else if (index === otpInputs.length - 1) {
            const fullOtp = Array.from(otpInputs).map(input => input.value).join('');
            if (fullOtp.length === otpInputs.length) {
                const identifier = activeTab === 'ic'
                    ? icNumber.value
                    : filterPhoneNumber(countryCode.value, phoneNumber.value);
                verifyLogin(identifier, fullOtp, activeTab === 'ic' ? 'ICNO' : 'CONTACT');
            }
        }
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && input.value === '' && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});

// Resend OTP
resendCode.addEventListener('click', () => {
    const identifier = activeTab === 'ic'
        ? icNumber.value
        : filterPhoneNumber(countryCode.value, phoneNumber.value);
    requestOtp(identifier, activeTab === 'ic' ? 'ICNO' : 'CONTACT');
});

// Register/Login toggle
document.querySelectorAll('.register-button').forEach(button => {
    button.addEventListener('click', () => {
        loginCard.style.display = 'none';
        registerCard.style.display = 'block';
        registerPhone.style.display = 'block';
        enterTacRegister.style.display = 'none';
        enterDetailsRegister.style.display = 'none';
        otpInputsRegister.forEach(input => input.value = '');
        fullNameReg.value = '';
        emailReg.value = '';
        icNumberReg.value = '';
        phoneNumberReg.value = '';
        passwordReg.value = '';
        cPasswordReg.value = '';
        // Move Turnstile into register card
        renderRegisterTurnstile();
    });
});

document.querySelectorAll('.login-button').forEach(button => {
    button.addEventListener('click', () => {
        registerCard.style.display = 'none';
        loginCard.style.display = 'block';
        // Move Turnstile back to login card
        renderLoginTurnstile();
    });
});


function goToRegisterTab() {
    loginCard.style.display = 'none';
    registerCard.style.display = 'block';
    registerPhone.style.display = 'block';
    enterTacRegister.style.display = 'none';
    enterDetailsRegister.style.display = 'none';
    otpInputsRegister.forEach(input => input.value = '');
    fullNameReg.value = '';
    emailReg.value = '';
    icNumberReg.value = '';
    phoneNumberReg.value = '';
    passwordReg.value = '';
    cPasswordReg.value = '';
    // Move Turnstile into register card
    renderRegisterTurnstile();
}


function renderLoginTurnstile() {
    const container = document.getElementById("cf-login");

    // Ensure container is empty
    container.innerHTML = "";

    loginTurnstileWidgetId = turnstile.render("#cf-login", {
        sitekey: "0x4AAAAAACGMt9jHIDiBnaff",
        theme: "light",
        callback: function (token) {
            loginCfToken = token;
            console.log("Login Turnstile token:", token);
        }
    });
}

function renderRegisterTurnstile() {
    const container = document.getElementById("cf-register");

    container.innerHTML = "";

    registerTurnstileWidgetId = turnstile.render("#cf-register", {
        sitekey: "0x4AAAAAACGMt9jHIDiBnaff",
        theme: "light",
        callback: function (token) {
            registerCfToken = token;
            console.log("Register Turnstile token:", token);
        }
    });
}

function destroyTurnstile() {
    // Destroy login widget
    if (loginTurnstileWidgetId) {
        document.getElementById("cf-login").innerHTML = "";
        loginTurnstileWidgetId = null;
        loginCfToken = "";
    }

    // Destroy register widget
    if (registerTurnstileWidgetId) {
        document.getElementById("cf-register").innerHTML = "";
        registerTurnstileWidgetId = null;
        registerCfToken = "";
    }
}

function resetTurnstile(type = "login") {
    if (type === "login" && loginTurnstileWidgetId) {
        turnstile.reset(loginTurnstileWidgetId);
        loginCfToken = "";
    }

    if (type === "register" && registerTurnstileWidgetId) {
        turnstile.reset(registerTurnstileWidgetId);
        registerCfToken = "";
    }
}
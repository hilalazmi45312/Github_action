const otpInputs = document.querySelectorAll('.step-2-otp');
const sendOtpButton = document.querySelectorAll('.login-button');
const emailButton = document.getElementById('continue-email');
const resendOtpButton = document.getElementById('resend-code');
let txId = '';
let activeTab = 'phone';

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');

    document.querySelector(`#login-${tab}`).style.display = 'block';
    event.currentTarget.classList.add('active');
    activeTab = tab;
}

function togglePassword() {
    const passwordInput = document.getElementById("password");
    const toggleIcon = document.getElementById("toggle-password");
    const isPasswordVisible = passwordInput.type === "text";
    passwordInput.type = isPasswordVisible ? "password" : "text";
    toggleIcon.classList.toggle("fa-eye", !isPasswordVisible);
    toggleIcon.classList.toggle("fa-eye-slash", isPasswordVisible);
}

function validatePassword() {
    // const password = document.getElementById("password").value;
    // const rules = {
    //     length: password.length >= 8,
    //     uppercase: /[A-Z]/.test(password),
    //     number: /\d/.test(password),
    //     special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    // };
    // ['length', 'uppercase', 'number', 'special'].forEach(rule => {
    //     document.getElementById(`rule-${rule}`).classList.toggle("valid", rules[rule]);
    // });

    // passwordPassed = Object.values(rules).every(Boolean);
}

function validateIC(input) {
    input.value = input.value.replace(/[^0-9a-zA-Z]/g, '');
    // if (/^\d/.test(input.value)) {
    //     input.maxLength = 12;
    // } else {
    //     input.maxLength = 10;
    // }
}


function getFieldValue(activeTab) {
    let value = '';
    let typeField = {};

    switch (activeTab) {
        case 'email':
            value = document.getElementById('email').value.trim();
            value = (value);
            if (!value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required',
                    text: 'Email cannot be empty.',
                });
                return null;
            }
            typeField = {
                action: 'EMAIL',
                phone: value
            };
            break;

        case 'ic':
            value = document.getElementById('ic_number').value.trim();
            value = (value);
            if (!value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required',
                    text: 'IC number cannot be empty.',
                });
                return null;
            }
            typeField = {
                action: 'ICNO',
                phone: value
            };
            break;

        default:
            const phone = document.getElementById('phone_number').value.trim();
            const countryCode = document.getElementById('country_code').value.trim();

            if (!phone) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required',
                    text: 'Phone number cannot be empty.',
                });
                return null;
            }

            typeField = {
                action: 'CONTACT',
                phone: filterPhoneNumber(countryCode, phone)
            };
    }

    return typeField;
}

function updateStepUI(stepIndex) {
    document.querySelectorAll('.step').forEach((step, i) => {
        step.classList.toggle('active', i === stepIndex);
    });
    document.querySelectorAll('.step-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === stepIndex);
    });
    document.querySelectorAll('.remove-step-2').forEach(el => {
        el.style.display = stepIndex === 2 ? 'none' : '';
    });

    //reset button states
    sendOtpButton.forEach(button => {
        button.disabled = false;
        button.innerHTML = 'Next';
    });

    emailButton.disabled = false;
    emailButton.innerHTML = 'Continue';
}

function goToStep(stepIndex) {
    updateStepUI(stepIndex);
}

function goBack() {
    goToStep(0);
}

function filterPhoneNumber(country_code, phone_number) {
    let filtered = phone_number.replace(/\D/g, '');
    if (filtered.startsWith('0')) filtered = filtered.slice(1);
    if (filtered.startsWith('60')) filtered = filtered.slice(2).replace(/^0/, '');
    return `${country_code}${filtered}`;
}

function handleAjaxError(error, message) {
    console.error(message, error);
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred. Please contact support.',
    });
}

function manageButtonState(button, enable, text) {
    button.disabled = !enable;
    button.innerHTML = enable ? text : '<i class="fa fa-circle-o-notch fa-spin" style="font-size:16px"></i>';
}

function makeAjaxRequest(url, data, onSuccess) {
    jQuery.ajax({
        url,
        type: 'POST',
        dataType: 'json',
        data,
        success: onSuccess,
        error: (xhr, status, error) => handleAjaxError(error, `Error in ${data.action}:`)
    });
}

function checkingPhone(phone, type = 'CONTACT') {
    console.log(`Checking phone: ${phone}, type: ${type}`);
    // Remove return to enable AJAX
    makeAjaxRequest(ajaxUrl, { action: 'checking_phone', phone, type }, response => {
        const { success, data } = response;

        if (success) {
            if (data.flag === 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Not Found',
                    html: data.message,
                });
            } else {
                requestOtp(phone, type);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to verify the provided data. Please try again.',
            });
        }
    });
}

function requestOtp(phone, type) {
    manageButtonState(resendOtpButton, false);
    makeAjaxRequest(ajaxUrl, { action: 'request_otp', phone, type }, response => {
        if (response.success && response.data.flag === 1) {
            txId = response.data.tx_id || '';
            jQuery('#masked_phone').text(response.data.masked_phone);
            goToStep(1);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data.message || 'Failed to send OTP. Please try again.',
            });
            if (response.data.flag === 2) goToStep(0);
        }
        manageButtonState(resendOtpButton, true, 'Resend');
    });
}

function verifyOtp(fullPhone, otp, type) {
    Swal.fire({
        title: 'Logging in...',
        html: 'Please wait while we process your login.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    makeAjaxRequest(ajaxUrl, { action: 'login', phone: fullPhone, otp, tx_id: txId, type }, response => {
        Swal.close();
        if (response.success) {
            let timerInterval;
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.data.message || 'Login successful!',
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    timerInterval = setInterval(() => { }, 100);
                },
                willClose: () => {
                    clearInterval(timerInterval);
                }
            }).then((result) => {
                window.location.href = response.data.redirect_url;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data.message || 'Invalid OTP. Please try again.',
            });
        }
        document.getElementById('image-1').classList.remove('bouncy-image');
        if (type === 'EMAIL') {
            manageButtonState(document.getElementById('login-button'), true, 'Login');
        }
    });
}

function loginEmail() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    if (!email || !password) {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Email and password cannot be empty.',
        });
        return;
    }
    verifyOtp(email, password, 'EMAIL');
    manageButtonState(document.getElementById('login-button'), false, 'Logging in...');
}

document.addEventListener('DOMContentLoaded', () => {

    emailButton.addEventListener('click', () => {
        const emailField = document.getElementById('email').value.trim();
        //check also for email validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailField)) {
            Swal.fire({
                icon: 'warning',
                title: 'Required',
                text: 'Email is not valid.',
            });
            return;
        }
        manageButtonState(emailButton, false);
        goToStep(2);
    });

    sendOtpButton.forEach(button => {
        button.addEventListener('click', () => {
            const getField = getFieldValue(activeTab);

            if (!getField) return; // Abort if field is invalid

            console.log(`Field value: ${JSON.stringify(getField)}`);

            manageButtonState(button, false);

            checkingPhone(getField.phone, getField.action);

            jQuery(document).one('ajaxStop', () => manageButtonState(button, true, 'Next'));
        });
    });

    otpInputs.forEach((input, index) => {
        console.log(`Setting up input ${index}`);
        input.addEventListener('input', () => {
            console.log(`Input ${index} changed: ${input.value}`);
            if (input.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            } else if (index === otpInputs.length - 1) {
                const fullOtp = Array.from(otpInputs).map(input => input.value).join('');
                if (fullOtp.length === otpInputs.length) { // All boxes filled
                    const fullPhone = filterPhoneNumber(
                        document.getElementById('country_code').value,
                        document.getElementById('phone_number').value
                    );
                    const icNumber = document.getElementById('ic_number').value;
                    document.getElementById('image-1').classList.add('bouncy-image');
                    if (activeTab == 'ic') {
                        verifyOtp(icNumber, fullOtp, 'ICNO');
                    }
                    else {
                        verifyOtp(fullPhone, fullOtp, 'CONTACT');
                    }
                }
            }
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && input.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    resendOtpButton.addEventListener('click', () => {
        const fullPhone = filterPhoneNumber(
            document.getElementById('country_code').value,
            document.getElementById('phone_number').value
        );
        const icNumber = document.getElementById('ic_number').value;
        document.getElementById('image-1').classList.add('bouncy-image');
        if (activeTab == 'ic') {
            requestOtp(icNumber, 'ICNO');
        }
        else {
            requestOtp(fullPhone, 'CONTACT');
        }
    });


});
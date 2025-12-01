const otpInputs = document.querySelectorAll('.step-2-otp');
const sendOtpButton = document.getElementById('send_otp');
const resendOtpButton = document.getElementById('resend-code');
const createUser = document.getElementById('create_user');
let txId = '';
let passwordPassed = false;

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
}

function goToStep(stepIndex) {
    updateStepUI(stepIndex);
}

function goBack() {
    goToStep(0);
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
    const password = document.getElementById("password").value;
    const rules = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    ['length', 'uppercase', 'number', 'special'].forEach(rule => {
        document.getElementById(`rule-${rule}`).classList.toggle("valid", rules[rule]);
    });

    passwordPassed = Object.values(rules).every(Boolean);
}

function validateIC(input) {
    input.value = input.value.replace(/[^0-9a-zA-Z]/g, '');
    // if (/^\d/.test(input.value)) {
    //     input.maxLength = 12;
    // } else {
    //     input.maxLength = 10;
    // }
}

function filterPhoneNumber(country_code, phone_number) {
    let filtered = phone_number.replace(/\D/g, '');
    if (filtered.startsWith('0')) filtered = filtered.slice(1);
    if (filtered.startsWith('60')) filtered = filtered.slice(2).replace(/^0/, '');
    if (country_code === '')
    {
        country_code = '60';
    }
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

function checkingPhone(phone) {
    makeAjaxRequest(ajaxUrl, { action: 'checking_phone', phone }, response => {
        const { success, data } = response;
        if (success) {
            if (data.dialog_info && data.flag === 5) {
                Swal.fire({
                    icon: 'warning',
                    title: data.dialog_info.TITLE,
                    html: data.dialog_info.MESSAGE.replace(/\.\s+/g, '.<br><br>'),
                });
            } else {
                requestOtp(phone);
            }
        }
    });
}

function requestOtp(phone) {
    manageButtonState(resendOtpButton, false);
    makeAjaxRequest(ajaxUrl, { action: 'request_otp', phone, type: 'REGISTER' }, response => {
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
            if (response.data.flag === 2 || response.data.flag === 0) goToStep(0);
        }
        manageButtonState(resendOtpButton, true, 'Resend');
    });
}

function verifyOtp(fullPhone, otp) {
    Swal.fire({
        title: 'Verifying OTP...',
        html: 'Please wait while we process your registration.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    makeAjaxRequest(ajaxUrl, { action: 'verify_otp', phone: fullPhone, otp, tx_id: txId }, response => {
        Swal.close();
        if (response.success && response.data.status === 'SUCCESS') {
            goToStep(2);
            document.getElementById('full_name').focus();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data.message || 'Invalid OTP. Please try again.',
            });
        }
        document.getElementById('image-1').classList.remove('bouncy-image');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    sendOtpButton.addEventListener('click', () => {
        const phone = document.getElementById('phone_number').value;
        const code = document.getElementById('country_code').value;
        if (!phone) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please enter a valid phone number.',
            });
            return;
        }
        manageButtonState(sendOtpButton, false);
        checkingPhone(filterPhoneNumber(code, phone));
        jQuery(document).one('ajaxStop', () => manageButtonState(sendOtpButton, true, 'Next'));
    });

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            } else if (index === otpInputs.length - 1) {
                const fullOtp = Array.from(otpInputs).map(input => input.value).join('');
                if (fullOtp.length === otpInputs.length) { // All boxes filled
                    const fullPhone = filterPhoneNumber(
                        document.getElementById('country_code').value,
                        document.getElementById('phone_number').value
                    );
                    document.getElementById('image-1').classList.add('bouncy-image');
                    verifyOtp(fullPhone, fullOtp);
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
        requestOtp(fullPhone);
    });

    createUser.addEventListener('click', () => {
        const fullName = document.getElementById('full_name').value;
        const email = document.getElementById('email').value;
        const ic_number = document.getElementById('ic_number').value;
        const password = document.getElementById('password').value;
        const termsAccepted = document.getElementById('terms').checked;

        if (!fullName) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter your full name.' });
            return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter a valid email address.' });
            return;
        }
        if (!ic_number || !/^\d{12}$/.test(ic_number)) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter a valid IC number.' });
            return;
        }
        if (!password) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter your password.' });
            return;
        }
        if (!passwordPassed) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Password does not meet the required criteria.' });
            return;
        }
        if (!termsAccepted) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please accept the terms and conditions.' });
            return;
        }

        manageButtonState(createUser, false);
        makeAjaxRequest(ajaxUrl, {
            action: 'create_user',
            full_name: fullName,
            email,
            ic_number,
            password,
            phone: filterPhoneNumber(
                document.getElementById('country_code').value,
                document.getElementById('phone_number').value
            ),
            terms: termsAccepted
        }, response => {
            let responseData = response.data || {};
            console.log('Response Data:', responseData);
            if (response.success) {
                let timerInterval;
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.data.message || 'User created successfully!',
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
                    html: responseData.message || 'Failed to create user. Please try again.',
                });
            }
            manageButtonState(createUser, true, 'Create User');
        });
    });

});
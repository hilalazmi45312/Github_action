// Global DOM elements
// const otpInputsRegister = document.querySelectorAll('.otp-inputs-register input');
// const enterTacRegister = document.querySelector('#enter-tac-register');
// const registerPhone = document.querySelector('#register-phone');
// const enterDetailsRegister = document.querySelector('#enter-details-register');
const registerCountryCode = document.getElementById('register-country_code');
const registerPhoneNumber = document.getElementById('register-phone_number');
// const phoneNumberReg = document.getElementById('phone_number_reg');
const countryCodeReg = document.getElementById('country_code_reg');
const resendCodeReg = document.getElementById('resend-code-reg');
const registerMaskedPhone = document.getElementById('register_masked_phone');
const createUser = document.getElementById('create-user');
const registerPhoneButton = document.querySelector('.register-phone-button');
// const fullNameReg = document.getElementById('full_name_reg');
// const emailReg = document.getElementById('email_reg');
// const icNumberReg = document.getElementById('ic_number_reg');
// const passwordReg = document.getElementById('password_reg');
// const cPasswordReg = document.getElementById('c_password_reg');
// const termsCheckbox = document.getElementById('terms');

let passwordPassed = false;

function showOtpScreenRegister() {
    enterTacRegister.style.display = 'block';
    registerPhone.style.display = 'none';
}

function showRegisterScreen() {
    enterTacRegister.style.display = 'none';
    enterDetailsRegister.style.display = 'block';
    phoneNumberReg.value = registerPhoneNumber.value;
    countryCodeReg.value = registerCountryCode.value;
}

function goBackRegister() {
    enterTacRegister.style.display = 'none';
    registerPhone.style.display = 'block';
}

function checkingPhoneForRegister(phone, button) {
    manageButtonState(button, false);
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
                requestOtpRegister(phone, 'REGISTER');
            }
        }
        manageButtonState(button, true, 'Next');
    });
}

function requestOtpRegister(identifier, type) {
    showOtpScreenRegister();
    manageButtonState(resendCodeReg, false);

    makeAjaxRequest(ajaxUrl, { action: 'request_otp', phone: identifier, type }, response => {
        if (response.success && response.data.flag === 1) {
            txId = response.data.tx_id || '';
            registerMaskedPhone.textContent = response.data.masked_phone;
        } else {
            showSwalError('Error', response.data.message || 'Failed to send OTP. Please try again.');
            if (response.data.flag === 2) goBackRegister();
        }
        manageButtonState(resendCodeReg, true, 'Resend');
    });
}

function verifyOtpRegister(phone, otp, type) {
    console.log('TXID :' + txId);
    Swal.fire({
        title: 'Verifying...',
        html: 'Please wait while we verify your details.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    makeAjaxRequest(ajaxUrl, { action: 'verify_otp', phone, otp, type, tx_id: txId }, response => {
        if (response.success && response.data.status === 'SUCCESS') {
            Swal.close();
            showRegisterScreen();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data.message || 'Invalid OTP. Please try again.',
            });
        }
    });
}

function validatePassword(password) {
    const rules = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };

    // If all rules pass
    if (Object.values(rules).every(Boolean)) {
        passwordPassed = true;
        return true; // ✅ explicitly return true
    }

    passwordPassed = false;

    // Build a list of missing criteria
    const missing = [];
    if (!rules.length) missing.push('• At least 8 characters long');
    if (!rules.uppercase) missing.push('• Contains at least one uppercase letter (A–Z)');
    if (!rules.number) missing.push('• Contains at least one number (0–9)');
    if (!rules.special) missing.push('• Contains at least one special character (!@#$...)');

    // Show SweetAlert for missing rules
    Swal.fire({
        icon: 'error',
        title: 'Weak Password',
        html: `
            Your password must meet the following requirements:<br><br>
            ${missing.join('<br>')}
        `,
    });

    return false; // ✅ explicitly return false
}

otpInputsRegister.forEach((input, index) => {
    input.addEventListener('input', () => {
        if (input.value.length === 1 && index < otpInputsRegister.length - 1) {
            otpInputsRegister[index + 1].focus();
        } else if (index === otpInputsRegister.length - 1) {
            const fullOtp = Array.from(otpInputsRegister).map(input => input.value).join('');
            if (fullOtp.length === otpInputsRegister.length) {
                const identifier = filterPhoneNumber(registerCountryCode.value, registerPhoneNumber.value);
                verifyOtpRegister(identifier, fullOtp, 'REGISTER');
            }
        }
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && input.value === '' && index > 0) {
            otpInputsRegister[index - 1].focus();
        }
    });
});

registerPhoneButton.addEventListener('click', () => {
    const phoneNumber = registerPhoneNumber.value;
    if (!phoneNumber) return showSwalError('Required', 'Phone number cannot be empty.');

    const fullPhone = filterPhoneNumber(registerCountryCode.value, phoneNumber);
    checkingPhoneForRegister(fullPhone, registerPhoneButton);
});

resendCodeReg.addEventListener('click', () => {
    const fullPhone = filterPhoneNumber(registerCountryCode.value, registerPhoneNumber.value);
    requestOtpRegister(fullPhone, 'REGISTER');
});

createUser.addEventListener('click', () => {
    const fullName = fullNameReg.value;
    const phoneNumber = phoneNumberReg.value;
    const email = emailReg.value;
    const icNumber = icNumberReg.value;
    const password = passwordReg.value;
    const cPassword = cPasswordReg.value;
    // const termsAccepted = termsCheckbox.checked;

    if (!fullName) return Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter your full name.' });
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter a valid email address.' });
    }

    if (!phoneNumber) {
        return Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter your phone number.' });
    }

    if (!icNumber || !/^\d{12}$/.test(icNumber)) {
        return Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter a valid IC number.' });
    }
    if (!password) return Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter your password.' });

    passwordPassed = validatePassword(password);

    if (!passwordPassed) {
        manageButtonState(createUser, true, 'Next');
        return;
    }

    if (password !== cPassword) {
        return Swal.fire({ icon: 'error', title: 'Error', text: 'Passwords do not match.' });
    }

    console.log('passwordPassed ' + passwordPassed);
    console.log('password ' + password);
    console.log('cPassword ' + cPassword);

    // if (!termsAccepted) {
    //     return Swal.fire({ icon: 'error', title: 'Error', text: 'Please accept the terms and conditions.' });
    // }

    manageButtonState(createUser, false);
    makeAjaxRequest(ajaxUrl, {
        action: 'create_user',
        full_name: fullName,
        email,
        ic_number: icNumber,
        password,
        phone: filterPhoneNumber(registerCountryCode.value, registerPhoneNumber.value),
        // terms: termsAccepted
    }, response => {
        const responseData = response.data || {};
        if (response.success) {
            let timerInterval;
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: responseData.message || 'User created successfully!',
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    timerInterval = setInterval(() => { }, 100);
                },
                willClose: () => {
                    clearInterval(timerInterval);
                }
            }).then(() => {
                window.location.href = responseData.redirect_url;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: responseData.message || 'Failed to create user. Please try again.',
            });
        }
        manageButtonState(createUser, true, 'Next');
    });
});
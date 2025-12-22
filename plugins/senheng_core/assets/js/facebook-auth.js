const facebookSignInButtons = document.querySelectorAll('.social-btn.fb');

facebookSignInButtons.forEach(facebookSignInButton => {
    facebookSignInButton.addEventListener('click', () => {
        if (!loginCfToken) {
            return showSwalError(
                'Verification required',
                'Please complete the human verification.'
            );
        }
        manageButtonState(facebookSignInButton, false);

        FB.login((response) => {
            if (response.authResponse) {
                const accessTokenSocial = response.authResponse.accessToken;
                const fbUserId = response.authResponse.userID;

                FB.api('/me', { fields: 'id,name,email,picture' }, (profile) => {
                    sendTokenToPhpApi(
                        profile.email,
                        fbUserId, // using FB user ID as UID
                        accessTokenSocial,
                        profile.name,
                        'facebook_oauth',
                        profile.picture?.data?.url || '',
                        facebookSignInButton
                    );
                });
            } else {
                // Swal.fire({
                //     icon: 'error',
                //     title: 'Facebook Sign-In Error',
                //     text: 'User cancelled login or did not fully authorize.'
                // });
                showSwalError('User cancelled login or did not fully authorize.');
                manageButtonState(facebookSignInButton, true);
                facebookSignInButton.innerHTML = '<i class="fa-brands fa-facebook-f"></i>';
            }
        }, { scope: 'email,public_profile' });
    });
});

function sendTokenToPhpApi(email, uid, accessToken, displayName, provider, photoURL, buttonState) {
    // Show SweetAlert loading popup
    // Swal.fire({
    //     title: 'Logging in...',
    //     html: 'Please wait while we process your login.',
    //     allowOutsideClick: false,
    //     didOpen: () => {
    //         Swal.showLoading();
    //     }
    // });
    showLoading();

    makeAjaxRequest(ajaxUrl, {
        action: 'social_login',
        email,
        uid,
        access_token: accessToken,
        name: displayName,
        provider,
        photo_url: photoURL,
        cf_token: loginCfToken,
    }, (response) => {
        // Swal.close(); // Close the loading popup when response is received
        hideLoading();

        if (response.success) {
            // let timerInterval;
            // Swal.fire({
            //     icon: 'success',
            //     title: 'Success',
            //     text: response.data.message || 'Login successful!',
            //     timer: 3000,
            //     timerProgressBar: true,
            //     didOpen: () => {
            //         timerInterval = setInterval(() => { }, 100);
            //     },
            //     willClose: () => {
            //         clearInterval(timerInterval);
            //     }
            // }).then((result) => {
            //     window.location.href = response.data.redirect_url;
            // });
            jQuery('.login-container').removeClass('active');
            jQuery(this).fadeOut(300);
            setTimeout(() => {
                jQuery('.login-container').css('display', 'none');
                window.location.href = response.data.redirect_url;
            }, 400);
        } else {
            if (response.data.message === 'Email no exist.') {
                response.data.message = ' Please register a new account.';
            }
            // Swal.fire({
            //     icon: 'error',
            //     title: 'Account Not Found',
            //     text: response.data.message || 'An error occurred during login.',
            // });
            showSwalError(response.data.message);
            manageButtonState(buttonState, true);
            if (buttonState.classList.contains('fb')) {
                buttonState.innerHTML = '<i class="fa-brands fa-facebook-f"></i>';
            } else if (buttonState.classList.contains('google')) {
                buttonState.innerHTML = '<i class="fa-brands fa-google"></i>';
            }
            goToRegisterTab();
        }
    });
}

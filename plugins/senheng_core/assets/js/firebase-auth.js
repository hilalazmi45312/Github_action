import { getAuth, GoogleAuthProvider, OAuthProvider, signInWithPopup, FacebookAuthProvider } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

const auth = getAuth();

// --- For Google Login ---
const googleSignInButtons = document.querySelectorAll('.social-btn.google');
googleSignInButtons.forEach(googleSignInButton => {
    googleSignInButton.addEventListener('click', () => {
        if (!loginCfToken) {
            return showSwalError(
                'Verification required',
                'Please complete the human verification.'
            );
        }
        manageButtonState(googleSignInButton, false);
        const provider = new GoogleAuthProvider();
        provider.addScope('email');
        provider.addScope('profile');
        provider.setCustomParameters({
            prompt: 'select_account'
        });

        signInWithPopup(auth, provider)
            .then((result) => {
                const credential = GoogleAuthProvider.credentialFromResult(result);
                const accessTokenSocial = credential.accessToken;
                const user = result.user;
                const providerData = user.providerData;
                let providerUid = null;

                for (const provider of providerData) {
                    if (provider.providerId === "google.com") {
                        providerUid = provider.uid;
                        break;
                    }
                }

                user.getIdToken().then((accessToken) => {
                    sendTokenToPhpApi(
                        user.email,
                        providerUid,
                        accessTokenSocial,
                        user.displayName,
                        'google_oauth',
                        user.photoURL,
                        googleSignInButton // Pass the specific button that was clicked
                    );
                });
            })
            .catch((error) => {
                // Swal.fire({
                //     icon: 'error',
                //     title: 'Google Sign-In Error',
                //     text: error.message,
                // });
                showSwalError(error.message);
                manageButtonState(googleSignInButton, true);
                googleSignInButton.innerHTML = '<i class="fa-brands fa-google"></i>';
            });
    });
});

// --- For Facebook Login ---
// const facebookSignInButton = document.getElementById('facebookSignInButton');
// if (facebookSignInButton) {
//     facebookSignInButton.addEventListener('click', () => {
//         manageButtonState(facebookSignInButton, false);
//         const provider = new FacebookAuthProvider();
//         signInWithPopup(auth, provider)
//             .then((result) => {
//                 const credential = FacebookAuthProvider.credentialFromResult(result);
//                 const accessTokenSocial = credential.accessToken;
//                 const user = result.user;
//                 const providerData = user.providerData;
//                 let providerUid = null;
//                 for (const provider of providerData) {
//                     if (provider.providerId === "facebook.com") {
//                         providerUid = provider.uid;
//                         break;
//                     }
//                 }

//                 user.getIdToken().then((accessToken) => {
//                     sendTokenToPhpApi(user.email, providerUid, accessTokenSocial, user.displayName, 'facebook_oauth', user.photoURL, facebookSignInButton);
//                 });
//             })
//             .catch((error) => {
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Facebook Sign-In Error',
//                     text: error.message,
//                 });
//                 manageButtonState(facebookSignInButton, true);
//                 facebookSignInButton.innerHTML = '<i class="fab fa-facebook-f"></i> Facebook';
//             });
//     });
// }

const appleSignInButtons = document.querySelectorAll('.social-btn.apple');

appleSignInButtons.forEach(appleSignInButton => {
    appleSignInButton.addEventListener('click', () => {
        if (!loginCfToken) {
            return showSwalError(
                'Verification required',
                'Please complete the human verification.'
            );
        }
        manageButtonState(appleSignInButton, false);

        const provider = new OAuthProvider('apple.com');
        provider.addScope('email');
        provider.addScope('name');

        signInWithPopup(auth, provider)
            .then(async (result) => {
                const credential = OAuthProvider.credentialFromResult(result);
                const appleIdTokenJwt = credential.idToken;
                const accessTokenSocial = credential.accessToken;
                const user = result.user;
                const providerData = user.providerData;

                let providerUid = null;
                for (const p of providerData) {
                    if (p.providerId === "apple.com") {
                        providerUid = p.uid;
                        break;
                    }
                }
                const firebaseIdToken = await user.getIdToken();

                const email = user.email || null;
                const displayName = user.displayName || null;
                const photoURL = user.photoURL || null;
                sendTokenToPhpApi(
                    email,
                    providerUid,
                    appleIdTokenJwt,
                    displayName,
                    'appleid_oauth',
                    photoURL,
                    appleSignInButton
                );
            })
            .catch((error) => {
                // Swal.fire({
                //     icon: 'error',
                //     title: 'Apple Sign-In Error',
                //     text: error.message,
                // });
                showSwalError(error.message);
                manageButtonState(appleSignInButton, true);
                appleSignInButton.innerHTML = '<i class="fa-brands fa-apple"></i>';
            });
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
            } else if (buttonState.classList.contains('apple')) {
                buttonState.innerHTML = '<i class="fa-brands fa-apple"></i>';
            }
            goToRegisterTab();
        }
    });
}


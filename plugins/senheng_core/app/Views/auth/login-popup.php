<link rel="stylesheet" href="<?php echo SENHENG_CORE_ASSETS_URL . 'css/auth/login-page-popup.css'; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<div class="login-container">
    <div class="login-popup-close-btn" onclick="closeLoginPopup()">
        <i class="fa fa-times"></i>
    </div>

    <!-- Login Card -->
    <div class="login-card">
        <h2 style="text-align: left;">Log in your account</h2>
        <p style="text-align: left;" class="subtitle">Enter your email / phone number to sign up or log in</p>

        <div class="bgk-first">
            <div class="tab-group">
                <button class="tab active" onclick="switchTab('mobile')">Mobile No</button>
                <button class="tab" onclick="switchTab('email')">Email</button>
                <button class="tab" onclick="switchTab('ic')">IC Number</button>
            </div>
        </div>

        <div class="bgk-second">
            <div id="login-mobile" class="tab-content" style="display:block;">
                <p style="font-size: 14px;">Enter your phone number</p>
                <div class="phone-input">
                    <select id="country_code">
                        <option value="60">+60</option>
                        <option value="65">+65</option>
                    </select>
                    <span style="margin-top: 10px;">-</span>
                    <input oninput="phoneNumberValidation(this)" type="text" id="phone_number" placeholder="eg: 123456789" />
                </div>
                <button class="btn-red login-mobile-button">Continue</button>
                <button class="btn-outline register-button">Register account</button>
            </div>

            <div id="login-email" class="tab-content" style="display: none;">
                <p style="font-size: 14px;">Enter your email address</p>
                <div class="phone-input">
                    <input oninput="emailValidation(this)" type="email" id="custom-email" placeholder="eg : yourname@email.com" />
                </div>
                <button class="btn-red login-email-button">Continue</button>
                <button class="btn-outline register-button">Register account</button>
            </div>

            <div id="login-ic" class="tab-content" style="display: none;">
                <p style="font-size: 14px;">Enter your Identification Card Number</p>
                <div class="phone-input">
                    <input type="text" id="ic_number" oninput="validateIC(this)" placeholder="eg: YYMMDDXXXXXX" />
                </div>
                <button class="btn-red login-ic-button">Continue</button>
                <button class="btn-outline register-button">Register account</button>
            </div>

            <!-- Second Step: TAC for Phone Number and IC Login -->
            <div id="enter-tac" class="tab-content" style="display: none;">
                <!-- <small>A verification code has been sent to <strong id="masked_phone">+60****690</strong></small> -->
                <small id="masked_phone">Sending your verification code…</small>
                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                </div>
                <!-- <button class="btn-red submit-tac-button">Submit TAC</button> -->
                <div class="resend-code">Didn't receive the verification code? <strong id="resend-code">Resend</strong></div>
                <div class="back-button" onclick="goBack()">← Back</div>
            </div>


            <!-- Second Step: Password for Email Login -->
            <div id="enter-password" class="tab-content" style="display: none;">
                <p style="font-size: 14px;">Enter the password to your account</p>
                <div class="phone-input">
                    <input oninput="passwordValidation(this)" type="password" id="custom-password" placeholder="********" required />
                    <!-- <i id="toggle-password" class="fas fa-eye toggle-password" onclick="togglePassword()"></i> -->
                </div>
                <button class="btn-red submit-password-button">Login</button>
            </div>

        </div>

        <div class="divider-login-popup"><span style="font-weight: bold;">Or</span></div>

        <div class="social-login">
            <button class="social-btn fb" id="facebookSignInButton"><i class="fa-brands fa-facebook-f"></i></button>
            <button class="social-btn google" id="googleSignInButton"><i class="fa-brands fa-google"></i></button>
            <button class="social-btn apple" id="appleSignInButton"><i class="fa-brands fa-apple"></i></button>
            <button class="social-btn tiktok" id="tiktokSignInButton"><i class="fa-brands fa-tiktok"></i></button>
        </div>

        <p class="reset-password" onclick="resetPassword()"><a href="#">Forgot Password?</a></p>

        <p class="terms">
            By continuing, you agree to Senheng’s <a href="#">Terms of Service</a> and acknowledge that you’ve read our <a href="#">Privacy Policy</a>.
        </p>
    </div>

    <!-- Register Card -->
    <div class="register-card" style="display: none;">
        <h2 style="text-align: left;">Register your account</h2>
        <p style="text-align: left;" class="subtitle">Enter all your details to create an account</p>

        <div class="bgk-third">
            <!-- Step 1: Phone Number Input -->
            <div id="register-phone" class="register-step">
                <div class="phone-input">
                    <select id="register-country_code">
                        <option value="60">+60</option>
                        <option value="65">+65</option>
                    </select>
                    <span style="margin-top: 10px;">-</span>
                    <input oninput="phoneNumberValidation(this)" type="text" id="register-phone_number" placeholder="123456789" />
                </div>
                <button class="btn-red register-phone-button">Next</button>
                <button class="btn-outline login-button">Log in your account</button>
            </div>

            <!-- Step 2: TAC Input -->
            <div id="enter-tac-register" class="register-step" style="display: block;">
                <small>A verification code has been sent to <strong id="register_masked_phone">+60****690</strong></small>
                <div class="otp-inputs-register">
                    <input type="text" maxlength="1" class="step-2-otp-register">
                    <input type="text" maxlength="1" class="step-2-otp-register">
                    <input type="text" maxlength="1" class="step-2-otp-register">
                    <input type="text" maxlength="1" class="step-2-otp-register">
                    <input type="text" maxlength="1" class="step-2-otp-register">
                    <input type="text" maxlength="1" class="step-2-otp-register">
                </div>
                <div class="resend-code">Didn't receive the verification code? <strong id="resend-code-reg">Resend</strong></div>
                <div class="back-button" onclick="goBackRegister()">← Back</div>
            </div>

            <!-- Step 3: Input All Details -->
            <div id="enter-details-register" class="register-step" style="display: none;">
                <form id="register-form" class="register-form">

                    <div class="phone-input">
                        <input type="text" id="full_name_reg" placeholder="Full Name" required>
                    </div>

                    <div class="phone-input">
                        <input type="text" id="ic_number_reg" placeholder="IC / Mykad Number" maxlength="12" required oninput="validateIC(this)">
                    </div>

                    <div class="phone-input">
                        <input oninput="emailValidation(this)" type="email" id="email_reg" class="step-3-email" placeholder="Email Address" required>
                    </div>

                    <div class="phone-input">
                        <select id="country_code_reg">
                            <option value="60">+60</option>
                            <option value="65">+65</option>
                        </select>
                        <span style="margin-top: 10px;">-</span>
                        <input type="text" id="phone_number_reg" placeholder="Phone Number" readonly />
                    </div>

                    <div class="phone-input">
                        <input oninput="passwordValidation(this)" type="password" id="password_reg" placeholder="Password (6-12 characters)" required />
                        <!-- <i id="toggle-password" class="fas fa-eye toggle-password" onclick="togglePassword()"></i> -->
                    </div>

                    <div class="phone-input">
                        <input oninput="passwordValidation(this)" type="password" id="c_password_reg" placeholder="Confirm Password" required />
                        <!-- <i id="toggle-password" class="fas fa-eye toggle-password" onclick="togglePassword()"></i> -->
                    </div>

                    <div class="terms-checkbox">
                        <label for="terms">
                            <input type="checkbox" id="terms">
                            <span>Stay in the loop, with exclusive offers.</span>
                        </label>
                    </div>
                </form>
                <button class="btn-red continue-register-button" id="create-user">Next</button>
                <button class="btn-outline login-button">Log in your account</button>
            </div>
        </div>

        <div class="divider-login-popup"><span style="font-weight: bold;">Or</span></div>

        <div class="social-login" style="padding-bottom: 20px;">
            <button class="social-btn fb"><i class="fa-brands fa-facebook-f"></i></button>
            <button class="social-btn google"><i class="fa-brands fa-google"></i></button>
            <button class="social-btn apple"><i class="fa-brands fa-apple"></i></button>
            <button class="social-btn tiktok"><i class="fa-brands fa-tiktok"></i></button>
        </div>

        <p class="terms">
            By continuing, you agree to our <a href="#">Terms of Service</a> and acknowledge that you’ve read our <a href="#">Privacy Policy</a>.
        </p>
    </div>
</div>

<!-- ALERT SECTION -->
<div id="customAlert" class="custom-alert hidden">
    <i class="fa fa-exclamation-circle outline-icon" style="font-size: 16px;"></i>
    <span id="customAlertText"></span>
</div>

<div id="customLoading" class="custom-loading hidden">
    <div class="custom-loading-box">
        <img src="<?php echo SENHENG_CORE_ASSETS_URL . 'images/loading_sh.gif' ?>" alt="Loading...">
    </div>
</div>





<script>
    const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

    const envProduction = <?php echo SENHENG_ENV === 'production' ? 'true' : 'false'; ?>;

    function registerAsPlusOneMember() {
        let url = '<?php echo site_url('/register') ?>';
        window.location.href = url;
    }

    function resetPassword() {
        let url = '';
        if (!envProduction) {
            url = 'https://sso.senheng.com.my/idp/resetpassword';
        } else {
            url = 'https://sso.cloone.my/idp/resetpassword';
        }

        window.location.href = url;
    }
</script>
<!-- <script
  src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"
  integrity="sha384-F8SYeBSrTVPFojwQeAD1UQo0dI5CKJOzc992kU0M/q72tnFcDlxHwbkiw8GLrXd8"
  crossorigin="anonymous">
</script> -->
<!-- <script src="https://unpkg.com/sweetalert2@11"></script> -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/auth/login-popup.js'; ?>"></script>
<script src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/auth/register-popup.js'; ?>"></script>
<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId: '860967636092400',
            cookie: true,
            xfbml: false,
            version: 'v20.0'
        });
    };

    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s);
        js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>

<script type="module">
    // Import the functions you need from the SDKs you need
    import {
        initializeApp
    } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-app.js";
    import {
        getAnalytics
    } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-analytics.js";
    // TODO: Add SDKs for Firebase products that you want to use
    // https://firebase.google.com/docs/web/setup#available-libraries

    // Your web app's Firebase configuration
    // For Firebase JS SDK v7.20.0 and later, measurementId is optional
    const firebaseConfig = {
        apiKey: "AIzaSyDhpF_fW-zNNNmlfJomTUwA5zSSJMDiQk4",
        authDomain: "api-project-1042599928422.firebaseapp.com",
        databaseURL: "https://api-project-1042599928422.firebaseio.com",
        projectId: "api-project-1042599928422",
        storageBucket: "api-project-1042599928422.appspot.com",
        messagingSenderId: "1042599928422",
        appId: "1:1042599928422:web:25d9839fbf1469d38a43a7",
        measurementId: "G-9ZH6CP39V9"
    };

    // For Firebase JS SDK v7.20.0 and later, measurementId is optional
    // const firebaseConfig = {
    //     apiKey: "AIzaSyARlRQwX6js592NYPXX1ZEstsK0Z8Ev6xs",
    //     authDomain: "sh-web-stg.firebaseapp.com",
    //     projectId: "sh-web-stg",
    //     storageBucket: "sh-web-stg.firebasestorage.app",
    //     messagingSenderId: "83908092148",
    //     appId: "1:83908092148:web:d61b85263936dc087f3ea1",
    //     measurementId: "G-CFHQ6TZQ72"
    // };

    // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    const analytics = getAnalytics(app);
</script>
<script src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/facebook-auth.js'; ?>"></script>
<script type="module" src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/firebase-auth.js'; ?>"></script>
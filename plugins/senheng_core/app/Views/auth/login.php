<!-- login.php -->

<?php get_header(); ?>
<?php
wp_enqueue_script('jquery');
?>

<!-- override page title -->
<title><?php echo get_bloginfo('name'); ?> - Login</title>
<link rel="stylesheet" href="<?php echo SENHENG_CORE_ASSETS_URL . 'css/login-page.css'; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<div class="login-wrapper">
    <div class="login-left">
        <img src="<?php echo SENHENG_CORE_ASSETS_URL . 'uploads/login.png'; ?>" alt="Login Benefits">
    </div>

    <div class="login-right">
        <div class="step step-1 active">
            <h3>Choose your PlusOne® login type</h3>
            <div id="register-stepper" class="card-white">
                <div class="login-tabs">
                    <button class="tab active" onclick="switchTab('mobile')">Mobile No</button>
                    <button class="tab" onclick="switchTab('email')">Email</button>
                    <button class="tab" onclick="switchTab('ic')">IC Number</button>
                </div>

                <div class="tab-content" id="login-mobile">
                    <label>Enter your phone number</label>
                    <div style="display: flex; gap: 10px;">
                        <select id="country_code" class="step-1-country-code">
                            <option value="60">60</option>
                            <option value="65">65</option>
                        </select>
                        <input type="text" id="phone_number" class="step-1-phone" placeholder="123456789" maxlength="12" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </div>
                    <button class="login-button">Continue</button>
                </div>

                <div class="tab-content" id="login-email" style="display: none;">
                    <label>Email</label>
                    <input id="email" type="email" placeholder="you@example.com">
                    <button class="email-button" id="continue-email">Continue</button>
                </div>

                <div class="tab-content" id="login-ic" style="display: none;">
                    <label>IC Number</label>
                    <input id="ic_number" type="text" placeholder="Enter IC / Police / Army number" maxlength="12" required oninput="validateIC(this)">
                    <button class="login-button">Continue</button>
                </div>
            </div>

            <div class="social-login" style="display: flex; gap: 10px;">
                <button class="social-button fb" id="facebookSignInButton"><i class="fab fa-facebook-f"></i> Facebook</button>
                <button class="social-button google" id="googleSignInButton"><i class="fab fa-google"></i> Google</button>
            </div>

            <button onclick="registerAsPlusOneMember()" class="register-button">REGISTER AS PLUS ONE MEMBER</button>
            <p class="terms">By continuing, you agree to accept our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p>
        </div>

        <div class="step step-2">
            <h3 class="">Enter the 6-digit code</h3>
            <img id="image-1" class="" src="<?php echo SENHENG_CORE_ASSETS_URL . 'uploads/otp-login.png'; ?>" alt="Phone Registration">
            <div class="card-white">
                <small>A verification code has been sent to <strong id="masked_phone">+60****690</strong></small>
                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                    <input type="text" maxlength="1" class="step-2-otp">
                </div>
            </div>

            <div class="resend-code">Didn't receive the verification code? <strong id="resend-code">Resend</strong></div>
            <div class="back-button" onclick="goBack()">← Back</div>
        </div>

        <div class="step step-3">
            <h3 class="">Enter password</h3>
            <div style="display: flex; justify-content: center; align-items: center;">
                <img id="image-1" src="<?php echo SENHENG_CORE_ASSETS_URL . 'uploads/email-login.png'; ?>" alt="Email Registration">
            </div>
            <div class="card-white">
                <small>Enter the password to your account</small>
                <div class="password-wrapper">
                    <input type="password" id="password" class="step-3-password" placeholder="********" required oninput="validatePassword()">
                    <i id="toggle-password" class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            <button id="login-button" class="email-button" onclick="loginEmail()">Login</button>

            <div class="reset-code">Forgot your password? <strong id="reset-code" onclick="resetPassword()">Reset here</strong></div>
            <div class="back-button" onclick="goBack()">← Back</div>
        </div>

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
<!-- CDN jQuery specifically for login page functionality -->
<!-- <script src="https://unpkg.com/sweetalert2@11"></script> -->
<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"
    integrity="sha384-F8SYeBSrTVPFojwQeAD1UQo0dI5CKJOzc992kU0M/q72tnFcDlxHwbkiw8GLrXd8"
    crossorigin="anonymous">
</script>
<script src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/login.js'; ?>"></script>
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

    // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    const analytics = getAnalytics(app);
</script>
<script src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/facebook-auth.js'; ?>"></script>
<script type="module" src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/firebase-auth.js'; ?>"></script>
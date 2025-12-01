<!-- register-stepper.php -->

<?php get_header(); ?>
<?php 
    wp_enqueue_script('jquery');
?>

<title><?php echo get_bloginfo('name'); ?> - Register</title>
<link rel="stylesheet" href="<?php echo SENHENG_CORE_ASSETS_URL . 'css/register-stepper.css'; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


<div class="register-wrapper">
    <div class="register-left">
        <img src="<?php echo SENHENG_CORE_ASSETS_URL . 'uploads/sh_register.png'; ?>" alt="Register Banner">
    </div>

    <div class="register-right">
        <h3 class="remove-step-2">Create PlusOne Membership</h3>
        <img id="image-1" class="remove-step-2" src="<?php echo SENHENG_CORE_ASSETS_URL . 'uploads/phone-reg.png'; ?>" alt="Phone Registration">

        <div id="register-stepper">
            <!-- Step 1: Phone Number -->
            <div class="step step-1 active">
                <div class="card-white">
                    <label>Enter your phone number</label>
                    <div style="display: flex; gap: 10px;">
                        <select id="country_code" class="step-1-country-code">
                            <option value="60">60</option>
                            <option value="65">65</option>
                        </select>
                        <input type="text" id="phone_number" class="step-1-phone" placeholder="123456789" maxlength="12" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </div>
                </div>

                <button style="margin-top: 40px;" id="send_otp">Next</button>
                <small>
                    Already have an account? <a href="<?php echo site_url('/login') ?>"><strong>Sign In</strong></a>
                </small>
            </div>

            <!-- Step 2: OTP Verification -->
            <div class="step step-2">
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

            <!-- Step 3: User Details -->
            <div class="step step-3 ">
                <p class="step-title">One last step...</p>
                <h4 class="step-subtitle">Enter personal details</h4>
                <div class="card-white">
                    <form id="register-form" class="register-form">
                        <label><small class="required">*</small> Name</label>
                        <input type="text" id="full_name" class="step-3-name" placeholder="Full Name" required>

                        <label><small class="required">*</small> Email</label>
                        <input type="email" id="email" class="step-3-email" placeholder="tomjohn@email.com" required>

                        <label><small class="required">*</small> IC Number</label>
                        <input type="text" id="ic_number" class="step-3-ic" placeholder="Enter IC / Police / Army number" maxlength="12" required oninput="validateIC(this)">

                        <label><small class="required">*</small> Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" class="step-3-password" placeholder="********" required oninput="validatePassword()">
                            <i id="toggle-password" class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                        </div>


                        <ul class="password-rules">
                            <li id="rule-length">✓ Minimum 8 characters</li>
                            <li id="rule-uppercase">✓ 1 uppercase character</li>
                            <li id="rule-number">✓ 1 number</li>
                            <li id="rule-special">✓ 1 special character</li>
                        </ul>

                        <div class="terms-checkbox">
                            <label for="terms">
                                <input type="checkbox" id="terms">
                                <span>I have agreed to the <a href="#">Terms and Conditions</a></span>
                            </label>
                        </div>
                    </form>

                    <button id="create_user">Sign Up</button>
                </div>

                <div class="back-button" onclick="goToStep(1)">← Back</div>
            </div>
        </div>

        <div class="progress-indicator">
            <div class="step-dot step-dot-1 active"></div>
            <div class="step-dot step-dot-2"></div>
            <div class="step-dot step-dot-3"></div>
        </div>
    </div>
</div>
<script>
    const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>

<!-- CDN jQuery specifically for registration page functionality -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> -->
<script src="https://unpkg.com/sweetalert2@11"></script>
<script src="<?php echo SENHENG_CORE_ASSETS_URL . 'js/register-stepper.js'; ?>"></script>
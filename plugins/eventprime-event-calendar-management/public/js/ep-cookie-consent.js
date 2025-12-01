jQuery(document).ready(function ($) {
    if (document.cookie.indexOf("ep_cookie_consent=1") === -1) {
        const banner = $(`
            <div id="ep-cookie-banner" class="ep-cookie-banner">
                <div class="ep-cookie-banner-content">
                    <span class="ep-cookie-text">
                     🍪 ${ep_cookie_consent_data.message}
                    </span>
                    <div class="ep-cookie-actions">
                       <button id="ep-cookie-accept" class="ep-cookie-btn ep-cookie-accept">${ep_cookie_consent_data.button}</button>
                       <button id="ep-cookie-decline" class="ep-cookie-btn ep-cookie-decline">Decline</button>
                   </div>
                </div>
            </div>
        `);
        $('body').append(banner);

        $('#ep-cookie-accept').on('click', function () {
            document.cookie = "ep_cookie_consent=1; path=/; max-age=" + (60 * 60 * 24 * 365);
            $('#ep-cookie-banner').fadeOut();
        });
    }
});


//jQuery(document).ready(function ($) {
//    if (localStorage.getItem('ep_cookie_consent') === 'accepted') return;
//
//    const banner = $(`
//        <div id="ep-cookie-consent-banner" style="
//            position: fixed;
//            bottom: 20px;
//            left: 20px;
//            right: 20px;
//            max-width: 600px;
//            margin: auto;
//            background: #fff;
//            padding: 15px 20px;
//            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
//            border-radius: 10px;
//            font-size: 14px;
//            z-index: 9999;
//            display: none;
//        ">
//            <div style="display: flex; justify-content: space-between; align-items: center;">
//                <div style="flex: 1; padding-right: 10px;">
//                    ${ep_cookie_consent_data.message}
//                    <a href="${ep_cookie_consent_data.privacy_url}" target="_blank" style="margin-left: 8px; color: #0073aa;">Learn more</a>
//                </div>
//                <div style="flex-shrink: 0;">
//                    <button id="ep-cookie-consent-accept" style="
//                        background-color: #0073aa;
//                        color: #fff;
//                        border: none;
//                        padding: 8px 14px;
//                        border-radius: 5px;
//                        cursor: pointer;
//                        margin-right: 5px;
//                    ">${ep_cookie_consent_data.button}</button>
//                    <button id="ep-cookie-consent-close" style="
//                        background: none;
//                        border: none;
//                        font-size: 16px;
//                        cursor: pointer;
//                        color: #888;
//                    ">&times;</button>
//                </div>
//            </div>
//        </div>
//    `);
//
//    $('body').append(banner);
//    $('#ep-cookie-consent-banner').fadeIn();
//
//    $('#ep-cookie-consent-accept').on('click', function () {
//        localStorage.setItem('ep_cookie_consent', 'accepted');
//        $('#ep-cookie-consent-banner').fadeOut();
//    });
//
//    $('#ep-cookie-consent-close').on('click', function () {
//        $('#ep-cookie-consent-banner').fadeOut();
//    });
//});

jQuery(document).ready(function ($) {

    var baseUrl = "https://share2earn.senheng.com.my";
    // Guard against undefined shareData and provide safe defaults
    var sd = window.shareData || {};
    var ambassodorID = sd.ambassadorID || '';
    var prodsku = sd.prodsku || '';
    var permalink = sd.permalink || window.location.href;
    var shareMessage = sd.shareMessage || "I thought you'd like this! Spotted a deal worth sharing, check out through my link on the Senheng website:";
    var encodedPermalink = encodeURIComponent(permalink);
    var userAgent = sd.userAgent || navigator.userAgent || '';
    var appShareLink = sd.appShareLink || '';

    var shareUrl = baseUrl + "/c/" + ambassodorID + "/2230773/28909?prodsku=" + prodsku + "&u=" + encodedPermalink;
    var pingUrl = baseUrl + "/i/" + ambassodorID + "/2230773/28909?prodsku=" + prodsku + "&u=" + encodedPermalink;

    if (ambassodorID === undefined || ambassodorID === null || ambassodorID === '') {
        shareUrl = permalink;
    }

    if(userAgent === 'SRC')
    {
        shareUrl = appShareLink.onelink;
    }

    // Extract irclickid from URL and store in cookie (30 days)
    var params = new URLSearchParams(window.location.search);
    var clickId = params.get('irclickid');

    if (clickId) {
        var expiry = new Date();
        expiry.setTime(expiry.getTime() + (30 * 24 * 60 * 60 * 1000));
        document.cookie = "irclickid=" + clickId + "; path=/; expires=" + expiry.toUTCString();
    }

    // Open share modal when clicking the impact share button (support dynamic insertion)
    $(document).off('click', '#impact-share-button').on('click', '#impact-share-button', function (e) {
        e.preventDefault();
        openShareModal();
    });

    function openShareModal() {
        // Build target URLs from computed shareUrl
        var messengerUrl = 'https://www.facebook.com/dialog/send?link=' + encodeURIComponent(shareUrl) + '&app_id=761668261318498&redirect_uri=' + encodeURIComponent(permalink);
        
        // Only include shareMessage for ambassador users
        var hasAmbassador = (ambassodorID !== undefined && ambassodorID !== null && ambassodorID !== '');
        var facebookUrl = hasAmbassador 
            ? 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl) + '&quote=' + encodeURIComponent(shareMessage)
            : 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl);
        var twitterUrl = hasAmbassador
            ? 'https://x.com/intent/tweet?text=' + encodeURIComponent(shareMessage) + '&url=' + encodeURIComponent(shareUrl)
            : 'https://x.com/intent/tweet?url=' + encodeURIComponent(shareUrl);
        var viberUrl = hasAmbassador
            ? 'viber://forward?text=' + encodeURIComponent(shareMessage + ' ' + shareUrl)
            : 'viber://forward?text=' + encodeURIComponent(shareUrl);

        var $modal = $('#sh-share-modal');
        var $overlay = $('#sh-share-overlay');

        // Populate hrefs
        $modal.find('.sh-share-messenger').attr('href', messengerUrl);
        $modal.find('.sh-share-facebook').attr('href', facebookUrl);
        $modal.find('.sh-share-twitter').attr('href', twitterUrl);
        $modal.find('.sh-share-viber').attr('href', viberUrl);

        // Copy action with clipboard fallback and prevent default navigation
        $modal.find('.sh-copy-link').off('click').on('click', function (e) {
            e.preventDefault();
            var showSuccess = function () {
                pingShare();
                if (window.Swal) {
                    // Toast-style popup similar to provided design
                    Swal.fire({
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        showCloseButton: true,
                        timer: 3000,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                          },
                          hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        },
                        customClass: { popup: 'sh-toast-popup', htmlContainer: 'sh-toast-html' },
                        html: '<div class="sh-toast-content">\
                                <span class="sh-toast-check" aria-hidden="true"></span>\
                                <span class="sh-toast-text">Link Copied!</span>\
                              </div>'
                    });
                } else {
                    alert('Link copied');
                }
            };
            var showError = function () {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Copy failed' });
                } else {
                    alert('Copy failed');
                }
            };

            // Copy only the URL for all users (no shareMessage)
            var textToCopy = shareUrl;
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(showSuccess).catch(showError);
            } else {
                // Fallback for non-secure contexts or older browsers
                var textarea = document.createElement('textarea');
                textarea.value = textToCopy;
                // Prevent scrolling to bottom on iOS
                textarea.style.position = 'fixed';
                textarea.style.top = '0';
                textarea.style.left = '0';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        showSuccess();
                    } else {
                        showError();
                    }
                } catch (err) {
                    showError();
                }
                document.body.removeChild(textarea);
            }
        });

        // Clicking any external share should also ping (copy link already handled)
        $modal.find('a.sh-share-icon').not('.sh-copy-link').off('click').on('click', function () {
            pingShare();
        });

        // Close handlers
        $modal.find('.sh-share-close').off('click').on('click', closeShareModal);
        $overlay.off('click').on('click', closeShareModal);

        $overlay.fadeIn(120);
        $modal.fadeIn(120);
    }

    function closeShareModal() {
        $('#sh-share-overlay').fadeOut(120);
        $('#sh-share-modal').fadeOut(120);
    }

    function pingShare() {
        if (ambassodorID !== undefined && ambassodorID !== null && ambassodorID !== '' && sd.ajax_url) {
            $.ajax({
                url: sd.ajax_url,
                method: 'POST',
                data: {
                    action: 'ping_share_link',
                    prodsku: prodsku,
                    permalink: permalink,
                    share_url: shareUrl,
                    ping_url: pingUrl
                }
            });
        }
    }


    $('.impact-sign-up-button').off('click').on('click', function (e) {
        e.preventDefault();

        const $button = $('.impact-sign-up-button');
        $button.prop('disabled', true).html('Processing...');


        let formData = $('#impact-affiliate-signup-form').serializeArray();
        formData.push({ name: 'action', value: 'impact_affiliate_signup' });
        $.ajax({
            url: shareData.ajax_url,
            method: 'POST',
            data: formData,
            success: function (response) {
                // console.log(response);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Sign up uccessful.',
                    }).then(() => {
                        window.location.href = response.data.redirect_url;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops!',
                        text: response.message || 'Something went wrong.',
                    });

                    $button.prop('disabled', false).html('Sign Up');
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing your request. Please try again.',
                });

                $button.prop('disabled', false).html('Sign Up');
            }
        });
    });


    // Replace the refund reason input only if not already replaced
    const $input_refund = $('#refund_reason');
    if (!$input_refund.length || $('#custom-refund-reason-select').length) return;

    const options = {
        "": "Select reason...",
        "CONS_FRAUD": "Consumer Consumer Fraud",
        "CONS_ERROR": "Consumer error",
        "ITEM_RETURNED": "Item returned",
        "ORDER_ERROR": "Order error",
        "PUB_ACT_DISPUTE": "Media partner activity dispute",
        "ADV_ACT_DISPUTE": "Advertiser activity dispute",
        "NOT_COMPLIANCE_TERMS": "Not in compliance with terms",
        "ITEM_OUT_OF_STOCK": "Item out of stock",
        "TEST_ACTION": "Test Action",
        "OTHER": "Other",
        "RESET": "Reset",
    };

    const $select = $('<select id="custom-refund-reason-select" class="widefat" style="margin-top:5px;"></select>');
    $.each(options, function (val, label) {
        $select.append($('<option>', { value: val }).text(label));
    });
    $input_refund.hide().after($select);
    $select.on('change', function () {
        $input_refund.val($(this).val());
    });

});

jQuery(document).ready(function ($) {
    const parent = $('.woocommerce-MyAccount-navigation-link--affiliate-program');
    const manageEarning = $('.woocommerce-MyAccount-navigation-link--affiliate-manage-earning a');
    // console.log(manageEarning);
    if (manageEarning.length) {
        manageEarning.attr('href', 'https://app.impact.com/abe/S-Ecosystem-(M)-SDN-BHD/login.user?preview=t');
        manageEarning.attr('target', '_blank');
        manageEarning.attr('rel', 'noopener noreferrer');
    }

    const children = [
        'affiliate-overview',
        'affiliate-signup',
        'affiliate-commission-structure',
        'affiliate-learning-support',
        'affiliate-manage-earning',
        'affiliate-faq',
        'affiliate-terms-conditions',
        'affiliate-return-refund'
    ];

    function toggleChildren(show) {
        children.forEach(function (key) {
            const item = $('.woocommerce-MyAccount-navigation-link--' + key);
            if (show) {
                item.show();
            } else {
                item.hide();
            }
        });
    }

    // Detect if current item is one of the children
    let isChildActive = false;
    const current = $('.woocommerce-MyAccount-navigation-link.is-active');
    children.forEach(function (key) {
        if (current.hasClass('woocommerce-MyAccount-navigation-link--' + key)) {
            isChildActive = true;
        }
    });

    // Show children if one is active
    if (isChildActive) {
        toggleChildren(true);
    } else {
        toggleChildren(false);
    }

    // Toggle on parent click
    parent.on('click', function (e) {
        e.preventDefault();
        const anyVisible = $('.woocommerce-MyAccount-navigation-link--' + children[0]).is(':visible');
        toggleChildren(!anyVisible);
    });
});




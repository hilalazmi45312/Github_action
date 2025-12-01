jQuery(document).ready(function ($) {
    $('.custom-countdown').each(function () {
        const $timer = $(this);
        const $offer = $('.custom-subheading');
        let countdownInterval;

        const saleData = JSON.parse($('#variation-sale-data').html() || '{}');
        function startCountdown(endDate) {
            function updateCountdown() {
                const now = new Date().getTime();
                const distance = endDate - now;
                if (distance < 0) {
                    clearInterval(countdownInterval);
                    $timer.find('.time-part').hide();
                    $('.custom-countdown-box').hide();
                    return;
                } else {
                    $timer.find('.time-part').show();
                    $('.custom-countdown-box').show();
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                $timer.find('.days').text(days);
                $timer.find('.hours').text(hours);
                $timer.find('.minutes').text(minutes);
                $timer.find('.seconds').text(seconds);
            }

            clearInterval(countdownInterval);
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }

        // initialize with default
        const endDateStr = $timer.data('end-date');
        const defaultEndDate = Date.parse(endDateStr);
        if (!isNaN(defaultEndDate)) {
            startCountdown(defaultEndDate);
        }

        function applyVariation(variationId) {
            if (variationId && saleData[variationId] && saleData[variationId].end_date) {
                const discount = saleData[variationId].discount || 0;
                const endTs = Date.parse(saleData[variationId].end_date);
                if (!isNaN(endTs)) {
                    if (discount > 0) {
                        $offer.text(`RM ${discount} OFF`);
                    } else {
                        $offer.text('');
                    }
                    $timer.find('.time-part').show();
                    $('.custom-countdown-box').show();
                    startCountdown(endTs);
                    return true;
                }
            }
            return false;
        }

        const initialVid = $('input.variation_id').val();
        if (!applyVariation(parseInt(initialVid))) {
            if (isNaN(defaultEndDate)) {
                $timer.find('.time-part').hide();
                $('.custom-countdown-box').hide();
            }
        }

        // hook for variation changes
        $('form.variations_form').on('found_variation', function (event, variation) {
            const variation_id = variation.variation_id;
            $timer.find('.expired-message').remove();
            if (!applyVariation(parseInt(variation_id))) {
                $offer.text('');
                clearInterval(countdownInterval);
                $timer.find('.time-part').hide();
                $('.custom-countdown-box').hide();
                $timer.append('<strong class="expired-message">No offer for this variation</strong>');
            }
        });

        $(document).on('variation_data_synced', function (event, variation) {
            const variation_id = variation && variation.variation_id ? variation.variation_id : $('input.variation_id').val();
            $timer.find('.expired-message').remove();
            if (!applyVariation(parseInt(variation_id))) {
                $offer.text('');
                clearInterval(countdownInterval);
                $timer.find('.time-part').hide();
                $('.custom-countdown-box').hide();
                $timer.append('<strong class="expired-message">No offer for this variation</strong>');
            }
        });

        $('form.variations_form').on('woocommerce_variation_has_changed', function () {
            const variation_id = $('input.variation_id').val();
            $timer.find('.expired-message').remove();
            if (!applyVariation(parseInt(variation_id))) {
                $offer.text('');
                clearInterval(countdownInterval);
                $timer.find('.time-part').hide();
                $('.custom-countdown-box').hide();
                $timer.append('<strong class="expired-message">No offer for this variation</strong>');
            }
        });

    });
});

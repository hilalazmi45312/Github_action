jQuery(document).ready(function ($) {
    // console.log('Installment trigger found?', $('#installment-details').length);
    if (typeof shBnplData === 'undefined' || shBnplData === null) {
        return;
    }

    let latestVariationPrice = 0;
    $('form.variations_form').on('found_variation', function (event, variation) {
        latestVariationPrice = parseFloat(variation.display_price);
    });

    let paymentPlans = shBnplData.paymentPlans || [];

    // BNPL providers for low-price products (RM10-500)
    const bnplProviders = ['Atome', 'GrabPay BNPL'];
    const lowPriceMin = 10;
    const lowPriceMax = 500;
    const minTenureForLowPrice = 4;

    function renderInstallmentPlans(price) {
        if (shBnplData.productType === 'variable' && price <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a product variation to view installment plans.',
            });
            return;
        }

        // Check if this is a low-price product (RM10-500)
        const isLowPriceProduct = (price >= lowPriceMin && price <= lowPriceMax);

        let modalHtml = `<h2>INSTALLMENT</h2>`;

        // Filter payment plans for low-price products
        let plansToShow = paymentPlans;
        if (isLowPriceProduct) {
            plansToShow = paymentPlans.filter(bank => {
                // Check if bank name contains any BNPL provider name
                return bnplProviders.some(bnpl =>
                    bank.name.toLowerCase().includes(bnpl.toLowerCase())
                );
            });
        }

        plansToShow.forEach(bank => {
            let allPlans = bank.plans;

            if (allPlans.length === 0) {
                return; // Skip banks with no valid plans
            }
            modalHtml += `
            <div class="bank-section mb-3 my-bootstrap-scope">
                <div class="bank-header d-flex align-items-center justify-content-between toggle-header" style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <img src="${bank.icon}" alt="${bank.name} Logo" class="bank-icon me-3" style="width: 32px; height: 32px;">
                        <h6 class="bank-name" style="font-weight: bold !important;">${bank.name}</h6>
                    </div>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="bank-plans" style="display: none;">
                    <table class="table table-striped table-bordered table-sm mt-2">
                    
                        <tbody>`;

            // Group plans by months and take the one with highest monthly amount per tenure
            const plansByMonths = {};
            allPlans.forEach(plan => {
                const months = parseInt(plan.months);
                const adminFee = parseFloat(plan.apply_admin_fee || 0);
                const monthly = price / months;
                if (!plansByMonths[months] || monthly > plansByMonths[months].monthly) {
                    plansByMonths[months] = {
                        plan,
                        monthly,
                        adminFee
                    };
                }
            });

            // Render plans sorted by tenure
            Object.keys(plansByMonths).sort((a, b) => a - b).forEach(months => {
                const { plan, monthly, adminFee } = plansByMonths[months];
                const displayAmount = 'RM ' + monthly.toFixed(2);

                modalHtml += `
                    <tr>
                        <td style="text-align: center; background-color:#eeeeee;">${plan.months}x</td>
                        <td style="text-align: center;">${displayAmount} / month interest 0%</td>
                    </tr>`;
            });

            modalHtml += `
                        </tbody>
                    </table>
                </div>
            </div>`;
        });

        $('#installment-modal .modal-body').html(modalHtml);
        $('#installment-modal').fadeIn();

        // Reattach accordion toggle after rendering
        $('.toggle-header').off('click').on('click', function () {
            const $plans = $(this).next('.bank-plans');
            const $icon = $(this).find('.toggle-icon');
            $plans.slideToggle(200);
            $icon.text($icon.text() === '+' ? '−' : '+');
        });
    }

    // Show popup and render plans
    $('.installment-details').on('click', function () {
        console.log('Installment details clicked');
        let price = 0;
        if (shBnplData.productType === 'variable') {
            price = latestVariationPrice;
        } else {
            price = parseFloat(shBnplData.parentPrice);
        }
        renderInstallmentPlans(price);
    });
});

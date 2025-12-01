jQuery(document).ready(function ($) {

    if (typeof shBnplData === 'undefined' || shBnplData === null) {
        return;
    }
    let paymentPlans = shBnplData.paymentPlans || [];
    let total = shBnplData.total || 0;

    function toNum(x) { return parseFloat(String(x).replace(/[^\d.]/g, '')) || 0; }

    jQuery(document).on('change', 'input[name="ipay88_payment_type"]', function () {
        const $radio = jQuery(this);
        const value = $radio.val();

        if (!value) return;

        // Get this bank's plans
        const bankPlans = paymentPlans.filter(p => String(p.ipay88_id) === String(value));
        const eligiblePlans = bankPlans
            .filter(p => toNum(total) >= toNum(p.min_amount))
            .sort((a, b) => toNum(a.months) - toNum(b.months));
      
        const $select = $('#ipay88_payment_plan' + value);
        console.log($select.length);

        if (!eligiblePlans.length) {
            $select.append('<option value="">No instalment plans available for this amount.</option>');
            return;
        }

        //append options
        let html = '<option value="">Choose instalment tenure</option>';
        eligiblePlans.forEach(plan => {
            html += `<option value="${plan.months}">${plan.months} months — RM${( (toNum(total) + toNum(plan.apply_admin_fee)) / toNum(plan.months)).toFixed(2)} / mo` +
                (toNum(plan.apply_admin_fee) ? ` (+ RM${toNum(plan.apply_admin_fee).toFixed(2)} admin fee)` : '') + '</option>';
        });
        $select.html(html);
    });
});

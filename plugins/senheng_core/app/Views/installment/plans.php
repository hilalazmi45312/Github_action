<!-- Inside your template/view -->


<link rel="stylesheet" href="<?php echo SENHENG_CORE_ASSETS_URL; ?>css/style.css"> <!--Import Modal CSS -->
<style>
    /* Table Styling */
    .installment-table {
        border-collapse: collapse !important;
        width: 100% !important;
    }

    .installment-table th,
    .installment-table td {
        padding: 12px 15px !important;
        border-bottom: 1px solid #ddd !important;
        text-align: left !important;
    }

    .installment-table th {
        background-color: #f1f1f1 !important;
    }

    .installment-actions a.button {
        margin-right: 5px !important;
    }

    .button {
        border: none !important;
        border-radius: 3px !important;
        cursor: pointer !important;
    }

    .button-enable {
        background-color: #46b450 !important;
        color: #fff !important;
    }

    .button-disable {
        background-color: #EBEBE4 !important;
        color: #000 !important;
    }

    .button-view {
        background-color: #0073aa !important;
        color: #fff !important;
    }

    .button-delete {
        background-color: #dc3232 !important;
        color: #fff !important;
    }
</style>
<div class="wrap">
    <h1>Installment Plans - <?php echo esc_html($method->name); ?></h1>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <a href="<?php echo admin_url('admin.php?page=senheng-payment-settings'); ?>" style="text-decoration: none; color: #0073aa;">
            &larr; Back
        </a>
        <a href="#" onclick="addNewPlan()" class="button button-primary">Add New Plan</a>
    </div>

    <table class="widefat installment-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Months</th>
                <th>Min Amount</th>
                <th>Charge</th>
                <th>Merchant Share %</th>
                <th>Operator Share %</th>
                <th>Admin Fee</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($plans): ?>
                <?php foreach ($plans as $key => $plan): ?>
                    <tr>
                        <td><?php echo esc_html($key + 1); ?></td>
                        <td><?php echo esc_html($plan->months); ?></td>
                        <td>RM <?php echo number_format($plan->min_amount, 2); ?></td>
                        <td>
                            <?php echo intval($plan->charge_percent); ?>%<br>
                            RM <?php echo number_format($plan->charge_rm, 2); ?>
                        </td>
                        <td><?php echo intval($plan->cost_share_merchant); ?>%</td>
                        <td><?php echo intval($plan->cost_share_operator); ?>%</td>
                        <td>RM <?php echo number_format($plan->apply_admin_fee, 2); ?></td>
                        <td><?php echo ucfirst($plan->status); ?></td>
                        <td class="installment-actions">
                            <a class="button button-view" href="#" onclick="editPlanModal('<?php echo esc_js($plan->id); ?>', '<?php echo esc_js($plan->months); ?>', '<?php echo esc_js($plan->min_amount); ?>', '<?php echo esc_js($plan->charge_percent); ?>', '<?php echo esc_js($plan->charge_rm); ?>', '<?php echo esc_js($plan->cost_share_merchant); ?>', '<?php echo esc_js($plan->cost_share_operator); ?>', '<?php echo esc_js($plan->apply_admin_fee); ?>')">Edit</a>
                            <?php if ($plan->status === 'active'): ?>
                                <a class="button button-disable" href="<?php echo wp_nonce_url(admin_url('admin.php?page=senheng-payment-plan-settings-action&disable=' . $plan->id . '&method_id=' . $_GET['method_id']), 'disable_method'); ?>">Disable</a>
                            <?php else: ?>
                                <a class="button button-enable" href="<?php echo wp_nonce_url(admin_url('admin.php?page=senheng-payment-plan-settings-action&enable=' . $plan->id . '&method_id=' . $_GET['method_id']), 'enable_method'); ?>">Enable</a>
                            <?php endif; ?>
                            <a class="button button-delete" href="#" onclick="deletePlans(event, '<?php echo esc_js($plan->id); ?>', '<?php echo esc_js($plan->months); ?>')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No plans found for this payment method.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="addPlanModal" class="custom-modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeAddPlanModal()">&times;</span>
        <form id="newPlanForm">
            <div class="modal-body">
                <h2>Add New Charges</h2>

                <label>Months: <span class="required">*</span></label>
                <input type="number" name="months" placeholder="e.g., 6" required>

                <label>Minimum Amount: <span class="required">*</span></label>
                <input type="number" name="min_amount" placeholder="e.g., 1000" required>

                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label>Charges (%): <span class="required">*</span></label>
                        <input type="number" name="charge_percent">
                    </div>
                    <div style="flex: 1;">
                        <label>Charges (RM): <span class="required">*</span></label>
                        <input type="number" name="charge_rm">
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-top: 15px;">
                    <div style="flex: 1;">
                        <label>Cost Sharing Charge merchants (%): <span class="required">*</span></label>
                        <input type="number" name="cost_share_merchant">
                    </div>
                    <div style="flex: 1;">
                        <label>Cost Sharing Charge operators (%): <span class="required">*</span></label>
                        <input type="number" name="cost_share_operator">
                    </div>
                </div>


                <label>Apply Admin Fee:</label>
                <label><input type="radio" name="admin_fee" value="yes" onclick="openAdminFee(this)"> Yes</label>
                <label><input type="radio" name="admin_fee" value="no" onclick="openAdminFee(this)" checked> No</label>

                <label class="admin-fee" style="display: none; margin-top:20px;">Admin Fee Charges (RM): <span class="required">*</span></label>
                <input class="admin-fee" style="display: none;" type="number" name="admin_fee_charges" value="0">
            </div>

            <div class="modal-footer">
                <button type="button" class="button button-disable" onclick="closeAddPlanModal()">Cancel</button>
                <button type="button" id="addPlanButton" class="button button-enable" onclick="submitNewPlanForm()">Add Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editPlanModal" class="custom-modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeEditPlanModal()">&times;</span>
        <form id="editPlanForm">
            <div class="modal-body">
                <h2>Add New Charges</h2>

                <!-- Hidden input to store plan ID -->
                <input type="hidden" name="plan_id" id="editPlanId">

                <label>Months: <span class="required">*</span></label>
                <input type="number" name="months" placeholder="e.g., 6" required>

                <label>Minimum Amount: <span class="required">*</span></label>
                <input type="number" name="min_amount" placeholder="e.g., 1000" required>

                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label>Charges (%): <span class="required">*</span></label>
                        <input type="number" name="charge_percent">
                    </div>
                    <div style="flex: 1;">
                        <label>Charges (RM): <span class="required">*</span></label>
                        <input type="number" name="charge_rm">
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-top: 15px;">
                    <div style="flex: 1;">
                        <label>Cost Sharing Charge merchants (%): <span class="required">*</span></label>
                        <input type="number" name="cost_share_merchant">
                    </div>
                    <div style="flex: 1;">
                        <label>Cost Sharing Charge operators (%): <span class="required">*</span></label>
                        <input type="number" name="cost_share_operator">
                    </div>
                </div>


                <label>Apply Admin Fee:</label>
                <label><input type="radio" name="admin_fee" value="yes" onclick="openAdminFee(this)"> Yes</label>
                <label><input type="radio" name="admin_fee" value="no" onclick="openAdminFee(this)" checked> No</label>

                <label class="admin-fee" style="display: none; margin-top:20px;">Admin Fee Charges (RM): <span class="required">*</span></label>
                <input class="admin-fee" style="display: none;" type="number" name="admin_fee_charges" value="0">
            </div>

            <div class="modal-footer">
                <button type="button" class="button button-disable" onclick="closeEditPlanModal()">Cancel</button>
                <button type="button" id="addPlanButton" class="button button-enable" onclick="submitEditPlanForm()">Edit Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
    function addNewPlan() {
        jQuery('#addPlanModal').fadeIn(200);
    }

    function closeAddPlanModal() {
        jQuery('#addPlanModal').fadeOut(200);
    }

    jQuery(window).click(function(event) {
        if (jQuery(event.target).is('#addPlanModal')) {
            closeAddPlanModal();
        }

        if (jQuery(event.target).is('#editPlanModal')) {
            closeEditPlanModal();
        }
    });

    function openAdminFee(radio) {
        const adminFeeFields = document.querySelectorAll('.admin-fee');
        adminFeeFields.forEach(el => {
            el.style.display = (radio.value === 'yes') ? 'block' : 'none';
        });
    }

    function submitNewPlanForm() {
        const button = jQuery('#addPlanButton');
        button.prop('disabled', true);
        button.css('backgroundColor', '#ccc');
        button.html('Adding...');

        const form = jQuery('#newPlanForm')[0];
        const formData = new FormData(form);
        formData.append('method_id', '<?php echo esc_js($_GET['method_id']); ?>');
        formData.append('action', 'create_payment_plan');

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.data.message || 'Payment plan created successfully',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    let errorMessages = '';
                    if (data.data && data.data.errors) {
                        for (let field in data.data.errors) {
                            errorMessages += `• ${data.data.errors[field]}<br>`;
                        }
                    } else {
                        errorMessages = data.message || 'Something went wrong.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        html: errorMessages,
                        confirmButtonText: 'Fix Errors'
                    });

                    button.removeAttr('disabled')
                    button.text('Add Plan');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong. Please try again later.',
                });

                button.removeAttr('disabled');
                button.text('Add Plan');
            }
        });
    }

    function editPlanModal(planId, months, minAmount, chargePercent, chargeRM, costShareMerchant, costShareOperator, adminFeeCharges) {
        jQuery('#editPlanId').val(planId);
        jQuery('#editPlanModal input[name="months"]').val(months);
        jQuery('#editPlanModal input[name="min_amount"]').val(minAmount);
        jQuery('#editPlanModal input[name="charge_percent"]').val(chargePercent);
        jQuery('#editPlanModal input[name="charge_rm"]').val(chargeRM);
        jQuery('#editPlanModal input[name="cost_share_merchant"]').val(costShareMerchant);
        jQuery('#editPlanModal input[name="cost_share_operator"]').val(costShareOperator);
        jQuery('#editPlanModal input[name="admin_fee_charges"]').val(adminFeeCharges);

        if (adminFeeCharges > 0) {
            jQuery('#editPlanModal input[name="admin_fee"][value="yes"]').prop('checked', true);
            openAdminFee({
                value: 'yes'
            });
        } else {
            jQuery('#editPlanModal input[name="admin_fee"][value="no"]').prop('checked', true);
            openAdminFee({
                value: 'no'
            });
        }

        jQuery('#editPlanModal').fadeIn(200);
    }

    function closeEditPlanModal() {
        jQuery('#editPlanModal').fadeOut(200);
    }

    function submitEditPlanForm() {
        const button = jQuery('#addPlanButton');
        button.prop('disabled', true);
        button.css('backgroundColor', '#ccc');
        button.html('Editing...');

        const form = jQuery('#editPlanForm')[0];
        const formData = new FormData(form);
        formData.append('method_id', '<?php echo esc_js($_GET['method_id']); ?>');
        formData.append('action', 'create_payment_plan');

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.data.message || 'Payment plan updated successfully',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    let errorMessages = '';
                    if (data.data && data.data.errors) {
                        for (let field in data.data.errors) {
                            errorMessages += `• ${data.data.errors[field]}<br>`;
                        }
                    } else {
                        errorMessages = data.message || 'Something went wrong.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        html: errorMessages,
                        confirmButtonText: 'Fix Errors'
                    });

                    button.removeAttr('disabled')
                    button.text('Edit Plan');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong. Please try again later.',
                });

                button.removeAttr('disabled');
                button.text('Edit Plan');
            }
        });
    }


    function deletePlans(event, planId, methodName) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            html: "You are about to delete the plan: <br><strong>" + methodName + " months</strong>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3232',
            cancelButtonColor: '#999',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?php echo admin_url('admin.php?page=senheng-payment-plan-settings-action&method_id=' . esc_js($_GET['method_id'])); ?>&delete=" + planId + "&_wpnonce=<?php echo wp_create_nonce('delete_method'); ?>";
            }
        });
    }
</script>
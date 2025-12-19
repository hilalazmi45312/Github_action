<!-- app/Views/installment/index.php -->

<?php
$base_url = admin_url('admin.php?page=senheng-payment-settings');
$methods = $pagination['data'];
$currentPage = $pagination['current_page'];
$lastPage = $pagination['last_page'];
?>
<link rel="stylesheet" href="<?php echo SENHENG_CORE_ASSETS_URL; ?>css/style.css"> <!--Import Modal CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="wrap">
    <h1>Payment Methods</h1>

    <style>
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

        .senheng-brand-popup {
            border-radius: 4px;
            padding: 24px 24px 20px;
            max-width: 480px !important;
        }

        /* Title style closer to WP admin h1/h2 vibe */
        .senheng-brand-title {
            font-size: 24px;
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 8px;
        }

        /* Body wrapper */
        .senheng-brand-container {
            margin-top: 0;
            padding-top: 0;
        }

        .senheng-brand-modal__subtitle {
            font-size: 14px;
            line-height: 1.4;
            color: #3c434a;
            font-weight: 500;
            margin: 0 0 8px;
            text-align: center;
        }

        /* Select styling */
        .senheng-brand-modal__select {
            width: 100%;
            min-height: 200px;
            max-height: 240px;
            overflow-y: auto;
            box-sizing: border-box;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            background: #fff;
            font-size: 13px;
            line-height: 1.4;
            color: #2c3338;
            padding: 8px;
            outline: none;
        }

        .senheng-brand-modal__select:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }

        /* Buttons row */
        .senheng-brand-save,
        .senheng-brand-cancel {
            min-width: 80px;
            font-size: 14px;
            line-height: 2.15384615;
            /* match WP button vertical rhythm */
            height: auto;
            margin: 16px 6px 0;
        }

        .senheng-brand-cancel {
            background: #ccd0d4;
            border-color: #ccd0d4;
            color: #1d2327;
        }

        .senheng-brand-cancel:hover {
            background: #bfc3c7;
            border-color: #bfc3c7;
            color: #1d2327;
        }

        /* Make Select2 inside SweetAlert look sane in WP admin */
        .swal2-popup .select2-container {
            width: 100% !important;
            text-align: left;
            font-size: 13px;
        }

        .swal2-popup .select2-selection.select2-selection--multiple {
            min-height: 34px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }

        .swal2-popup .select2-selection--multiple .select2-selection__rendered {
            padding: 4px 8px;
            gap: 4px;
        }

        .swal2-popup .select2-dropdown {
            z-index: 99999;
            /* above wp-admin stuff */
        }
    </style>

    <div style="margin-bottom: 40px; margin-top: 40px; display: flex; gap: 10px;">
        <button class="button button-primary" id="addPaymentMethodBtn">+ Add Payment Method</button>
        <button class="button button-primary" id="brandExcludeBtn">Admin Fee Waive (Brand)</button>
    </div>

    <table class="widefat installment-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($methods) && !empty($methods)): ?>
                <?php foreach ($methods as $key => $method): ?>
                    <tr>
                        <td><?= esc_html($_GET['paged'] ?? 1) * 10 - 10 + $key + 1; ?></td>
                        <td><?= esc_html($method->name); ?></td>
                        <td><?= esc_html(ucfirst($method->status)); ?></td>
                        <td class="installment-actions">
                            <a class="button button-view" href="<?php echo admin_url('admin.php?page=senheng-payment-plans&method_id=' . $method->id); ?>">View</a>

                            <?php if ($method->status === 'active'): ?>
                                <a class="button button-disable" href="<?php echo wp_nonce_url(admin_url('admin.php?page=senheng-payment-settings-action&disable=' . $method->id), 'disable_method'); ?>">Disable</a>
                            <?php else: ?>
                                <a class="button button-enable" href="<?php echo wp_nonce_url(admin_url('admin.php?page=senheng-payment-settings-action&enable=' . $method->id), 'enable_method'); ?>">Enable</a>
                            <?php endif; ?>

                            <a class="button button-delete" href="#" onclick="deletePaymentMethod(event, '<?php echo esc_js($method->id); ?>', '<?php echo esc_js($method->name); ?>')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No payment methods found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Pagination -->
    <?php include SENHENG_CORE_VIEW_PATH . 'component/pagination.php'; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"
    integrity="sha384-F8SYeBSrTVPFojwQeAD1UQo0dI5CKJOzc992kU0M/q72tnFcDlxHwbkiw8GLrXd8"
    crossorigin="anonymous">
</script>
<script>
    function deletePaymentMethod(event, methodId, methodName) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            html: "You are about to delete the payment method: <br><strong>" + methodName + "</strong>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3232',
            cancelButtonColor: '#999',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                // The nonce should be generated in PHP and passed to JS
                var nonce = "<?php echo wp_create_nonce('delete_method'); ?>";
                var url = "<?php echo admin_url('admin.php?page=senheng-payment-settings-action&delete='); ?>" + methodId + "&_wpnonce=" + nonce;
                window.location.href = url;
            }
        });
    }

    document.getElementById('brandExcludeBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Admin Waiver (Brands)',
            html: `
            <div class="senheng-brand-modal">
                <p class="senheng-brand-modal__subtitle">Select brands to exclude:</p>
                <select id="brandSelect" multiple="multiple" style="width:100%">
                    <?php
                    $brands = get_terms([
                        'taxonomy'   => 'product_brand',
                        'hide_empty' => false,
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                    ]);

                    $excluded = get_option('senheng_excluded_brands', []);
                    if (!is_array($excluded)) {
                        $excluded = [];
                    }
                    if (! empty($brands) && ! is_wp_error($brands)) {

                        // Extract brand slugs from the $brandWaives array
                        $waiveSlugs = array_map(static function ($item) {
                            return isset($item['brand_slug']) ? strtolower(trim($item['brand_slug'])) : '';
                        }, $brandWaives);

                        foreach ($brands as $brand) {
                            $slug = strtolower(trim($brand->slug));
                            $selected = in_array($slug, $waiveSlugs, true) ? 'selected' : '';
                            echo "<option value='{$brand->slug}' {$selected}>{$brand->name}</option>";
                        }
                    } else {
                        echo "<option disabled>No brands found</option>";
                    }
                    ?>
                </select>
            </div>
        `,
            showCancelButton: true,
            confirmButtonText: 'Save',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'senheng-brand-popup',
                title: 'senheng-brand-title',
                htmlContainer: 'senheng-brand-container',
                confirmButton: 'button button-primary senheng-brand-save',
                cancelButton: 'button senheng-brand-cancel'
            },
            buttonsStyling: false,

            // didOpen: () => {
            //     (function($) {
            //         $('#brandSelect').select2({
            //             placeholder: 'Select brands...',
            //             allowClear: true,
            //             width: '100%',
            //             dropdownParent: $(Swal.getPopup()) // <— this is the magic
            //         });
            //     })(jQuery);
            // },

            focusConfirm: false,
            preConfirm: () => {
                const values = jQuery('#brandSelect').val();
                if (!values || values.length === 0) {
                    Swal.showValidationMessage('Please select at least one brand.');
                    return false;
                }
                return values;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const brandIds = result.value;
                const nonce = "<?php echo wp_create_nonce('exclude_brands'); ?>";
                const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

                Swal.fire({
                    title: 'Saving...',
                    html: 'Please wait while we update admin fee waive.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'senheng_exclude_brands',
                            brands: brandIds.join(','),
                            _wpnonce: nonce
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Saved',
                                text: 'Selected brands have been excluded.',
                                confirmButtonText: 'OK',
                                customClass: {
                                    popup: 'senheng-brand-popup',
                                    confirmButton: 'button button-primary'
                                },
                                buttonsStyling: false
                            });
                            window.location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.data || 'Something went wrong.',
                                confirmButtonText: 'OK',
                                customClass: {
                                    popup: 'senheng-brand-popup',
                                    confirmButton: 'button button-primary'
                                },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save brand selection.',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'senheng-brand-popup',
                                confirmButton: 'button button-primary'
                            },
                            buttonsStyling: false
                        });
                    });
            }
        });
    });

    // Add Payment Method Button Handler
    document.getElementById('addPaymentMethodBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Add Payment Method',
            html: `
                <div style="text-align: left;">
                    <div style="margin-bottom: 15px;">
                        <label for="paymentMethodIpay88Id" style="display: block; margin-bottom: 5px; font-weight: 600;">iPay88 Payment ID:</label>
                        <input type="number" id="paymentMethodIpay88Id" class="swal2-input" placeholder="e.g. 156" style="width: 100%; margin: 0; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="paymentMethodName" style="display: block; margin-bottom: 5px; font-weight: 600;">Payment Method Name:</label>
                        <input type="text" id="paymentMethodName" class="swal2-input" placeholder="e.g. Public Bank EPP (Instalment Payment)" style="width: 100%; margin: 0; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="paymentMethodStatus" style="display: block; margin-bottom: 5px; font-weight: 600;">Status:</label>
                        <select id="paymentMethodStatus" class="swal2-select" style="width: 80%; padding: 8px; border: 1px solid #d9d9d9; border-radius: 4px;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Create',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'senheng-brand-popup',
                title: 'senheng-brand-title',
                htmlContainer: 'senheng-brand-container',
                confirmButton: 'button button-primary senheng-brand-save',
                cancelButton: 'button senheng-brand-cancel'
            },
            buttonsStyling: false,
            focusConfirm: false,
            preConfirm: () => {
                const ipay88Id = document.getElementById('paymentMethodIpay88Id').value.trim();
                const name = document.getElementById('paymentMethodName').value.trim();
                const status = document.getElementById('paymentMethodStatus').value;
                
                if (!name) {
                    Swal.showValidationMessage('Please enter a payment method name.');
                    return false;
                }
                
                return { ipay88_id: ipay88Id, name: name, status: status };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { ipay88_id, name, status } = result.value;
                const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

                Swal.fire({
                    title: 'Creating...',
                    html: 'Please wait while we create the payment method.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'create_payment_method',
                        ipay88_id: ipay88_id,
                        name: name,
                        status: status
                    })
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.data.message || 'Payment method created successfully.',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'senheng-brand-popup',
                                confirmButton: 'button button-primary'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let errorMsg = 'Something went wrong.';
                        if (data.data && data.data.errors) {
                            errorMsg = Object.values(data.data.errors).flat().join('<br>');
                        } else if (data.data && data.data.message) {
                            errorMsg = data.data.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMsg,
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'senheng-brand-popup',
                                confirmButton: 'button button-primary'
                            },
                            buttonsStyling: false
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to create payment method.',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'senheng-brand-popup',
                            confirmButton: 'button button-primary'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });
</script>
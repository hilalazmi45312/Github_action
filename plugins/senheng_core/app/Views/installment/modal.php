<link rel="stylesheet" href="<?php echo SENHENG_CORE_ASSETS_URL; ?>css/style.css"> <!--Import Modal CSS -->
<style>
    .btn-danger {
        background-color: #dc3545;
        color: white;
        border-radius: 20px;
        padding: 10px 20px;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto;
        width: 30%;
    }
    .bank-section .bank-name{
        margin-bottom: 0;
    }

    @media (max-width: 450px) {
        #installment-modal .modal-content{
            width: 85% !important;
        }
        .bank-section .bank-name{
            font-size: 0.8rem;
            width: 80%;
        }
        .bank-section .bank-plans td{
            font-size: 0.8rem;
        }
    }

</style>


<!-- Modal -->
<div id="installment-modal" class="custom-modal" style="z-index: 999;">
    <div class="modal-content">
        <span class="close-button" onclick="closeInstallmentModal()">&times;</span>
        <div class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="closeInstallmentModal()">Close</button>
        </div>
    </div>
</div>

<script>
    jQuery(window).click(function(event) {
        if (jQuery(event.target).is('#installment-modal')) {
            closeInstallmentModal();
        }
    });

    function closeInstallmentModal() {
        jQuery('#installment-modal').fadeOut(200);
    }
</script>
jQuery(function ($) {

    function loadBlockedUsers() {
        $.post(culbAjax.ajaxurl, {
            action: 'culb_get_blocked_users',
            _ajax_nonce: culbAjax.nonce
        }, function (res) {
            if (!res.success) return;

            let rows = '';
            res.data.forEach(u => {
                rows += `
                    <tr>
                        <td>${u.name}</td>
                        <td>${u.email}</td>
                        <td>${u.until}</td>
                        <td>${u.by}</td>
                    </tr>
                `;
            });

            $('#culb-table tbody').html(rows);
        });
    }

    $(document).on('click', '.culb-toggle', function () {
        const btn = $(this);

        $.post(culbAjax.ajaxurl, {
            action: 'culb_update_block',
            _ajax_nonce: culbAjax.nonce,
            mode: btn.data('mode'),
            users: [btn.data('id')]
        }, function () {
            location.reload();
        });
    });

    loadBlockedUsers();
});

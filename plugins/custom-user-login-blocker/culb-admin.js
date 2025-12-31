jQuery(function ($) {
    // Initialize Select2
    $('#culb-user-select').select2({
        placeholder: 'Search and select users...',
        ajax: {
            url: culbAjax.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    action: 'culb_search_users',
                    _ajax_nonce: culbAjax.nonce
                };
            },
            processResults: function (data) {
                return { results: data.data };
            }
        }
    });

    // Load blocked users
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
                        <td>
                            <button class="button culb-unblock" data-id="${u.id}">Unblock</button>
                            <button class="button culb-temp-block-btn" data-id="${u.id}">Temp Block</button>
                        </td>
                    </tr>`;
            });

            if (rows === '') rows = '<tr><td colspan="5">No blocked users.</td></tr>';
            $('#culb-blocked-table tbody').html(rows);
        });
    }

    // Single toggle from search results
    $(document).on('click', '.culb-toggle', function (e) {
        e.preventDefault();
        const btn = $(this);
        const userId = btn.data('id');
        const mode = btn.data('mode');

        btn.prop('disabled', true).text('Processing...');

        $.post(culbAjax.ajaxurl, {
            action: 'culb_update_block',
            mode: mode,
            users: [userId],
            _ajax_nonce: culbAjax.nonce
        }, function () {
            btn.text(mode === 'block' ? 'Unblock' : 'Block Permanently')
               .data('mode', mode === 'block' ? 'unblock' : 'block')
               .prop('disabled', false);
            btn.closest('tr').find('td:nth-child(3)')
               .html(mode === 'block'
                   ? '<strong style="color:red">Blocked</strong>'
                   : '<span style="color:green">Active</span>');
            loadBlockedUsers();
        });
    });

    // Bulk block
    $('#culb-bulk-block').on('click', function () {
        const users = $('#culb-user-select').val() || [];
        const role = $('#culb-role-select').val();
        const temp = $('#culb-temp-block').is(':checked');
        const until = temp ? $('#culb-until').val() : '';

        if (users.length === 0 && !role) {
            alert('Please select users or a role.');
            return;
        }

        if (temp && !until) {
            alert('Please set a date/time for temporary block.');
            return;
        }

        $(this).prop('disabled', true);
        $('#culb-status').text('Processing...');

        $.post(culbAjax.ajaxurl, {
            action: 'culb_update_block',
            mode: 'block',
            users: users,
            role: role,
            until: until,
            _ajax_nonce: culbAjax.nonce
        }, function (res) {
            if (res.success) {
                $('#culb-user-select').val(null).trigger('change');
                $('#culb-role-select').val('');
                $('#culb-temp-block').prop('checked', false);
                $('#culb-until').val('');
                $('#culb-status').text(res.data.message + ' (' + res.data.count + ' users)');
                loadBlockedUsers();
            }
            $('#culb-bulk-block').prop('disabled', false);
        });
    });

    // Unblock from blocked table
    $(document).on('click', '.culb-unblock', function () {
        const btn = $(this);
        const id = btn.data('id');

        btn.prop('disabled', true).text('...');

        $.post(culbAjax.ajaxurl, {
            action: 'culb_update_block',
            mode: 'unblock',
            users: [id],
            _ajax_nonce: culbAjax.nonce
        }, function () {
            loadBlockedUsers();
        });
    });

    // Initial load
    loadBlockedUsers();
});
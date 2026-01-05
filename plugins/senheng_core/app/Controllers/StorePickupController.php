<?php

add_filter( 'use_block_editor_for_post_type', function( $enabled, $post_type ) {
    return 'store' === $post_type ? false : $enabled;
}, 10, 2 );


/** ================================
 *  CUSTOM POST TYPE: STORES (OUTLETS)
 *  ============================================== */
function senheng_register_store_cpt()
{
  $labels = array(
    'name'               => 'Stores',
    'singular_name'      => 'Store',
    'add_new'            => 'Add New Store',
    'add_new_item'       => 'Add New Store',
    'edit_item'          => 'Edit Store',
    'new_item'           => 'New Store',
    'view_item'          => 'View Store',
    'search_items'       => 'Search Stores',
    'not_found'          => 'No stores found',
    'menu_name'          => 'Stores',
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'has_archive'        => false,
    'rewrite'            => array('slug' => 'store'),
    'show_in_rest'       => true,
    'supports'           => array('title', 'editor', 'thumbnail'),
    'menu_icon'          => 'dashicons-store',
    'menu_position'      => 20,
  );

  register_post_type('store', $args);
}
add_action('init', 'senheng_register_store_cpt');


/** ================================
 *  Shortcode: [store_locator]
 *  Outputs state -> full outlet list (no inner accordion)
 *  Hides posts with ACF true/false "store_disabled"
 *  ============================================== */
function senheng_store_locator_shortcode()
{

  // 1) Query all *enabled* stores
  $stores_query = new WP_Query(array(
    'post_type'      => 'store',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'meta_query'     => array(
      'relation' => 'OR',
      array(
        'key'     => 'store_disabled',
        'compare' => 'NOT EXISTS',   // legacy posts with no flag = enabled
      ),
      array(
        'key'     => 'store_disabled',
        'value'   => '0',
        'compare' => '=',            // unchecked
      ),
    ),
  ));

  $stores_by_state = array();
  $state_labels    = array();

  if ($stores_query->have_posts()) {
    while ($stores_query->have_posts()) {
      $stores_query->the_post();

      $state_field_raw = get_field('store_state');
      $state_value      = '';
      $state_label_raw  = '';

      if (is_array($state_field_raw)) {
        $state_value     = isset($state_field_raw['value']) ? $state_field_raw['value'] : '';
        $state_label_raw = isset($state_field_raw['label']) ? $state_field_raw['label'] : '';
      } else {
        $state_label_raw = $state_field_raw;
        $state_value     = $state_field_raw;
      }

      if (!$state_label_raw) $state_label_raw = 'Other';
      if (!$state_value)     $state_value     = $state_label_raw;

      $state_key = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $state_value)));
      $state_label_display = trim(str_replace('-', ' ', $state_label_raw));

      $address     = get_field('store_address');
      $contact     = get_field('store_contact');
      $hours       = get_field('store_hours_repeater');
      $permalink   = get_permalink();
      $store_title = get_the_title();

      if (!isset($state_labels[$state_key])) {
        $state_labels[$state_key] = $state_label_display;
      }
      if (!isset($stores_by_state[$state_key])) {
        $stores_by_state[$state_key] = array();
      }

      $stores_by_state[$state_key][] = array(
        'title'   => $store_title,
        'link'    => $permalink,
        'address' => $address,
        'contact' => $contact,
        'hours'   => $hours,
      );
    }
  }
  wp_reset_postdata();

  // 2) Order states alphabetically by label
  $ordered_state_keys = array_keys($stores_by_state);
  usort($ordered_state_keys, function ($a, $b) use ($state_labels) {
    return strcasecmp($state_labels[$a], $state_labels[$b]);
  });

  // 3) Output markup
  ob_start();
?>

  <style>
    .locator-accordion-wrapper {
      max-width: 1200px;
      width: 100%;
      margin: 40px auto 80px;
      color: #000;
      font-size: 16px;
      line-height: 1.5
    }

    .locator-state-block {
      padding-top: 10px
    }

    .locator-state-header {
      width: 100%;
      background: transparent;
      border: 0;
      padding: 0;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      text-align: left;
      font-size: 16px;
      font-weight: 700;
      line-height: 1.3;
      color: #111;
      transition: color .2s
    }

    .locator-state-header:hover {
      color: #c00;
      background: none
    }

    .locator-state-arrow {
      font-size: 12px;
      line-height: 1;
      color: #000;
      opacity: .6;
      transition: transform .2s, color .2s, opacity .2s;
      margin-left: 12px
    }

    .locator-state-block.is-open .locator-state-arrow {
      transform: rotate(180deg);
      color: #c00;
      opacity: 1
    }

    .locator-state-content {
      display: none
    }

    .locator-state-block.is-open .locator-state-content {
      display: block
    }

    /* Outlet card (full clickable) */
    .locator-outlet-card {
      position: relative;
      background: #f8f8f8;
      padding: 20px;
      margin-bottom: 8px;
      border: none !important;
      border-radius: 6px;
      transition: background .15s
    }

    .locator-outlet-card-link {
      position: absolute;
      inset: 0;
      z-index: 1;
      display: block;
      border-radius: 6px
    }

    .locator-outlet-card:hover {
      background: #f2f2f2
    }

    .locator-outlet-call-link {
      position: relative;
      z-index: 2
    }

    .locator-outlet-title {
      margin: 0 0 8px 0;
      font-size: 15px;
      font-weight: 600;
      line-height: 1.35
    }

    .locator-outlet-title span {
      text-decoration: underline;
      text-underline-offset: 2px
    }

    .locator-outlet-card:hover .locator-outlet-title span {
      color: #c00
    }

    .locator-hours-line {
      margin: 0 0 6px 0;
      font-weight: 600;
      font-size: 15px;
      color: #000;
      line-height: 1.4
    }

    .locator-addr-line {
      margin: 6px 0 8px 0;
      word-break: break-word;
      font-size: 15px;
      line-height: 1.5;
      color: #444
    }

    /* State title: make active (open) look like hover */
    .locator-state-block.is-open .locator-state-header {
      color: #c00;
    }

    /* (optional) keep the arrow matching while open — you already rotate it */
    .locator-state-block.is-open .locator-state-header .locator-state-arrow {
      color: #c00;
      opacity: 1;
    }


    @media (max-width:768px) {
      .locator-accordion-wrapper {
        margin-top: 32px;
        font-size: 15px
      }

      .locator-state-header {
        font-size: 18px
      }

      .locator-hours-line,
      .locator-addr-line {
        font-size: 14px
      }
    }
  </style>

  <div class="locator-accordion-wrapper">

    <?php foreach ($ordered_state_keys as $state_key): ?>
      <?php $state_label = $state_labels[$state_key];
      $store_list = $stores_by_state[$state_key]; ?>
      <div class="locator-state-block" data-state-block>

        <button class="locator-state-header" data-state-toggle>
          <span><?php echo esc_html($state_label); ?></span>
          <span class="locator-state-arrow">▼</span>
        </button>

        <div class="locator-state-content" data-state-content>
          <?php foreach ($store_list as $store): ?>
            <div class="locator-outlet-card">
              <?php if (!empty($store['link'])): ?>
                <a class="locator-outlet-card-link" href="<?php echo esc_url($store['link']); ?>"></a>
              <?php endif; ?>

              <div class="locator-outlet-title">
                <span><?php echo esc_html($store['title']); ?></span>
              </div>

              <?php
              $hours_output_lines = [];
              if (!empty($store['hours']) && is_array($store['hours'])) {
                $hours_rows = [];
                foreach ($store['hours'] as $row) {
                  $day  = !empty($row['store_day'])  ? trim($row['store_day'])  : '';
                  $time = !empty($row['store_time']) ? trim($row['store_time']) : '';
                  if ($day !== '' && $time !== '') $hours_rows[] = ['day' => $day, 'time' => $time];
                }
                if (!empty($hours_rows)) {
                  $groups = [];
                  $current = ['start_day' => $hours_rows[0]['day'], 'end_day' => $hours_rows[0]['day'], 'time' => $hours_rows[0]['time']];
                  for ($i = 1; $i < count($hours_rows); $i++) {
                    $row = $hours_rows[$i];
                    if ($row['time'] === $current['time']) {
                      $current['end_day'] = $row['day'];
                    } else {
                      $groups[] = $current;
                      $current = ['start_day' => $row['day'], 'end_day' => $row['day'], 'time' => $row['time']];
                    }
                  }
                  $groups[] = $current;
                  foreach ($groups as $g) {
                    $hours_output_lines[] = ($g['start_day'] === $g['end_day'])
                      ? "{$g['start_day']} : {$g['time']}"
                      : "{$g['start_day']} - {$g['end_day']} : {$g['time']}";
                  }
                }
              }
              ?>

              <?php if (!empty($hours_output_lines)): ?>
                <?php foreach ($hours_output_lines as $line): ?>
                  <div class="locator-hours-line"><?php echo esc_html($line); ?></div>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if (!empty($store['address'])): ?>
                <div class="locator-addr-line"><?php echo nl2br(esc_html($store['address'])); ?></div>
              <?php endif; ?>

              <?php if (!empty($store['contact'])): ?>
                <div class="locator-addr-line" style="color:#000;font-weight:500;">
                  <a class="locator-outlet-call-link"
                    href="tel:<?php echo esc_attr(preg_replace('/\D+/', '', $store['contact'])); ?>"
                    style="color:inherit;text-decoration:none;">
                    <?php echo esc_html($store['contact']); ?>
                  </a>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endforeach; ?>

  </div>

  <script>
    // State-level accordion
    document.querySelectorAll('[data-state-block]').forEach(function(stateBlock) {
      const headerBtn = stateBlock.querySelector('[data-state-toggle]');
      const allStateBlocks = document.querySelectorAll('[data-state-block]');
      headerBtn.addEventListener('click', function() {
        const isOpen = stateBlock.classList.contains('is-open');
        allStateBlocks.forEach(function(other) {
          other.classList.remove('is-open');
        });
        if (!isOpen) stateBlock.classList.add('is-open');
      });
    });
  </script>

<?php
  return ob_get_clean();
}
add_shortcode('store_locator', 'senheng_store_locator_shortcode');

/** ================================
 *  Auto-add 1 blank row to Store Hours on new posts
 *  ============================================== */
add_action('acf/load_value/name=store_hours_repeater', function ($value, $post_id, $field) {
  if (empty($value) && get_post_type($post_id) === 'store') {
    $value = [['store_day' => '', 'store_time' => '']];
  }
  return $value;
}, 10, 3);



/** ================================
 *  Admin polish: activities/promo sortable (kept from your file)
 *  ============================================== */
add_action('admin_head', function () {
  $screen = get_current_screen();
  if (!$screen || $screen->post_type !== 'store') return;
?><style></style><?php
                });
                add_action('admin_footer', function () {
                  $screen = get_current_screen();
                  if (!$screen || $screen->post_type !== 'store') return;
                  ?><script>
    (function() {})();
  </script><?php
                });
                add_action('admin_head', function () {
                  $screen = get_current_screen();
                  if (!$screen || $screen->post_type !== 'store') return;
            ?>
  <style>
    .in-store-promotions-items-sortable .acf-row-handle.order {
      cursor: move;
    }

    .in-store-promotions-items-sortable .acf-row.ui-sortable-helper {
      background: #fffbea;
      box-shadow: 0 4px 10px rgba(0, 0, 0, .15)
    }

    .in-store-promotions-items-placeholder {
      background: #e5f3ff;
      border: 2px dashed #007cba;
      height: 60px
    }
  </style>
<?php
                });
                add_action('admin_footer', function () {
                  $screen = get_current_screen();
                  if (!$screen || $screen->post_type !== 'store') return;
?>
  <script>
    (function($) {
      function initSortableForItems(context) {
        var $itemRepeaters = $(context).find('.acf-field-repeater[data-name="store_promotions_items"] .acf-row-list');
        $itemRepeaters.each(function() {
          var $tbody = $(this);
          $tbody.addClass('in-store-promotions-items-sortable');
          if ($tbody.data('sortable-init')) return;
          $tbody.data('sortable-init', true);
          $tbody.sortable({
            items: '> .acf-row',
            handle: '.acf-row-handle.order',
            placeholder: 'in-store-promotions-items-placeholder',
            forcePlaceholderSize: true,
            helper: 'clone',
            opacity: 0.9,
            tolerance: 'pointer',
            stop: function() {
              relabelRows($tbody);
            }
          });
        });
      }

      function relabelRows($tbody) {
        $tbody.children('.acf-row').each(function(newIndex) {
          var $row = $(this),
            oldId = $row.attr('data-id'),
            newId = 'row-' + newIndex;
          if (oldId === newId) return;
          $row.attr('data-id', newId);
          $row.find('input, select, textarea').each(function() {
            var $input = $(this),
              name = $input.attr('name');
            if (!name) return;
            $input.attr('name', name.replace(oldId, newId));
          });
        });
        $tbody.children('.acf-row').each(function(i) {
          $(this).find('.acf-row-handle.order .acf-row-number').text(i + 1);
        });
      }
      jQuery(function() {
        initSortableForItems(document);
      });
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('.acf-button');
        if (!btn) return;
        if ((btn.textContent || '').trim().toLowerCase() === 'add row') {
          setTimeout(function() {
            initSortableForItems(document);
          }, 200);
        }
      });
      if (typeof acf !== 'undefined') {
        acf.add_action('append', function($el) {
          initSortableForItems($el);
        });
      }
    })(jQuery);
  </script>
<?php
                });



                /* ==============================================================
   STORE DISABLE – UI + ADMIN EXPERIENCE
   ============================================================== */

                /** 1) Show a top toggle under the title (syncs with ACF 'store_disabled') */
                add_action('edit_form_after_title', function ($post) {
                  if ($post->post_type !== 'store') return;

                  $is_disabled = (bool) get_post_meta($post->ID, 'store_disabled', true);
                  wp_nonce_field('sh_store_disabled_top_nonce', 'sh_store_disabled_top_nonce');
?>
  <div class="sh-store-disabled-top" style="padding:10px 12px;background:#fff;margin:10px 0 16px;border:1px solid #c3c4c7;border-radius:6px;">
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
      <input type="checkbox" name="store_disabled_top" value="1" <?php checked($is_disabled, true); ?> />
      <strong>Disable store</strong>
    </label>
    <!-- <div style="font-size:12px;color:#666;margin-top:6px;">If checked, this outlet won’t appear in the Store Locator page.</div> -->
  </div>
  <style>
    /* Hide the ACF field row if you also added it in the group (avoid duplicate) */
    .acf-field[data-name="store_disabled"] {
      display: none !important;
    }
  </style>
<?php
                });

                /**
                 * Save the top toggle (runs after ACF so our value persists)
                 */
                add_action('acf/save_post', function ($post_id) {
                  // Only on store post type, and only when our nonce is present
                  if (get_post_type($post_id) !== 'store') return;
                  if (
                    !isset($_POST['sh_store_disabled_top_nonce']) ||
                    !wp_verify_nonce($_POST['sh_store_disabled_top_nonce'], 'sh_store_disabled_top_nonce')
                  ) {
                    return;
                  }

                  $val = isset($_POST['store_disabled_top']) ? '1' : '0';

                  // Save both ways (ACF + raw meta) to be safe
                  if (function_exists('update_field')) {
                    update_field('store_disabled', $val, $post_id); // ACF field name
                  }
                  update_post_meta($post_id, 'store_disabled', $val);
                }, 20); // run AFTER ACF's own save


                /* ---------- helper: pretty state label from ACF ----------- */
                function sh_get_store_state_label($post_id)
                {
                  $state = get_field('store_state', $post_id);
                  if (is_array($state)) {
                    return $state['label'] ?? ($state['value'] ?? '');
                  }
                  if ($state === '' || $state === null) return '';
                  // Try choices map
                  if (function_exists('get_field_object')) {
                    $fo = get_field_object('store_state', $post_id);
                    if (!empty($fo['choices']) && isset($fo['choices'][$state])) {
                      return $fo['choices'][$state];
                    }
                  }
                  // Fallback: prettify slug
                  return ucwords(str_replace('-', ' ', (string)$state));
                }

                /** 2) Columns: State + Status (Enabled/Disabled) */
                add_filter('manage_store_posts_columns', function ($cols) {
                  $new = [];
                  foreach ($cols as $key => $label) {
                    $new[$key] = $label;
                    if ($key === 'title') {
                      $new['store_state_col'] = __('State', 'senheng');     // NEW
                      $new['store_status']    = __('Status', 'senheng');    // NEW (replaces old Visibility)
                    }
                  }
                  return $new;
                });
                add_action('manage_store_posts_custom_column', function ($col, $post_id) {
                  if ($col === 'store_state_col') {                             // NEW
                    echo esc_html(sh_get_store_state_label($post_id));
                  }
                  if ($col === 'store_status') {                                // NEW
                    $disabled = (bool) get_post_meta($post_id, 'store_disabled', true);
                    echo $disabled
                      ? '<span class="sh-badge sh-badge--disabled">Disabled</span>'
                      : '<span class="sh-badge sh-badge--active">Enabled</span>';
                  }
                }, 10, 2);

                /** 3) Post state label next to title (“Disabled”) */
                add_filter('display_post_states', function ($states, $post) {
                  if ($post->post_type === 'store' && (bool) get_post_meta($post->ID, 'store_disabled', true)) {
                    $states['sh_disabled'] = __('Disabled', 'senheng');
                  }
                  return $states;
                }, 10, 2);

                /** 4) Badges + column widths (no row tint) */
                add_action('admin_head-edit.php', function () {
                  $screen = get_current_screen();
                  if (!$screen || $screen->id !== 'edit-store') return;
?>
  <style>
    .column-store_state_col {
      width: 160px
    }

    .column-store_status {
      width: 120px
    }

    .sh-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 12px;
      line-height: 1.6;
      border: 1px solid transparent;
      font-weight: 600
    }

    .sh-badge--disabled {
      background: #ffe9e9;
      color: #a40000;
      border-color: #f3b9b9
    }

    .sh-badge--active {
      background: #ecfff1;
      color: #0d7a2b;
      border-color: #b7e5c7
    }
  </style>
  <?php
                });

                /** 5) Bulk actions: Disable / Enable stores */
                add_filter('bulk_actions-edit-store', function ($actions) {
                  $actions['sh_disable_store'] = __('Disable (hide from locator)', 'senheng');
                  $actions['sh_enable_store']  = __('Enable (show in locator)', 'senheng');
                  return $actions;
                });
                add_filter('handle_bulk_actions-edit-store', function ($redirect_to, $action, $post_ids) {
                  if ($action !== 'sh_disable_store' && $action !== 'sh_enable_store') return $redirect_to;

                  $set = ($action === 'sh_disable_store') ? '1' : '0';
                  $count = 0;
                  foreach ((array) $post_ids as $pid) {
                    if (!current_user_can('edit_post', $pid)) continue;
                    update_post_meta($pid, 'store_disabled', $set);
                    $count++;

                    // if ($set === '1') {
                    // this for bulk disable and enable become draft/publish on store pickup
                    sh_capture_store_save_data($pid);
                    // }
                  }
                  $redirect_to = add_query_arg(array(
                    'sh_bulk_done' => $action,
                    'sh_bulk_count' => $count,
                  ), $redirect_to);
                  return $redirect_to;
                }, 10, 3);
                add_action('admin_notices', function () {
                  if (empty($_REQUEST['sh_bulk_done'])) return;
                  $count = intval($_REQUEST['sh_bulk_count'] ?? 0);
                  $action = sanitize_text_field($_REQUEST['sh_bulk_done']);
                  if ($action === 'sh_disable_store') {
                    printf('<div class="notice notice-success"><p>%d store(s) disabled.</p></div>', $count);
                  } elseif ($action === 'sh_enable_store') {
                    printf('<div class="notice notice-success"><p>%d store(s) enabled.</p></div>', $count);
                  }
                });

                /** 6) Default sort: disabled to the bottom (SAFE: includes posts without meta) */
                add_action('pre_get_posts', function ($q) {
                  if (!is_admin() || !$q->is_main_query()) return;
                  if ($q->get('post_type') !== 'store') return;

                  // Respect manual sorts chosen by the user (e.g., clicking headers)
                  if ($q->get('orderby')) return;

                  // Default: title ASC, but put disabled last
                  $q->set('orderby', 'title');
                  $q->set('order', 'ASC');

                  add_filter('posts_clauses', function ($clauses) {
                    global $wpdb;
                    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS shpm 
                              ON ({$wpdb->posts}.ID = shpm.post_id 
                                  AND shpm.meta_key = 'store_disabled')";
                    $clauses['orderby'] = " (CASE WHEN shpm.meta_value = '1' THEN 1 ELSE 0 END) ASC, {$wpdb->posts}.post_title ASC";
                    return $clauses;
                  }, 10, 1);
                });

                /** 7) Make "State" and "Status" columns sortable */
                add_filter('manage_edit-store_sortable_columns', function ($cols) {
                  $cols['store_state_col'] = 'store_state';   // clicking State sorts by its meta value
                  $cols['store_status']    = 'store_status';  // clicking Status sorts Enabled/Disabled
                  return $cols;
                });
                add_action('pre_get_posts', function ($q) {
                  if (!is_admin() || !$q->is_main_query()) return;
                  if ($q->get('post_type') !== 'store') return;

                  // Sort by State (meta value)
                  if ($q->get('orderby') === 'store_state') {
                    $q->set('meta_key', 'store_state');
                    $q->set('meta_type', 'CHAR');
                    $q->set('orderby', 'meta_value');
                    return;
                  }

                  // Sort by Status (Enabled/Disabled) using LEFT JOIN + CASE
                  if ($q->get('orderby') === 'store_status') {
                    $order = strtoupper($q->get('order') ?: 'ASC'); // ASC => Enabled first
                    add_filter('posts_clauses', function ($clauses) use ($order) {
                      global $wpdb;
                      $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS shpm2
                                  ON ({$wpdb->posts}.ID = shpm2.post_id
                                      AND shpm2.meta_key = 'store_disabled')";
                      if ($order === 'DESC') {
                        // Disabled first
                        $clauses['orderby'] = " (CASE WHEN shpm2.meta_value = '1' THEN 0 ELSE 1 END) ASC, {$wpdb->posts}.post_title ASC";
                      } else {
                        // Enabled first
                        $clauses['orderby'] = " (CASE WHEN shpm2.meta_value = '1' THEN 1 ELSE 0 END) ASC, {$wpdb->posts}.post_title ASC";
                      }
                      return $clauses;
                    }, 10, 1);
                  }
                });


                /* ==============================================================
   CAPTURE ALL STORE POST DATA ON SAVE
   ============================================================== */
                add_action('acf/save_post', 'sh_capture_store_save_data', 30, 1);

                function sh_capture_store_save_data($post_id)
                {
                  // -----------------------------------------------------------------
                  // 1. Only run for the "store" CPT
                  // -----------------------------------------------------------------
                  if (get_post_type($post_id) !== 'store') {
                    return;
                  }

                  // -----------------------------------------------------------------
                  // 2. Gather native WP fields
                  // -----------------------------------------------------------------
                  $post = get_post($post_id);

                  $native = [
                    'ID'            => $post->ID,
                    'post_title'    => $post->post_title,
                    'post_name'     => $post->post_name,  // This is the post_name (slug)
                    'post_status'   => $post->post_status,
                    'post_date'     => $post->post_date,
                    'post_modified' => $post->post_modified,
                    'post_excerpt'  => $post->post_excerpt,
                    // 'post_content'  => $post->post_content,
                  ];

                  // Thumbnail (featured image)
                  if (has_post_thumbnail($post_id)) {
                    $native['featured_image'] = wp_get_attachment_url(get_post_thumbnail_id($post_id));
                  }

                  // -----------------------------------------------------------------
                  // 3. Gather ACF fields
                  // -----------------------------------------------------------------
                  $acf = [];

                  // Get the field objects for this post
                  $field_objects = acf_get_field_groups(['post_id' => $post_id]);

                  foreach ($field_objects as $group) {
                    $fields = acf_get_fields($group['key']);

                    foreach ($fields as $field) {
                      $value = get_field($field['name'], $post_id);
                      $acf[$field['name']] = $value;
                    }
                  }

                  // -----------------------------------------------------------------
                  // 4. Merge everything
                  // -----------------------------------------------------------------
                  $payload = [
                    'timestamp' => current_time('mysql'),
                    'user_id'   => get_current_user_id(),
                    'native'    => $native,
                    'acf'       => $acf,
                  ];
                  $status_store = $payload['acf']['store_disabled'] === false ? 'publish' : 'draft';

                  // -----------------------------------------------------------------
                  // 5. Check for existing Local Pickup post with the same slug
                  // -----------------------------------------------------------------
                  $existing_pickup_location = get_page_by_path($native['post_name'], OBJECT, 'wc_pickup_location');

                  // If a matching Local Pickup post exists, update it; otherwise, create a new one
                  if ($existing_pickup_location) {
                    // Post exists, so update it
                    $post_id = $existing_pickup_location->ID;
                    $post_data = [
                      'ID'            => $post_id,
                      'post_title'    => $payload['native']['post_title'],
                      'post_content'   => '',
                      'post_name'     => $payload['native']['post_name'],
                      'post_status'   => $status_store,
                      'post_date'     => $payload['native']['post_date'],
                      'post_modified' => $payload['native']['post_modified'],
                      'post_type'     => 'wc_pickup_location',  // For Local Pickup Plus
                    ];

                    wp_update_post($post_data);
                  } else {
                    // No matching pickup location post found, so create a new one
                    $post_data = [
                      'post_title'   => $payload['native']['post_title'],
                      'post_content'   => '',
                      'post_name'    => $payload['native']['post_name'],
                      'post_status'  => $status_store,
                      'post_date'    => $payload['native']['post_date'],
                      'post_modified' => $payload['native']['post_modified'],
                      'post_type'    => 'wc_pickup_location',  // For Local Pickup Plus
                    ];

                    // Insert a new Local Pickup post
                    $post_id = wp_insert_post($post_data);
                  }

                  // -----------------------------------------------------------------
                  // 6. Update postmeta for the pickup location (store-specific details)
                  // -----------------------------------------------------------------
                  $state_mapping = array(
                    'johor' => 'JHR',
                    'kedah' => 'KDH',
                    'kelantan' => 'KTN',
                    'melaka' => 'MLK',
                    'negeri-sembilan' => 'NSN',
                    'pahang' => 'PHG',
                    'penang' => 'PNG',
                    'perak' => 'PRK',
                    'perlis' => 'PLS',
                    'sabah' => 'SBH',
                    'sarawak' => 'SWK',
                    'selangor' => 'SGR',
                    'terengganu' => 'TRG',
                    'kuala-lumpur' => 'KUL',
                    'putrajaya' => 'PJY',
                    'labuan' => 'LBN',
                  );
                  $state_code = isset($state_mapping[$payload['acf']['store_state']]) ? $state_mapping[$payload['acf']['store_state']] : 'SGR';

                  update_post_meta($post_id, '_pickup_location_address_country', 'MY');
                  update_post_meta($post_id, '_pickup_location_address_state', $state_code);
                  update_post_meta($post_id, '_pickup_location_address_postcode', $payload['acf']['store_postcode']);
                  update_post_meta($post_id, '_pickup_location_address_city', $payload['acf']['store_city']);
                  update_post_meta($post_id, '_pickup_location_address_address_1', $payload['acf']['store_address']);
                  update_post_meta($post_id, '_pickup_location_phone', $payload['acf']['store_contact']);
                  update_post_meta($post_id, '_pickup_location_email_recipients', 'gary@cloone.com.my');
                  if (!empty($payload['acf']['branch_code'])) {
                      update_post_meta(
                          $post_id,
                          '_pickup_location_branch_code',
                          sanitize_text_field($payload['acf']['branch_code'])
                      );
                  }

                  update_post_meta($post_id, '_pickup_location_products', 'a:2:{s:8:"products";a:0:{}}'); // No products assigned (empty array)
                  update_post_meta($post_id, '_pickup_location_price_adjustment_enabled', 'no'); // Price adjustment disabled
                  update_post_meta($post_id, '_pickup_location_business_hours_enabled', 'no'); // Business hours disabled
                  update_post_meta($post_id, '_pickup_location_public_holidays_enabled', 'no'); // Public holidays disabled
                  update_post_meta($post_id, '_pickup_location_pickup_lead_time_enabled', 'no'); // Pickup lead time disabled
                  update_post_meta($post_id, '_pickup_location_pickup_deadline_enabled', 'no'); // Pickup deadline disabled

                  // Insert or update geographical data in `wp_woocommerce_pickup_locations_geodata`
                  global $wpdb;
                  $table_name = $wpdb->prefix . 'woocommerce_pickup_locations_geodata';

                  // Check if this location already has an entry in the geodata table
                  $existing_entry = $wpdb->get_var(
                    $wpdb->prepare(
                      "SELECT post_id FROM $table_name WHERE post_id = %d",
                      $post_id
                    )
                  );

                  if ($existing_entry) {
                    // Update the existing entry
                    $wpdb->update(
                      $table_name,
                      array(
                        'title'       => $payload['native']['post_title'],
                        'state'       => $state_code,
                        'country'     => 'MY',
                        'postcode'    => $payload['acf']['store_postcode'],
                        'city'        => $payload['acf']['store_city'],
                        'address_1'   => $payload['acf']['store_address'],
                        'address_2'   => '', // Optional Address 2
                        'lat'         => 0,
                        'lon'         => 0,
                        'last_updated' => current_time('mysql'),
                      ),
                      array('post_id' => $post_id),
                      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s'),
                      array('%d')
                    );
                  } else {
                    // Insert new entry into geodata table
                    $wpdb->insert(
                      $table_name,
                      array(
                        'post_id'     => $post_id,
                        'title'       => $payload['native']['post_title'],
                        'state'       => $state_code,
                        'country'     => 'MY',
                        'postcode'    => $payload['acf']['store_postcode'],
                        'city'        => $payload['acf']['store_city'],
                        'address_1'   => $payload['acf']['store_address'],
                        'address_2'   => '', // Optional Address 2
                        'lat'         => 0,
                        'lon'         => 0,
                        'last_updated' => current_time('mysql'),
                      ),
                      array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s')
                    );
                  }

                  // -----------------------------------------------------------------
                  // 6. Write to log (only when WP_DEBUG is on)
                  // -----------------------------------------------------------------
                  if (defined('WP_DEBUG') && WP_DEBUG) {
                    $log_file = WP_CONTENT_DIR . '/debug-store-save.log';
                    $line = sprintf(
                      "[%s] USER:%d POST:%d\n%s\n\n",
                      $payload['timestamp'],
                      $payload['user_id'],
                      $payload['native']['ID'],
                      var_export($payload, true)
                    );
                    error_log($line, 3, $log_file);
                  }
                }


                // DELETE
                add_action('trashed_post', 'sh_trash_pickup_when_store_trashed', 10, 1);

                function sh_trash_pickup_when_store_trashed($post_id)
                {
                  // -----------------------------------------------------------------
                  // 1. Only run for the "store" CPT
                  // -----------------------------------------------------------------
                  if (get_post_type($post_id) !== 'store') {
                    custom_log('POST ID CHECK: ' . $post_id);
                    return;
                  }

                  // -----------------------------------------------------------------
                  // 2. Find the corresponding Local Pickup post based on post_name (slug)
                  // -----------------------------------------------------------------
                  $store_post = get_post($post_id);
                  $store_slug_raw = $store_post->post_name;
                  $store_slug     = preg_replace('/__trashed$/', '', $store_slug_raw);

                  // Look for the corresponding pickup location post by slug
                  $pickup_location = get_page_by_path($store_slug, OBJECT, 'wc_pickup_location');

                  // If the pickup location exists, delete it
                  if ($pickup_location) {
                    // Delete the pickup location post
                    wp_delete_post($pickup_location->ID, true);  // true means force delete

                    // -----------------------------------------------------------------
                    // 3. Delete the corresponding geodata entry from the `wp_woocommerce_pickup_locations_geodata` table
                    // -----------------------------------------------------------------
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'woocommerce_pickup_locations_geodata';

                    // Delete the geodata entry for the deleted pickup location
                    $wpdb->delete(
                      $table_name,
                      ['post_id' => $pickup_location->ID],
                      ['%d']
                    );
                  }

                  // -----------------------------------------------------------------
                  // 4. Write to log (only when WP_DEBUG is on)
                  // -----------------------------------------------------------------
                  if (defined('WP_DEBUG') && WP_DEBUG) {
                    $log_file = WP_CONTENT_DIR . '/debug-store-save.log';
                    $line = sprintf(
                      "[%s] USER:%d POST:%d\n%s\n\n",
                      current_time('mysql'),
                      get_current_user_id(),
                      $store_post->ID,
                      var_export($store_post, true)
                    );
                    error_log($line, 3, $log_file);
                  }
                }


                // Tools > Store Pickup Sync
                add_action('admin_menu', function () {
                  add_management_page('Store Pickup Sync', 'Store Pickup Sync', 'manage_options', 'sh-store-pickup-sync', function () {
                    if (isset($_POST['sh_run_sync']) && check_admin_referer('sh_sync_nonce')) {
                      $q = new WP_Query([
                        'post_type'      => 'store',
                        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'no_found_rows'  => true,
                      ]);
                      $count = 0;
                      foreach ((array) $q->posts as $pid) {
                        if (sh_capture_store_save_data($pid)) $count++;
                      }
                      echo '<div class="notice notice-success"><p>Synced ' . intval($count) . ' store(s).</p></div>';
                    }
                    echo '<div class="wrap"><h1>Store Pickup Sync</h1>
      <form method="post">';
                    wp_nonce_field('sh_sync_nonce');
                    echo '<p>This will create/update pickup locations and geodata for all Stores.</p>
      <p><input type="submit" class="button button-primary" name="sh_run_sync" value="Run Sync Now"></p>
      </form></div>';
                  });
                });


                add_action('admin_footer-edit.php', function () {
                  if (get_current_user_id() === 1) return;
                  $screen = get_current_screen();
                  if ($screen && $screen->post_type === 'wc_pickup_location') : ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        // Hide row actions (Edit | Trash | etc)
        $('.row-actions').remove();

        // Hide "Add New" button
        $('.page-title-action').hide();

        // Hide bulk actions dropdown and apply button
        $('#bulk-action-selector-top, #bulk-action-selector-bottom, #doaction, #doaction2').hide();

        // Hide checkboxes for selecting posts
        $('th.check-column, td.check-column').hide();

        // Hide import/export buttons (if you want)
        $('a.button:contains("Import Pickup Locations"), a.button:contains("Export Pickup Locations")').hide();

        // Optional: disable title click links (so they can’t open edit screen)
        $('td.title.column-title a.row-title').removeAttr('href').css('pointer-events', 'none').css('color', '#555');
      });
    </script>
<?php endif;
                });

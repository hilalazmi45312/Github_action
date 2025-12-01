<div class="ep-setting-tab-content">
    <input type="hidden" name="em_setting_type" value="frontend_views_settings">
    <ul class="subsubsub">
        <?php
        if (isset($_GET['tab_nonce']) && isset( $_GET['sub_tab'] ) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['tab_nonce'])), 'ep_settings_tab'))
        {
            $sub_tab = sanitize_text_field(wp_unslash($_GET['sub_tab']));
            $active_sub_tab = isset( $sub_tab ) && array_key_exists($sub_tab, $this->ep_get_front_view_settings_sub_tabs() ) ? $sub_tab : 'events';
        }
        else
        {
            $active_sub_tab = 'events';
        }
        $nonce = wp_create_nonce('ep_settings_tab');
        foreach ( $this->ep_get_front_view_settings_sub_tabs() as $sub_tab_id => $sub_tab_name ) {
            remove_query_arg('section');
            $sub_tab_url = esc_url( 
                add_query_arg( 
                    array(
                        'sub_tab'          => $sub_tab_id,'tab_nonce'=>$nonce 
                    )
                )
            );
            $sub_active = $active_sub_tab == $sub_tab_id ? ' current' : '';

            echo '<li><a href="' . esc_url( $sub_tab_url ) . '" title="' . esc_attr( $sub_tab_name ) . '" class="' . esc_attr($sub_active) . '">';
                echo esc_html( $sub_tab_name );
            echo '</a>  |  </li>';
        }?>
    </ul>
    <br class="clear">
    <?php 
    $this->ep_get_settings_front_views_content( $active_sub_tab ); ?>
</div>
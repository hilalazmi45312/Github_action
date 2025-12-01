<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
?>
<div class="ep-performers-container ep-mb-5 ep-box-wrap" id="ep-performers-container">
        <?php
        if( isset( $args->enable_search ) && $args->enable_search == 1 ) {
            $search_keyword = '';
            if( isset( $_GET['keyword'] ) && ! empty( $_GET['keyword'] ) ) {
                $search_keyword = sanitize_text_field( $_GET['keyword'] );
            }?>
            <form id="ep_performer_search_form" class="ep-box-search-form ep-box-bottom ep-mb-4" name="ep_performer_search_form" action="">
                <div class="ep-bg-light ep-border ep-rounded ep-px-3"> 
                    <div class="ep-box-row">
                        <div class="ep-box-col-8 ep-p-3 ep-position-relative">
                            <div class="ep-input-group">
                                <span class="ep-input-group-text ep-bg-white ep-text-muted">
                                    <span class="material-icons-outlined">search</span>
                                </span>
                                <input type="hidden" name="ep_search" value="1" />
                                <input placeholder="<?php esc_attr_e( 'Keyword', 'eventprime-event-calendar-management' ); ?>" class="ep-form-control ep-form-control-sm ep-border-start-0" type="text" name="keyword" id="ep_keyword" value="<?php echo esc_attr( $search_keyword ); ?>" />  
                                <input class="ep-btn ep-btn-dark ep-btn-sm" type="submit" value="<?php esc_attr_e( 'Search', 'eventprime-event-calendar-management' ); ?>"/>
                            </div>
                        </div>
                        <div class="ep-box-col-4 ep-event-filter-block ep-text-item-right ep-d-inline-flex ep-align-items-center">
                            <?php if ( isset( $_GET['ep_search'] ) && ! empty( $_GET['ep_search'] ) && 1 == absint( $_GET['ep_search'] ) && !empty($search_keyword) ) {
                                $performers_page_url = get_permalink( $ep_functions->ep_get_global_settings('performers_page') );?>
                                <div class="ep-box-filter-search-buttons">
                                    <a href="<?php echo esc_url( $performers_page_url ); ?>">
                                        <?php esc_html_e( 'Clear', 'eventprime-event-calendar-management' ); ?>
                                    </a>   
                                </div><?php 
                            }?>
                        </div>
                    </div>
                </div>
            </form><?php 
        }
        do_action( 'ep_performers_list_before_content', $args );
        if( isset( $args->performers ) && !empty( $args->performers ) ) {?>
            <div class="em_performers ep-event-performers-<?php echo esc_attr($args->display_style);?>-container ep-px-0">
                <div id="ep-event-performers-loader-section" class="ep-box-row ep-box-top ep-performer-<?php echo esc_attr($args->display_style);?>-wrap ">
                    <?php
                    switch ( $args->display_style ) {
                        case 'card':
                        case 'grid':
                            $card_view_file = $ep_requests->eventprime_get_ep_theme('performers/card');
                            include $card_view_file;
                            break;
                        case 'box': 
                        case 'colored_grid':
                            $box_view_file = $ep_requests->eventprime_get_ep_theme('performers/box');
                            include $box_view_file;
                            break;
                        case 'list':
                        case 'rows': 
                            $list_view_file = $ep_requests->eventprime_get_ep_theme('performers/list');
                            include $list_view_file;
                            break;
                        default: 
                            $card_view_file = $ep_requests->eventprime_get_ep_theme('performers/card');
                            include $card_view_file;
                            break;
                    }?>
                </div>
            </div><?php
        } else{?>
            <div class="ep-alert ep-alert-warning ep-mt-3">
                <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No performers found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'Currently, there are no performer. Please check back later.', 'eventprime-event-calendar-management' ); ?>
            </div><?php
        }
        // Load more performers 
        if( isset($args->performers->max_num_pages) && $args->performers->max_num_pages > 1 && isset( $args->load_more ) && $args->load_more == 1 ) {
            ?>
            <div class="ep-performers-load-more ep-frontend-loadmore ep-box-w-100 ep-my-4 ep-text-center">
                <input type="hidden" id="ep-performers-style" value="<?php echo esc_attr($args->display_style);?>"/>
                <input type="hidden" id="ep-performers-limit" value="<?php echo esc_attr($args->limit);?>"/>
                <input type="hidden" id="ep-performers-cols" value="<?php echo esc_attr($args->column);?>"/>
                <input type="hidden" id="ep-performers-featured" value="<?php echo esc_attr($args->featured);?>"/>
                <input type="hidden" id="ep-performers-popular" value="<?php echo esc_attr($args->popular);?>"/>
                <input type="hidden" id="ep-performers-orderby" value="<?php echo esc_attr($args->orderby);?>"/>
                <input type="hidden" id="ep-performers-search" value="<?php echo esc_attr($args->enable_search);?>"/>
                <input type="hidden" id="ep-performers-paged" value="<?php echo esc_attr($args->paged);?>"/>
                <input type="hidden" id="ep-performers-box-color" value="<?php echo ( isset( $args->box_color ) && ! empty( $args->box_color ) ) ? esc_attr(implode( ',', $args->box_color )) : '';?>"/>
                <button data-max="<?php echo esc_attr($args->performers->max_num_pages);?>" id="ep-loadmore-event-performers" class="ep-btn ep-btn-outline-primary"><span class="ep-spinner ep-spinner-border-sm ep-mr-1"></span><?php esc_html_e( 'Load more', 'eventprime-event-calendar-management' );?></button>
            </div><?php
        }
        do_action( 'ep_performers_list_after_content', $args ); ?>
    </div>
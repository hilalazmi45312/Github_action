<?php
/**
 * Functions for the admin
 * @since 3.2.3
 * @package WooCommerce Product Add-Ons Ultimate
 */

/*
// Need to do some kind of migration again
// Query groups - trash any with post_parent = 0 that are not in the current list of global groups
// Also, update group title fields
*/

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pewc_is_group_public() {
	$public = get_option( 'pewc_is_group_public', 'yes' );
	return apply_filters( 'pewc_is_group_public', $public );
}

function pewc_filter_group_title( $title, $post_id=0 ) {
	if( get_post_type( $post_id ) == 'pewc_group' ) {
		$title = pewc_get_group_title( $post_id, array(), true );
	}
	return $title;
}
add_filter( 'the_title', 'pewc_filter_group_title', 10, 2 );

/**
 * Add custom columns
 */
function pewc_group_custom_columns( $columns ) {
	unset( $columns['title'] );
	unset( $columns['date'] );
	$columns['pewc_group_id'] = __( 'ID', 'pewc' );
	$columns['title'] = __( 'Front End Title', 'pewc' );
	$columns['group_name'] = __( 'Group Name', 'pewc' );
	$columns['rule'] = __( 'Rule', 'pewc' );
	$columns['date'] = __( 'Date', 'pewc' );
	$columns['menu_order'] = __( 'Priority', 'pewc' );

	if( pewc_display_all_groups() ) {
		// Add an extra column to show parent product if we're showing all groups
		$columns['parent_id']	= __( 'Parent Product', 'pewc' );
	}

	return $columns;
}
add_filter( 'manage_pewc_group_posts_columns', 'pewc_group_custom_columns' );

/**
 * Populate custom columns with values
 */
function pewc_group_manage_custom_columns( $column, $post_id ) {
	global $post;
	if ( get_post_type( $post_id ) == 'pewc_group' ) {
		if( $column == 'pewc_group_id' ) {
			echo '&#35;' . $post_id;
		} else if( $column == 'menu_order' ) {
			echo $post->menu_order;
		} else if( $column == 'group_name' ) {
			echo $post->post_title;
		} else if( $column == 'rule' ) {
			$rule = get_post_meta( $post_id, 'global_rules', true );
			if( ! empty( $rule['all'] ) ) {
				printf(
					'<div>%s</div>',
					__( 'All', 'pewc' )
				);
			}
			if( ! empty( $rule['ids']['products'] ) ) {
				printf(
					'<div>%s: %s</div>',
					__( 'Product IDs', 'pewc' ),
					join( ', ', $rule['ids']['products'] )
				);
			}
			if( ! empty( $rule['categories']['cats'] ) ) {
				$cat_names = array();
				foreach( $rule['categories']['cats'] as $cat_id ) {
					if ( is_numeric( $cat_id ) ) {
						$term = get_term_by( 'id', $cat_id, 'product_cat' );
					} else {
						// 3.21.4, before, group as post types saves the cats as slugs. We'll make it consistent from now on, but we use this to handle unchanged data
						$term = get_term_by( 'slug', $cat_id, 'product_cat' );
					}
					if ( isset( $term->name ) ) {
						$cat_names[] = $term->name;
					}
				}
				printf(
					'<div>%s: %s</div>',
					__( 'Categories', 'pewc' ),
					join( ', ', $cat_names )
				);
			}
		} else if( $column == 'parent_id' ) {
			echo join( ', ', pewc_get_parent_product( $post_id ) );
		}

	}
}
add_action( 'manage_posts_custom_column', 'pewc_group_manage_custom_columns', 10, 2 );

/**
 * Only display global groups in the list
 */
function pewc_group_filter_global( $query ) {

	if( pewc_display_all_groups() && ( ! isset( $_GET['pewc_group'] ) || $_GET['pewc_group'] != 'global' ) ) {
		return;
	}

	global $pagenow;
	// Get the post type
	$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
	if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'pewc_group' && ! empty( $query->query['post_type'] ) && $query->query['post_type'] == $post_type ) {

		// We can set a parameter in the URL to retrieve groups by product ID
		$post_parent = ! empty( $_GET['parent'] ) ? $_GET['parent'] : 0;
		$query->query_vars['post_parent'] = $post_parent;

		// 3.26.8, respect the order selection
		$allowed_orderby = array( 'title', 'date' );
		if ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) ) {
			$orderby = $_GET['orderby'] . ' ID'; // add ID as secondary sort, in case there are duplicates
		} else {
			$orderby = 'menu_order ID'; // added ID as secondary sort, because menu_order sometimes retrieve posts twice, and therefore some groups are not displayed
		}
		if ( ! empty( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}

		$query->query_vars['orderby'] = $orderby;
		$query->query_vars['order'] = $order;

	}

}
add_filter( 'parse_query', 'pewc_group_filter_global' );

/**
 * Optionally display all groups, not just globals, the post list
 * @since 3.6.1
 */
function pewc_display_all_groups() {
	return apply_filters( 'pewc_display_all_groups', false );
}


function pewc_group_views( $views ) {

	if ( isset( $views['mine'] ) ) {
		// 3.20.1, remove "Mine" link in the totals. This is added in wp_count_posts() if the number of user_posts is not equal to total_posts
		unset( $views['mine'] );
	}

	if( pewc_display_all_groups() ) {

		$views['global'] = sprintf(
			'<a href="%s" class="" aria-current="page">%s <span class="count"></span></a>',
			'edit.php?post_type=pewc_group&pewc_group=global',
			__( 'Global', 'pewc' )
		);

	}

	return $views;
}
add_filter( "views_edit-pewc_group", 'pewc_group_views' );

/**
 * Filters the totals in Global Groups page. This seems to be called first before the query to get the posts.
 * @since 3.20.1
 */
function pewc_count_global_groups( $counts, $type, $perm ){

	global $wpdb, $pagenow;

	$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';

	if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'pewc_group' && $type == 'pewc_group' ) {
		// copied from wp-includes/post.php because the WP query isn't filterable yet
		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s ";

		// this is what AOU needs to only retrieve the counts for global groups
		if ( ! pewc_display_all_groups() ) {
			$query .= " AND post_parent = 0 ";
		}

		if ( 'readable' === $perm && is_user_logged_in() ) {
			$post_type_object = get_post_type_object( $type );
			if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
				$query .= $wpdb->prepare(
					" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
					get_current_user_id()
				);
			}
		}

		$query .= ' GROUP BY post_status';

		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
		$counts  = array_fill_keys( get_post_stati(), 0 );

		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}

		$counts = (object) $counts;
	}
	return $counts;

}
add_filter( 'wp_count_posts', 'pewc_count_global_groups', 10, 3 );

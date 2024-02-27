<?php

function ssp_cpt_init() {
	define( 'SSP_BLOCKED', 1 );
	define( 'SSP_AUTHORIZED', -1 );
	define( 'SSP_INCOMING', 1 );
	define( 'SSP_OUTGOING', -1 );
	if ( get_option( 'ssp_license_status', false ) !== 'valid' ) {
		return;
	}
	ssp_firewall_add_post_type();
	ssp_firewall_incoming_requests();
	add_filter( 'manage_ssp-firewall_posts_columns', 'ssp_firewall_add_columns' );
	add_filter( 'manage_edit-ssp-firewall_sortable_columns', 'ssp_firewall_sortable_columns' );
	add_filter( 'manage_ssp-firewall_posts_custom_column', 'ssp_firewall_custom_column', 10, 2 );
	add_action( 'admin_print_styles-edit.php', function() { add_thickbox(); } );
	add_filter( 'views_edit-ssp-firewall', 'remove_sub_action', 10, 1 );
	add_action( 'load-edit.php', 'ssp_bulk_action' );
	add_filter( 'bulk_actions-edit-ssp-firewall', '__return_empty_array' );
	// http request interseptor
	add_filter( 'pre_http_request', 'ssp_inspect_request', 10, 3 );
	add_filter( 'http_api_debug', 'ssp_log_response', 10, 5 );
	// for search and filter
	add_filter( 'request', 'ssp_orderby_search_columns' );
	add_action( 'restrict_manage_posts', 'ssp_new_filters' );
	add_filter( 'parse_query', 'ssp_filter_query' );
}
add_action( 'init', 'ssp_cpt_init' );

function ssp_firewall_incoming_requests() {
	if ( is_user_logged_in() ) {
		return;
	}
	$url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://" .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$ip  = $_SERVER['REMOTE_ADDR'];
	if ( !$host = parse_url( $url, PHP_URL_HOST ) or strpos( $_SERVER['SCRIPT_NAME'], "wp-cron.php" ) !== false ) {
		return;
	}
	if ( in_array( $ip, ssp_get_options( 'user-ips' ) ) ) {
		ssp_insert_post(
			array(
				'url'      => esc_url_raw( $url ),
				'code'     => '',
				'duration' => timer_stop( false, 2 ),
				'host'     => $host,
				'file'     => '',
				'line'     => '',
				'meta'     => '',
				'user-ip'  => $ip,
				'state'    => 1,
				'postdata' => ''
			)
		);
		http_response_code( 403 );
		exit;
	}
	ssp_insert_post(
		array(
			'url'      => esc_url_raw( $url ),
			'code'     => '',
			'duration' => timer_stop( false, 2 ),
			'host'     => $host,
			'file'     => '',
			'line'     => '',
			'meta'     => '',
			'user-ip'  => $ip,
			'state'    => -1,
			'postdata' => ''
		)
	);
}

function remove_sub_action( $views ) {
	return array();
}

function ssp_new_filters() {
	if ( !ssp_current_screen( 'edit-ssp-firewall' ) ) {
		return;
	}
	// no items?
	if ( !isset( $_GET['ssp-firewall_state_filter'] ) && !_get_list_table( 'WP_Posts_List_Table' ) -> has_items() ) {
		return;
	}
	// filter value
	$filter = ( !isset( $_GET['ssp-firewall_state_filter'] ) ? '' : ( int ) $_GET['ssp-firewall_state_filter'] );
	$filter_request = ( !isset( $_GET['ssp-firewall_type_filter'] ) ? '' : ( int ) $_GET['ssp-firewall_type_filter'] );
	// filter dropdown
	echo sprintf(
		'<select name="ssp-firewall_type_filter">%s%s%s</select>',
		'<option value="">' . esc_html__( 'All Requests', 'stop-spammers-premium' ) . '</option>',
		'<option value="' . SSP_INCOMING . '" ' . selected( $filter_request, SSP_INCOMING, false ) . '>' . esc_html__( 'Incoming', 'stop-spammers-premium' ). '</option>',
		'<option value="' . SSP_OUTGOING . '" ' . selected( $filter_request, SSP_OUTGOING, false ) . '>' . esc_html__( 'Outgoing', 'stop-spammers-premium' ) . '</option>'
	);

	echo sprintf(
		'<select name="ssp-firewall_state_filter">%s%s%s</select>',
		'<option value="">' . esc_html__( 'All States', 'stop-spammers-premium' ) . '</option>',
		'<option value="' . SSP_AUTHORIZED . '" ' . selected( $filter, SSP_AUTHORIZED, false ) . '>' . esc_html__( 'Authorized', 'stop-spammers-premium' ). '</option>',
		'<option value="' . SSP_BLOCKED . '" ' . selected( $filter, SSP_BLOCKED, false ) . '>' . esc_html__( 'Blocked', 'stop-spammers-premium' ) . '</option>'
	);
	// empty protocol button
	if ( empty( $filter ) and empty( $filter_request ) ) {
		submit_button( esc_html__( 'Empty Protocol', 'stop-spammers-premium' ), 'apply', 'ssp-firewall_delete_all', false );
	}
}

function ssp_filter_query( $query ) {
	if ( !empty( $_GET['ssp-firewall_state_filter'] ) ) {
      $query->set( 'meta_query', [array( 'key' => '_ssp-firewall_state', 'value' => ( int ) $_GET['ssp-firewall_state_filter'] )] );
	}
	if ( !empty( $_GET['ssp-firewall_type_filter'] ) ) {
		$meta_filter = array();
		if ( $query->get( 'meta_query' ) ) {
			$meta_filter = $query->get( 'meta_query' );
		}
		if ( ( int ) $_GET['ssp-firewall_type_filter'] === 1 ) {
			$meta_filter[] = array( 'key' => '_ssp-firewall_user-ip' );
		}  else {
			$meta_filter[] = array( 'key' => '_ssp-firewall_user-ip', 'compare' => 'NOT EXISTS' );
		}
		$query->set( 'meta_query', $meta_filter );
	}
}

function ssp_bulk_action() {
	if ( !ssp_current_screen( 'edit-ssp-firewall' ) ) {
		return;
	}
	if ( !current_user_can( 'administrator' ) ) {
		return;
	}
	if ( !empty( $_GET['ssp-firewall_delete_all'] ) ) {
		// check nonce
		check_admin_referer( 'bulk-posts' );
		// delete items
		ssp_delete_all();
		// we're done
		wp_safe_redirect( add_query_arg( array( 'post_type' => 'ssp-firewall' ), admin_url( 'edit.php' ) ) );
		exit();
	}
	if ( empty( $_GET['ssp-action'] ) OR empty( $_GET['ssp-type'] ) ) {
		return;
	}
	// set vars
	$action = $_GET['ssp-action'];
	$type   = $_GET['ssp-type'];
	// validate action and type
	if ( !in_array( $action, array( 'block', 'unblock' ) ) OR !in_array( $type, array( 'host', 'file', 'user-ip' ) ) ) {
		return;
	}
	// security check
	check_admin_referer( 'ssp-firewall' );
	if ( empty( $_GET['id'] ) ) {
		return;
	}
	$item = ssp_get_meta( $_GET['id'], $type );
	ssp_update_options( $item , $type . 's', $action );
	wp_safe_redirect(
		add_query_arg( array( 'post_type' => 'ssp-firewall', 'updated' => count( $ids ) * ( $action === 'unblock' ? -1 : 1 ), 'paged' => ssp_get_pagenum() ), admin_url( 'edit.php' ) )
	);
	exit();
}

function ssp_delete_all( $offset = 0 ) {
	$offset = ( int ) $offset;
	if ( $offset < 0 ) {
		return;
	}
	global $wpdb;
	$subquery = sprintf(
		"SELECT * FROM ( SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = 'ssp-firewall' ORDER BY `ID` DESC LIMIT %d, 18446744073709551615 ) as t",
		$offset
	);
	// delete postmeta
	$wpdb->query(
		sprintf(
			"DELETE FROM `$wpdb->postmeta` WHERE `post_id` IN (%s)",
			$subquery
		)
	);
	// delete posts
	$wpdb->query(
		sprintf(
			"DELETE FROM `$wpdb->posts` WHERE `ID` IN (%s)",
			$subquery
		)
	);
}

function ssp_delete_selected( $count = 0 ) {
	global $wpdb;
	$subquery = sprintf(
		"SELECT * FROM ( SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = 'ssp-firewall' ORDER BY `ID` ASC LIMIT 0, %d  ) as t",
		$count
	);
	// delete postmeta
	$wpdb->query(
		sprintf(
			"DELETE FROM `$wpdb->postmeta` WHERE `post_id` IN (%s)",
			$subquery
		)
	);
	// delete posts
	$wpdb->query(
		sprintf(
			"DELETE FROM `$wpdb->posts` WHERE `ID` IN (%s)",
			$subquery
		)
	);
}

function ssp_update_options( $item, $type, $action ) {
	$options = get_option( 'ssp-firewall', array() );
	if ( !isset( $options[$type] ) ) {
		$options[$type] = array();
	}
	if ( !isset( $options[$type][$item] ) and $action == "block" ) {
		$options[$type][$item] = $item;
	}
	if ( isset( $options[$type][$item] ) and $action == "unblock" ) {
		unset( $options[$type][$item] );
	}
	update_option( 'ssp-firewall', $options );	
}

function ssp_get_options( $type ) {
	$options = get_option( 'ssp-firewall', array() );
	if ( isset( $options[$type] ) ) {
		return $options[$type];
	}
	return $options;
}

function ssp_firewall_add_post_type() {
	register_post_type(
		'ssp-firewall',
		array(
			'label'  => 'Firewall',
			'labels' => array(
				'not_found' 		 => esc_html__( 'No items found. Future connections will be shown here.', 'stop-spammers-premium' ),
				'not_found_in_trash' => esc_html__( 'No items found in trash.', 'stop-spammers-premium' ),
				'search_items' 		 => esc_html__( 'Search in Destination', 'stop-spammers-premium' )
			),
			'public' 	   => false,
			'show_ui' 	   => true,
			'query_var'    => true,
			'hierarchical' => false,
			'capabilities' => array(
				'create_posts' => false,
				'delete_posts' => false
			),
			'show_in_menu' 		  => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true
		)
	);
	// admin only
	if ( !is_admin() ) {
		return;
	}
}

function ssp_firewall_add_columns() {
	return ( array ) apply_filters(
		'ssp-firewall_manage_columns',
		array(
			'url'      => esc_html__( 'Destination', 'stop-spammers-premium'),
			'file'     => esc_html__( 'File', 'stop-spammers-premium'),
			'state'    => esc_html__( 'State', 'stop-spammers-premium'),
			'code'     => esc_html__( 'Code', 'stop-spammers-premium'),
			'duration' => esc_html__( 'Duration', 'stop-spammers-premium'),
			'created'  => esc_html__( 'Time', 'stop-spammers-premium'),
			'postdata' => esc_html__( 'Data', 'stop-spammers-premium')
		)
	);
}

function ssp_firewall_custom_column( $column, $post_id ) {
	$types = (array) apply_filters(
		'ssp-firewall_custom_column',
		array(
			'url'      =>  'ssp_html_url',
			'file'     =>  'ssp_html_file',
			'state'    =>  'ssp_html_state',
			'code'     =>  'ssp_html_code',
			'duration' =>  'ssp_html_duration',
			'created'  =>  'ssp_html_created',
			'postdata' =>  'ssp_html_postdata'
		)
	);
	// if type exists
	if ( !empty( $types[$column] ) ) {
		// callback
		$callback = $types[$column];
		// execute
		if ( is_callable( $callback ) ) {
			call_user_func(
				$callback,
				$post_id
			);
		}
	}
}

function ssp_firewall_sortable_columns() {
	return ( array )apply_filters(
		'ssp-firewall_sortable_columns',
		array(
			'url'     => 'url',
			'file'    => 'file',
			'state'   => 'state',
			'code'    => 'code',
			'created' => 'date'
		)
	);
}

function ssp_html_url( $post_id ) {
	// init data
	$url  = ssp_get_meta( $post_id, 'url' );
	$host = ssp_get_meta( $post_id, 'host' );
	// already blacklisted?
	$blacklisted = in_array( $host, ssp_get_options( 'hosts' ) );
	// print output

	if ( !empty( ssp_get_meta( $post_id, 'user-ip' ) ) and empty( ssp_get_meta( $post_id, 'file' ) ) ) {
		echo sprintf(
			'<div><p class="label blacklisted_%d"></p>%s</div>',
			$blacklisted,
			str_replace( $host, '<code>' . $host . '</code>', esc_url( $url ) )
		);
	} else {
		echo sprintf(
			'<div><p class="label blacklisted_%d"></p>%s<div class="row-actions">%s</div></div>',
			$blacklisted,
			str_replace( $host, '<code>' . $host . '</code>', esc_url( $url ) ),
			ssp_action_link( $post_id, 'host', $blacklisted )
		);
	}
}

function ssp_html_file( $post_id ) {
	// init data
	$file = ssp_get_meta( $post_id, 'file' );
	$line = ssp_get_meta( $post_id, 'line' );
	$meta = ssp_get_meta( $post_id, 'meta' );
	$ip   = ssp_get_meta( $post_id, 'user-ip' );
	if ( !is_array( $meta ) ) {
		$meta = array();
	}
	if ( !isset( $meta['type'] ) ) {
		$meta['type'] = 'WordPress';
	}
	if ( !isset( $meta['name'] ) ) {
		$meta['name'] = 'Core';
	}
	// already blacklisted?
	$blacklisted    = in_array( $file, ssp_get_options( 'files' ) );
	$blacklisted_ip = in_array( $ip, ssp_get_options( 'user-ips' ) );
	if ( !empty( ssp_get_meta( $post_id, 'user-ip' ) ) ) {
		// print output
		echo sprintf(
			'<div><p class="label blacklisted_%d"></p>%s: %s<br><code>%s</code><div class="row-actions">%s</div></div>',
			$blacklisted_ip,
			'User',
			"IP",
			$ip,
			ssp_action_link( $post_id, 'user-ip', $blacklisted_ip )
		);
	} else {
		// print output
		echo sprintf(
			'<div><p class="label blacklisted_%d"></p>%s: %s<br><code>/%s:%d</code><div class="row-actions">%s</div></div>',
			$blacklisted,
			$meta['type'],
			$meta['name'],
			$file,
			$line,
			ssp_action_link(
				$post_id,
				'file',
				$blacklisted
			)
		);
	}
}

function ssp_html_state( $post_id ) {
	// item state
	$state = ssp_get_meta( $post_id, 'state' );
	// state values
	$states = array(
		SSP_BLOCKED    => 'Blocked',
		SSP_AUTHORIZED => 'Authorized'
	);
	// print the state
	echo sprintf( '<span class="%s">%s</span>', strtolower( $states[$state] ), esc_html__( $states[$state], 'stop-spammers-premium' ) );
	// colorize blocked item
	if ( $state == SSP_BLOCKED ) {
		echo sprintf( '<style>#post-%1$d{background:rgba(248, 234, 232, 0.8)}#post-%1$d.alternate{background:#f8eae8}</style>', $post_id );
	}
}
	
function ssp_html_code( $post_id ) {
	echo ssp_get_meta( $post_id, 'code' );
}
	
function ssp_html_duration( $post_id ) {
	if ( $duration = ssp_get_meta( $post_id, 'duration' ) ) {
		echo sprintf( __( '%s seconds', 'stop-spammers-premium' ), $duration );
	}
}

function ssp_html_created( $post_id ) {
	echo sprintf( __( '%s ago', 'stop-spammers-premium' ), human_time_diff( get_post_time( 'G', true, $post_id ) ) );
}

function ssp_html_postdata( $post_id ) {
	// item post data
	$postdata = ssp_get_meta( $post_id, 'postdata' );
	// empty data?
	if ( empty( $postdata ) ) {
		return;
	}
	// parse post data
	if ( !is_array( $postdata ) ) {
		wp_parse_str( $postdata, $postdata );
	}
	// empty array?
	if ( empty( $postdata ) ) {
		return;
	}
	// thickbox content start
	echo sprintf( '<div id="ssp-firewall-thickbox-%d" class="ssp-firewall-hidden"><pre>', $post_id );
	// post data
	print_r( $postdata );
	// thickbox content end
	echo '</pre></div>';
	// thickbox button
	echo sprintf( '<a href="#TB_inline?width=400&height=300&inlineId=ssp-firewall-thickbox-%d" class="button thickbox">%s</a>', $post_id, esc_html__( 'Show', 'stop-spammers-premium') );
}

function ssp_get_meta( $post_id, $key ) {
	if ( $value = get_post_meta( $post_id, '_ssp-firewall_' . $key, true ) ) {
		return $value;
	}
	return get_post_meta( $post_id, $key, true );
}

function ssp_action_link( $post_id, $type, $blacklisted = false ) {
	// link action
	$action = ( $blacklisted ? 'unblock' : 'block' );
	// block link
	return sprintf(
		'<a href="%s" class="%s">%s</a>',
		esc_url( wp_nonce_url( add_query_arg( array( 'id' => $post_id, 'paged' => ssp_get_pagenum(), 'ssp-type' => $type, 'ssp-action' => $action, 'post_type' => 'ssp-firewall' ), admin_url( 'edit.php' ) ), 'ssp-firewall' ) ),
		$action,
		esc_html__( sprintf( '%s this %s', ucfirst( $action ), str_replace( '-', ' ', $type ) ), 'ssp-firewall' )
	);
}

function ssp_get_pagenum() {
	return ( empty( $GLOBALS['pagenum'] ) ? _get_list_table( 'WP_Posts_List_Table' ) -> get_pagenum() : $GLOBALS['pagenum'] );
}

function ssp_debug_backtrace() {
	// reverse items
	$trace = array_reverse( debug_backtrace() );
	// loop items
    foreach( $trace as $index => $item ) {
    	if ( !empty( $item['function'] ) && strpos( $item['function'], 'wp_remote_' ) !== false ) {
    		// use prev item
    		if ( empty( $item['file'] ) ) {
    			$item = $trace[-- $index];
    		}
			// get file and line
    		if ( !empty( $item['file'] ) && ! empty( $item['line'] ) ) {
    			return $item;
    		}
    	}
    }
}

function ssp_face_detect( $path ) {
	// default
	$meta = array( 'type' => 'WordPress', 'name' => 'Core' );
	// empty path
	if ( empty( $path ) ) {
		return $meta;
	}
	// search for plugin
	if ( $data = ssp_localize_plugin( $path ) ) {
		return array(
			'type' => 'Plugin',
			'name' => $data['Name']
		);
	// search for theme
	} else if ( $data = ssp_localize_theme( $path ) ) {
		return array(
			'type' => 'Theme',
			'name' => $data->get( 'Name' )
		);
	}
	return $meta;
}

function ssp_localize_plugin( $path ) {
	// check path
	if ( strpos( $path, WP_PLUGIN_DIR ) === false ) {
		return false;
	}
	// reduce path
	$path = ltrim( str_replace( WP_PLUGIN_DIR, '', $path ), DIRECTORY_SEPARATOR );
	// get plugin folder
	$folder = substr( $path, 0, strpos( $path, DIRECTORY_SEPARATOR ) ) . DIRECTORY_SEPARATOR;
	// frontend
	if ( !function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	// all active plugins
	$plugins = get_plugins();
	// loop plugins
	foreach( $plugins as $path => $plugin ) {
		if ( strpos( $path, $folder ) === 0 ) {
			return $plugin;
		}
	}
}

function ssp_localize_theme( $path ) {
	// check path
	if ( strpos( $path, get_theme_root() ) === false ) {
		return false;
	}
	// reduce path
	$path = ltrim( str_replace( get_theme_root(), '', $path ), DIRECTORY_SEPARATOR );
	// get theme folder
	$folder = substr( $path, 0, strpos( $path, DIRECTORY_SEPARATOR ) );
	// get theme
	$theme = wp_get_theme( $folder );
	// check and return theme
	if ( $theme->exists() ) {
		return $theme;
	}
	return false;
}

function ssp_insert_post( $meta ) {
	// empty?
	if ( empty( $meta ) ) {
		return;
	}
	// limit requests entries
	$all_requests = wp_count_posts( 'ssp-firewall' );
	if ( $all_requests->publish >= 10000 ) {
		ssp_delete_selected( 1000 );
	}
	// create post
	$post_id = wp_insert_post(
		array(
			'post_status' => 'publish',
			'post_type'   => 'ssp-firewall'
		)
	);
	// add meta values
	foreach( $meta as $key => $value ) {
		add_post_meta(
			$post_id,
			'_ssp-firewall_' . $key,
			$value,
			true
		);
	}
	return $post_id;
}

function ssp_get_postdata( $args ) {
	// no post data?
	if ( empty( $args['method'] ) OR $args['method'] !== 'POST' ) {
		return NULL;
	}
	// no body data?
	if ( empty( $args['body'] ) ) {
		return NULL;
	}
	return $args['body'];
}

// http request interseptor
function ssp_inspect_request( $pre, $args, $url ) {
	// empty url
	if ( empty( $url ) ) {
		return $pre;
	}
	// invalid host
	if ( !$host = parse_url( $url, PHP_URL_HOST ) ) {
		return $pre;
	}
	// timer start
	timer_start();
	$track = ssp_debug_backtrace();
	// no reference file found
	if ( empty( $track['file'] ) ) {
		return $pre;
	}
	// show your face, file
	$meta = ssp_face_detect( $track['file'] );
	// init data
	$file = str_replace( ABSPATH, '', $track['file'] );
	$line = ( int ) $track['line'];
	// blocked item?
	if ( in_array( $host, ssp_get_options( 'hosts' ) ) OR in_array( $file, ssp_get_options( 'files' ) ) ) {
		return ssp_insert_post(
			array(
				'url'      => esc_url_raw( $url ),
				'code'     => NULL,
				'host'     => $host,
				'file'     => $file,
				'line'     => $line,
				'meta'     => $meta,
				'state'    => 1,
				'postdata' => ssp_get_postdata( $args )
			)
		);
	}
	return $pre;
}

function ssp_log_response( $response, $type, $class, $args, $url ) {
	// only response type
	if ( $type !== 'response' ) {
		return false;
	}
	// empty url
	if ( empty( $url ) ) {
		return false;
	}
	// validate host
	if ( !$host = parse_url( $url, PHP_URL_HOST ) ) {
		return false;
	}
	// backtrace data
	$backtrace = ssp_debug_backtrace();
	// no reference file found
	if ( empty( $backtrace['file'] ) ) {
		return false;
	}
	// show your face, file
	$meta = ssp_face_detect( $backtrace['file'] );
	// extract backtrace data
	$file = str_replace( ABSPATH, '', $backtrace['file'] );
	$line = ( int ) $backtrace['line'];
	// response code
	$code = ( is_wp_error( $response ) ? -1 : wp_remote_retrieve_response_code( $response ) );
	// insert cpt
	ssp_insert_post(
		array(
			'url'      => esc_url_raw( $url ),
			'code'     => $code,
			'duration' => timer_stop( false, 2 ),
			'host'     => $host,
			'file'     => $file,
			'line'     => $line,
			'meta'     => $meta,
			'state'    => -1,
			'postdata' => ssp_get_postdata( $args )
		)
	);
}

function ssp_orderby_search_columns( $vars ) {
	if ( !is_admin() ) {
		return $vars;
	}
	if ( !ssp_current_screen( 'edit-ssp-firewall' ) ) {
		return $vars;
	}
	// cpt search
	if ( !empty( $vars['s'] ) ) {
		add_filter( 'get_meta_sql', 'ssp_modify_and_or' );
		$search_key = "_ssp-firewall_url";
		if ( filter_var( $vars['s'], FILTER_VALIDATE_IP ) ) {
			$search_key = "_ssp-firewall_user-ip";
		}
		// search in urls
		$meta_query = array(
			array(
				'key'     => $search_key,
				'value'   => $vars['s'],
				'compare' => 'LIKE'
			)
		);
		// combined with the filter
		if ( !empty( $_GET['ssp-firewall_state_filter'] ) ) {
			$meta_query[] = array(
				'key'     => '_ssp-firewall_state',
				'value'   => ( int ) $_GET['ssp-firewall_state_filter'],
				'compare' => '=',
				'type'    => 'numeric'
			);
		}
		// merge attrs
		$vars = array_merge(
			$vars,
			array(
				'meta_query' => $meta_query
			)
		);
	}
	// cpt orderby
	if ( empty( $vars['orderby'] ) OR !in_array( $vars['orderby'], array( 'url', 'file', 'state', 'code' ) ) ) {
		return $vars;
	}
	// set var
	$orderby = $vars['orderby'];
	return array_merge(
		$vars,
		array(
            'meta_key' => '_ssp-firewall_' . $orderby,
            'orderby'  => ( in_array( $orderby, array( 'code', 'state' ) ) ? 'meta_value_num' : 'meta_value' )
        )
     );
}

function ssp_modify_and_or( $join_where ) {
	if ( !empty( $join_where['where'] ) ) {
		$join_where['where'] = str_replace( 'AND (', 'OR (', $join_where['where'] );
	}
	return $join_where;
}

function ssp_current_screen( $id ) {
	$screen = get_current_screen();
	return ( is_object( $screen ) && $screen->id === $id );
}
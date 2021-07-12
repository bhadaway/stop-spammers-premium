<?php 

	function ssp_cpt_init() {

		define( 'SSP_BLOCKED', 1 );
		define( 'SSP_AUTHORIZED', -1 );

		ssp_firewall_add_post_type();
		
		add_filter( 'manage_ssp-firewall_posts_columns', 'ssp_firewall_add_columns' );
		add_filter( 'manage_edit-ssp-firewall_sortable_columns', 'ssp_firewall_sortable_columns' );
		add_filter( 'manage_ssp-firewall_posts_custom_column', 'ssp_firewall_custom_column', 10, 2 );

		add_action( 'admin_print_styles-edit.php', function() {
			add_thickbox();
		});

		add_filter( 'views_edit-ssp-firewall', 'remove_sub_action', 10, 1 );
		add_action( 'load-edit.php', 'ssp_bulk_action' );
		add_filter( 'bulk_actions-edit-ssp-firewall', '__return_empty_array' );

		// HTTP Request interseptor
		add_filter( 'pre_http_request', 'ssp_inspect_request', 10, 3 );
		add_filter( 'http_api_debug', 'ssp_log_response', 10, 5 );


	}
	add_action( 'init', 'ssp_cpt_init' );

	function remove_sub_action( $views ) {
		return array();
	}

	function ssp_bulk_action() {


		if ( empty($_GET['action']) OR empty($_GET['type']) ) {
			return;
		}

	}

	function ssp_firewall_add_post_type() {
		register_post_type(
			'ssp-firewall',
			array(
				'label' => 'Firewall',
				'labels' => array(
					'not_found' => esc_html__('No items found. Future connections will be shown at this place.', 'stop-spammers-premium'),
					'not_found_in_trash' => esc_html__('No items found in trash.', 'stop-spammers-premium'),
					'search_items' => esc_html__('Search in destination', 'stop-spammers-premium')
				),
				'public' => false,
				'show_ui' => true,
				'query_var' => true,
				'hierarchical' => false,
				'capabilities' => array(
					'create_posts' => false,
					'delete_posts' => false
				),
				'publicly_queryable' => false,
				'exclude_from_search' => true
			)
		);

		/* Admin only */
		if ( ! is_admin() ) {
			return;
		}
	}

	function ssp_firewall_add_columns() {
		
		return (array) apply_filters(
			'ssp-firewall_manage_columns',
			array(
				'url'      => esc_html__('Destination', 'ssp-firewall'),
				'file'     => esc_html__('File', 'ssp-firewall'),
				'state'    => esc_html__('State', 'ssp-firewall'),
				'code'     => esc_html__('Code', 'ssp-firewall'),
				'duration' => esc_html__('Duration', 'ssp-firewall'),
				'created'  => esc_html__('Time', 'ssp-firewall'),
				'postdata' => esc_html__('Data', 'ssp-firewall')
			)
		);

	}

	function ssp_firewall_custom_column( $column, $post_id ) {
		
		$types = (array) apply_filters(
			'ssp-firewall_custom_column',
			array(
				'url'      =>  '_html_url',
				'file'     =>  '_html_file',
				'state'    =>  '_html_state',
				'code'     =>  '_html_code',
				'duration' =>  '_html_duration',
				'created'  =>  '_html_created',
				'postdata' =>  '_html_postdata'
			)
		);

		/* If type exists */
		if ( ! empty($types[$column]) ) {
			/* Callback */
			$callback = $types[$column];

			/* Execute */
			if ( is_callable($callback) ) {
				call_user_func(
					$callback,
					$post_id
				);
			}
		}
	}

	function ssp_firewall_sortable_columns() {

		return (array)apply_filters(
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

	function _html_url( $post_id ) {
		/* Init data */
		$url = _get_meta($post_id, 'url');
		$host = _get_meta($post_id, 'host');

		/* Already blacklisted? */
		$blacklisted = in_array( $host, array() );

		/* Print output */
		echo sprintf(
			'<div><p class="label blacklisted_%d"></p>%s<div class="row-actions">%s</div></div>',
			$blacklisted,
			str_replace( $host, '<code>' .$host. '</code>', esc_url($url) ),
			_action_link( $post_id, 'host', $blacklisted )
		);
	}
	function _html_file( $post_id ) {
		/* Init data */
		$file = _get_meta($post_id, 'file');
		$line = _get_meta($post_id, 'line');
		$meta = _get_meta($post_id, 'meta');

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		if ( ! isset( $meta['type'] ) ) {
			$meta['type'] = 'WordPress';
		}

		if ( ! isset( $meta['name'] ) ) {
			$meta['name'] = 'Core';
		}

		/* Already blacklisted? */
		$blacklisted = in_array( $file, array() );

		/* Print output */
		echo sprintf(
			'<div><p class="label blacklisted_%d"></p>%s: %s<br /><code>/%s:%d</code><div class="row-actions">%s</div></div>',
			$blacklisted,
			$meta['type'],
			$meta['name'],
			$file,
			$line,
			_action_link(
				$post_id,
				'file',
				$blacklisted
			)
		);
	}

	function _html_state($post_id)
	{
		/* Item state */
		$state = _get_meta($post_id, 'state');

		/* State values */
		$states = array(
			SSP_BLOCKED    => 'Blocked',
			SSP_AUTHORIZED => 'Authorized'
		);

		/* Print the state */
		echo sprintf( '<span class="%s">%s</span>', strtolower($states[$state]), esc_html__($states[$state], 'ssp-firewall') );

		/* Colorize blocked item */
		if ( $state == SSP_BLOCKED ) {
			echo sprintf( '<style>#post-%1$d {background:rgba(248, 234, 232, 0.8)}#post-%1$d.alternate {background:#f8eae8}</style>', $post_id );
		}
	}
	
	function _html_code($post_id) {
		echo _get_meta($post_id, 'code');
	}
	
	function _html_duration($post_id) {
		if ( $duration = _get_meta($post_id, 'duration') ) {
			echo sprintf( __( '%s seconds', 'ssp-firewall' ), $duration );
		}
	}

	function _html_created($post_id)
	{
		echo sprintf( __( '%s ago', 'ssp-firewall' ), human_time_diff( get_post_time('G', true, $post_id) ) );
	}

	function _html_postdata($post_id) {
		/* Item post data */
		$postdata = _get_meta($post_id, 'postdata');

		/* Empty data? */
		if ( empty($postdata) ) {
			return;
		}

		/* Parse POST data */
		if ( ! is_array($postdata) ) {
			wp_parse_str($postdata, $postdata);
		}

		/* Empty array? */
		if ( empty($postdata) ) {
			return;
		}

		/* Thickbox content start */
		echo sprintf( '<div id="ssp-firewall-thickbox-%d" class="ssp-firewall-hidden"><pre>', $post_id );

		/* POST data */
		print_r($postdata);

		/* Thickbox content end */
		echo '</pre></div>';

		/* Thickbox button */
		echo sprintf( '<a href="#TB_inline?width=400&height=300&inlineId=ssp-firewall-thickbox-%d" class="button thickbox">%s</a>', $post_id, esc_html__('Show', 'ssp-firewall') );
	}


	function _get_meta( $post_id, $key ) {
		
		if ( $value = get_post_meta($post_id, '_ssp-firewall_' .$key, true) ) {
			return $value;
		}

		return get_post_meta($post_id, $key, true);
	}

	function _action_link($post_id, $type, $blacklisted = false) {
		/* Link action */
		$action = ( $blacklisted ? 'unblock' : 'block' );

		/* Block link */
		return sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( wp_nonce_url( add_query_arg( array( 'id'	    => $post_id, 'paged'		=> _get_pagenum(), 'type'		=> $type, 'action'    => $action, 'post_type' => 'ssp-firewall' ), admin_url('edit.php') ), 'ssp-firewall' ) ),
			$action,
			esc_html__( sprintf( '%s this %s', ucfirst($action), $type ), 'ssp-firewall' )
		);
	}

	function _get_pagenum() {
		return (empty($GLOBALS['pagenum']) ? _get_list_table('WP_Posts_List_Table')->get_pagenum() : $GLOBALS['pagenum'] );
	}

	function _is_internal($host) {
		/* Get the blog host */
		$blog_host = parse_url(
			get_bloginfo('url'),
			PHP_URL_HOST
		);

		return ( $blog_host === $host );
	}

	function _debug_backtrace() {
		/* Reverse items */
		$trace = array_reverse(debug_backtrace());

		/* Loop items */
    	foreach( $trace as $index => $item ) {
    		if ( ! empty($item['function']) && strpos($item['function'], 'wp_remote_') !== false ) {
    			/* Use prev item */
    			if ( empty($item['file']) ) {
    				$item = $trace[-- $index];
    			}

    			/* Get file and line */
    			if ( ! empty($item['file']) && ! empty($item['line']) ) {
    				return $item;
    			}
    		}
    	}
	}

	function _face_detect( $path ) {
		
		/* Default */
		$meta = array( 'type' => 'WordPress', 'name' => 'Core' );

		/* Empty path */
		if ( empty( $path ) ) {
			return $meta;
		}

		/* Search for plugin */
		if ( $data = _localize_plugin($path) ) {
			return array(
				'type' => 'Plugin',
				'name' => $data['Name']
			);

		/* Search for theme */
		} else if ( $data = _localize_theme($path) ) {
			return array(
				'type' => 'Theme',
				'name' => $data->get('Name')
			);
		}

		return $meta;
	}

	function _localize_plugin( $path ) {
		/* Check path */
		if ( strpos($path, WP_PLUGIN_DIR) === false ) {
			return false;
		}

		/* Reduce path */
		$path = ltrim( str_replace(WP_PLUGIN_DIR, '', $path), DIRECTORY_SEPARATOR );

		/* Get plugin folder */
		$folder = substr( $path, 0, strpos($path, DIRECTORY_SEPARATOR) ) . DIRECTORY_SEPARATOR;

		/* Frontend */
		if ( ! function_exists('get_plugins') ) {
			require_once(ABSPATH. 'wp-admin/includes/plugin.php');
		}

		/* All active plugins */
		$plugins = get_plugins();

		/* Loop plugins */
		foreach( $plugins as $path => $plugin ) {
			if ( strpos($path, $folder) === 0 ) {
				return $plugin;
			}
		}
	}
	function _localize_theme($path) {

		/* Check path */
		if ( strpos($path, get_theme_root()) === false ) {
			return false;
		}

		/* Reduce path */
		$path = ltrim( str_replace(get_theme_root(), '', $path), DIRECTORY_SEPARATOR );

		/* Get theme folder */
		$folder = substr( $path, 0, strpos($path, DIRECTORY_SEPARATOR) );

		/* Get theme */
		$theme = wp_get_theme($folder);

		/* Check & return theme */
		if ( $theme->exists() ) {
			return $theme;
		}

		return false;
	}

	function ssp_insert_post($meta) {
		/* Empty? */
		if ( empty($meta) ) {
			return;
		}

		/* Create post */
		$post_id = wp_insert_post(
			array(
				'post_status' => 'publish',
				'post_type'   => 'ssp-firewall'
			)
		);

		/* Add meta values */
		foreach($meta as $key => $value) {
			add_post_meta(
				$post_id,
				'_ssp-firewall_' .$key,
				$value,
				true
			);
		}

		return $post_id;
	}

	function _get_postdata($args) {
		/* No POST data? */
		if ( empty($args['method']) OR $args['method'] !== 'POST' ) {
			return NULL;
		}

		/* No body data? */
		if ( empty($args['body']) ) {
			return NULL;
		}

		return $args['body'];
	}

	/* HTTP Request interseptor */
	function ssp_inspect_request( $pre, $args, $url ) {

		/* Empty url */
		if ( empty($url) ) {
			return $pre;
		}

		/* Invalid host */
		if ( ! $host = parse_url($url, PHP_URL_HOST) ) {
			return $pre;
		}

		/* Timer start */
		timer_start();

		$track = _debug_backtrace();

		/* No reference file found */
		if ( empty($backtrace['file']) ) {
			return $pre;
		}

		/* Show your face, file */
		$meta = _face_detect($backtrace['file']);

		/* Init data */
		$file = str_replace(ABSPATH, '', $backtrace['file']);
		$line = (int) $backtrace['line'];

		/* Blocked item? */
		if ( in_array($host, $blacklist['hosts']) OR in_array($file, $blacklist['files']) ) {
			
			return ssp_insert_post(
					array(
						'url'      => esc_url_raw($url),
						'code'     => NULL,
						'host'     => $host,
						'file'     => $file,
						'line'     => $line,
						'meta'     => $meta,
						'state'    => 1,
						'postdata' => _get_postdata($args)
					)
			);

		}

		return $pre;

	}

	function ssp_log_response($response, $type, $class, $args, $url)
	{
		/* Only response type */
		if ( $type !== 'response' ) {
			return false;
		}

		/* Empty url */
		if ( empty($url) ) {
			return false;
		}

		/* Validate host */
		if ( ! $host = parse_url($url, PHP_URL_HOST) ) {
			return false;
		}

		/* Backtrace data */
		$backtrace = _debug_backtrace();

		/* No reference file found */
		if ( empty($backtrace['file']) ) {
			return false;
		}

		/* Show your face, file */
		$meta = _face_detect($backtrace['file']);

		/* Extract backtrace data */
		$file = str_replace(ABSPATH, '', $backtrace['file']);
		$line = (int) $backtrace['line'];

		/* Response code */
		$code = ( is_wp_error($response) ? -1 : wp_remote_retrieve_response_code($response) );

		/* Insert CPT */
		ssp_insert_post(
			array(
				'url'      => esc_url_raw($url),
				'code'     => $code,
				'duration' => timer_stop(false, 2),
				'host'     => $host,
				'file'     => $file,
				'line'     => $line,
				'meta'     => $meta,
				'state'    => -1,
				'postdata' => _get_postdata($args)
			)
		);
	}

<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(! class_exists('SMCLeanup')){
	class SMCLeanup{
		
	    private static $_instance = null;

	    public static $plugin_slug = 'sm-cleanup';

	    private static $_user_can = 'manage_options';

	    private static $_smclOP = [];

	    private static $_smclST = [];

	    /**
	     * Constructor. Called when plugin is initialised
	     */
	    private function __construct() {
	    	if(is_admin()){
	    		add_action( 'admin_menu',array( &$this, 'smCreateMenu' ) );
				add_action( 'admin_enqueue_scripts', array( &$this, 'smEnqueueAdminScript' ) );
				add_action( 'wp_ajax_sm-act_create_css', array( &$this, 'smCreateCss' ) );
				add_action( 'wp_ajax_sm-act_compress', array( &$this, 'smUpdateCompress') );
				add_action( 'wp_ajax_sm-act_empty', array( &$this, 'smUpdateEmpty') );
	    	}else{
	    		add_action( 'wp_enqueue_scripts', array( &$this, 'smEnqueuePublicScript' ) );
	    	}
	    }

	    public static function instance(){
	    	if(is_null( self::$_instance )){
	    		self::$_instance = new self();
	    	}
	    	return self::$_instance;
	    }
	    
		public function smEnqueuePost($url){
	   		if ( !empty($url) ){
	        	$url .= ',';
	   		}
	   		$file = SMCL_PLUGIN_DIR . "assets/css/smcl-post.css";
	   		if( !file_exists( $file ) ){
    			self::$_smclST = $this->smGetOption( 'opsmcl' );
	   			$important = isset( self::$_smclOP['post']['important'] ) && self::$_smclOP['post']['important'] == 1;
    			$styles = $this->smAddImpo( self::$_smclST, $important );
	    		file_put_contents( $file, $styles);
	   		}
	   		$url .= SMCL_PLUGIN_ASSETS . "css/smcl-post.css";
		 
		    return $url;
		}

	    public function smCreateMenu(){
	    	$hook = add_menu_page(
	    		__('SM Cleanup', 'smcleanup'),
	    		__('SM Cleanup', 'smcleanup'),
	    		self::$_user_can,
	    		self::$plugin_slug,
	    		array($this, 'smManagePage'),
	    		SMCL_PLUGIN_ASSETS .'menu-icon.jpg'
	    	);

    		if( $this->smIsEditPost() ){
    			add_action( 'add_meta_boxes', array( &$this, 'smAddCleanMetaBox') );
    			add_filter( 'tiny_mce_before_init', array( &$this, 'smSettingTiny') );
    			add_filter("mce_buttons", array( &$this, 'smButtonTiny') );
    			add_filter("mce_buttons_2", array( &$this, 'smButtonTiny2') );
    			add_filter( 'mce_external_plugins', array( &$this, 'smPlugTiny') );
    			add_filter('mce_css', array( &$this, 'smEnqueuePost' ) );
    		}
    		add_action( 'admin_init', array(&$this, 'smSetting') );
	    }

	    public function smSetting(){
	    	register_setting( 'smcl-options', '_smcl-options', array( &$this, 'smSanitize') );
	    }

	    public function smSettingTiny( $settings ){
	    	if( isset( self::$_smclOP['toolbar']['btn_size'] ) ){
	    		$settings['fontsize_formats'] = trim( self::$_smclOP['toolbar']['btn_size'] );
	    	}
	    	$data_time = array( $this->smTimeFormat('date_format'),$this->smTimeFormat('date_format') .' '. $this->smTimeFormat('time_format'),$this->smTimeFormat('time_format'));
        	$settings['insertdatetime_formats'] = json_encode( $data_time );
	    	return $settings;
	    }

	    public function smButtonTiny( $buttons ){
	    	array_unshift($buttons, 'fontselect','fontsizeselect');
	    	return $buttons;
	    }

	    public function smButtonTiny2( $buttons ){
	    	array_splice( $buttons, array_search( 'forecolor', $buttons), 1, array( 'forecolor','backcolor') );
	    	array_push( $buttons, 'media','insertdate','insertdatetime');
	    	return $buttons;
	    }

	    public function smPlugTiny( ){
	    	$plugins = array('advlist','template','insertdatetime');
			$plugins_array = array();
			foreach ($plugins as $plugin ) {
			  $plugins_array[ $plugin ] = SMCL_PLUGIN_LIBS ."/plugins/{$plugin}/plugin.min.js";
			}
			return $plugins_array;
	    }
	    public function smSanitize( $input ){
	    	$new_input = array();
	    	if( isset( $input['post'] ) ){
	    		foreach( (array)$input['post'] as $k=>$ip ){
	    			switch ( $k ) {
	    				case 'empty':
	    					$new_input['post'][$k] = intval( $ip );
	    					break;
	    				case 'margin':
	    					$ip = (array)$ip;
	    					foreach( $ip as $i => $m ){
	    						$new_input['post'][$k][$i] = intval( $m );
	    					}
	    					break;
	    				case 'prefix':
	    					$ips = array_unique( $ip );
	    					if( count( $ips ) != count( $ip ) ){
	    						$ip = isset( self::$_smclOP['post']['prefix'] ) ? self::$_smclOP['post']['prefix'] : array();
	    					}
	    					if( !empty( $ip ) ){
	    						foreach( $ip as $i => $m ){
		    						$new_input['post'][$k][$i] = sanitize_html_class( $m );
		    					}
	    					}
	    					break;
	    				default:
	    					$new_input['post'][$k] = sanitize_text_field( $ip );
	    			}
	    		}
	    	}

	    	if( isset( $input['toolbar'] ) ){
	    		foreach( (array)$input['toolbar'] as $k=>$ip ){
	    			if( $k == 'btn_size'){
	    				preg_match_all('/[0-9]+(?:px|pt|rem|em|%)+/', $ip, $matches);
	    				if( isset( $matches[0] ) ){
	    					$new_input['toolbar'][$k] = implode( ' ', $matches[0]);
	    				}
	    			}else{
	    				$new_input['toolbar'][$k] = sanitize_text_field( $ip );
	    			}
	    		}
	    	}
	    	if( isset( $input['data'] ) ){
	    		foreach( (array)$input['data'] as $k=>$ip ){
	    			if( $k == 'post_type'){
	    				$new_input['data'][$k] = (array)$ip;
	    			}else{
	    				$new_input['data'][$k] = sanitize_text_field( $ip );
	    			}
	    		}
	    	}
	    	return $new_input;
	    }

	    public function smManagePage(){
		    $this->smRenderCleanup( 'Setting' );
	    }

	    public function smIsEditPost(){
	    	global $pagenow;
	    	$list_post_type = array('post','page');
	    	$post_type = 'post';
	    	if( isset( $_GET['post_type'] ) ){
	    		$post_type = $_GET['post_type'];
	    	}
	    	self::$_smclOP = $this->smGetOption('options');
	    	if( isset( self::$_smclOP['data']['post_type'] ) ){
	    		$list_post_type = (array)self::$_smclOP['data']['post_type'];
	    	}

	    	return in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) && in_array( $post_type, $list_post_type );
	    }

	    public function smExistPost( $post_id ){
	    	global $wpdb;
	    	$sql = $wpdb->prepare( "SELECT count(*) FROM $wpdb->posts WHERE ID = %d", $post_id);
	    	return $wpdb->get_var( $sql );
	    }

		public function smEnqueuePublicScript(){
		   	$auto = 1;
		   	if( isset( self::$_smclOP['post']['copy'] ) ){
		   		$auto = self::$_smclOP['post']['copy'];
		   	}
		   	if( $auto ){
		   		wp_enqueue_style( 'smclean-post', SMCL_PLUGIN_ASSETS .'css/smcl-post.css', array(), SMCL_V );
		   	}
	    }

	    public function smEnqueueAdminScript( $hook ){
	    	wp_enqueue_style( "smcl-admin", SMCL_PLUGIN_ASSETS ."css/smcl-admin.css" );
	    	wp_enqueue_script("smcl-admin", SMCL_PLUGIN_ASSETS ."js/smcl-admin.js",array(),false,true);
	    	if( $this->smIsEditPost()){
	    		global $post;
	    		wp_enqueue_script("smcl-post", SMCL_PLUGIN_ASSETS ."js/smcl-post.min.js",array(),false,true);
	    		$edit_margin = 1;
	    		$list_exclude = array( 'EM','DEL','STRONG' );
	    		$list_include = array();
	    		$text = 'left';
	    		$class_name = array(
					'color'      =>'color-',
					'bgk'        => 'bg-',
					'align'      =>'text-',
					'transform'  =>'txt-',
					'padding'    =>'pd-left-',
					'decoration' => 'u-',
					'top'        => 'top-',
					'family'     => 'family-',
					'size'       => 'size-',
					'height'     => 'hei-',
					'width'      => 'wid-',
					'list_type'  => 'list-'
	    		);
	    		$sm_margin = "{'P': 10, 'H1':30, 'H2' : 25, 'H3':20, 'H4':15, 'H5':5}";
	    		$pot = isset( self::$_smclOP['post'] ) ?  self::$_smclOP['post'] : array();
	    		if( isset( $pot['margin'] ) ){
	    			$sm_margin = $pot['margin'];
	    		}
	    		if( isset( $pot['prefix'] ) ){
	    			$class_name = (array)$pot['prefix'];
	    			$class_name = array_map( array( &$this, 'smSetPrefix' ), $class_name );
	    		}

	    		if( isset( $pot['exclude'] )  ){
	    			$exclude = str_replace(' ', '', $pot['exclude'] );
	    			$exclude = explode( ',', strtoupper( $exclude ) );
	    			$list_include = array_diff( $list_exclude, $exclude );
	    			$list_exclude = $exclude;
	    		}
	    		if( isset( $pot['empty'] ) ){
	    			$edit_margin = intval( $pot['empty'] );
	    		}
	    		$obj = array(
	    			'ajax_url' => admin_url( 'admin-ajax.php' ),
	    			'sm_nonce' =>wp_create_nonce('act_smcl-nonce'),
	    			'id' =>$post->ID,
	    			'edit_margin' => $edit_margin,
	    			'list_exclude' => $list_exclude,
	    			'list_include' => $list_include,
	    			'sm_margin' => $sm_margin,
	    			'class_name' => $class_name,
	    			'text' => $text
	    		);
	    		wp_localize_script( 'smcl-post', '_SMCLSetting_', $obj );
	    	}
	    }

	    public function smUpdateCompress(){
	    	global $wpdb;
	    	$nonce = $_POST['nonce'];
	    	if( !wp_verify_nonce($nonce, 'act_smcl-nonce') ){
	    		wp_die( 'Insufficient privileges!' );
	    	}
	    	$post_id = intval( $_POST['post'] );
	    	$content = $_POST['content'];
	    	if( current_user_can('edit_posts', $post_id) ){
	    		$post_id = $this->smUpdateOrigin( $post_id, $content );
	    		echo $post_id;
	    	}
	    	wp_die();
	    }

	    public function smUpdateOrigin( $id, $content ){
	    	
	    	$compress = $this->smContentSize( get_post_field( 'post_content', $id, 'edit') );
			$this->smUpdateMeta( $id, 'origin', $compress );
			$post_args = array(
    			'ID' => $id,
    			'post_content' => $content
    		);
    		$id = wp_update_post( $post_args );
			return $id;
	    }

	    public function smUpdateEmpty(){
	    	global $wpdb;
	    	$nonce = $_POST['nonce'];
	    	if( !wp_verify_nonce($nonce, 'act_smcl-nonce') ){
	    		wp_die( 'Insufficient privileges!' );
	    	}
	    	$post_id = intval( $_POST['post'] );
	    	wp_die();
	    }

	    public function smCreateCss(){
	    	global $wpdb;
	    	$nonce = $_POST['nonce'];
	    	if( !wp_verify_nonce($nonce, 'act_smcl-nonce') ){
	    		wp_die( 'Insufficient privileges!' );
	    	}
	    	$post_id = intval( $_POST['post'] );
	    	$clas = '';
	    	if(isset( $_POST['clas'] ) ){
	    		$clas = $_POST['clas'];
	    	}
	    	if( current_user_can('edit_posts', $post_id) ){
	    		if( isset( self::$_smclOP['post']['auto'] ) && self::$_smclOP['post']['auto'] == 1 ){
	    			$content = $_POST['content'];
	    			$this->smUpdateOrigin( $post_id, $content );
	    		}
	    		if( !empty( $clas ) ){
	    			$important = isset( self::$_smclOP['post']['important'] ) && self::$_smclOP['post']['important'] == 1;
	    			$opsmcl = $this->smUpdateSMCL( $post_id, $clas, $important );
	    			if( $opsmcl ){
		    			$file = file_put_contents( SMCL_PLUGIN_DIR .'assets/css/smcl-post.css', $opsmcl );
		    			if( $file ){
		    				echo 'updated';
		    			}else{
		    				echo 'errors';
		    			}
		    		}else{
		    			echo true;
		    		}
	    		}else{
	    			echo true;
	    		}
	    	}

	    	wp_die();
	    }

	    public function smAddCleanMetaBox(){
	    	if( !current_user_can('edit_posts') )
	    		return;
	    	$screens = array( 'post', 'page' );
	    	if( isset( self::$_smclOP['data']['post_type'] ) ){
	    		$screens = self::$_smclOP['data']['post_type'];
	    	}
			foreach ( $screens as $screen ) {
				add_meta_box(
					'smcl_mtb-type',
					__( 'SM Cleanup', 'smcleanup' ),
					array( &$this, 'smCleanMetaboxFunc' ),
					$screen
				);
			}
	    }

	    public function smUpdateSMCL( $id, $val, $important ){
	    	$opsmcl = $this->smGetOption( 'opsmcl' );
	    	// new style for post
	    	if( empty( $opsmcl ) ){
	    		$opsmcl = $newstyle = $val;
	    	}else{
	    		$newstyle = array_diff_key( (array)$val, (array)$opsmcl );
	    		$opsmcl = wp_parse_args( $val, $opsmcl );
	    	}
	    	$style = $this->smGetMeta( $id, 'newstyle' );
	    	if( $style ){
	    		$newstyle = wp_parse_args( $newstyle, (array)$style );
	    	}
    		if( !empty( $newstyle ) && $newstyle != [""] ){
    			$this->smUpdateMeta( $id, 'newstyle', $newstyle );
    		}
    		if( $this->smUpdateOption('opsmcl', $opsmcl ) ){
    			self::$_smclST = $opsmcl;
    			return $this->smAddImpo( $opsmcl, $important );
    		}
    		return false;
	    }

	    public function smUpdateMeta( $post_id, $key, $val ){
	    	$key = "_smcl-meta-{$key}";
	    	return update_post_meta( $post_id, $key, $val );
	    }

	    public function smGetMeta( $post_id, $key ){
	    	$key = "_smcl-meta-{$key}";
	    	return get_post_meta( $post_id, $key, true );
	    }

	    public function smDelMeta( $post_id, $key ){
	    	$key = "_smcl-meta-{$key}";
	    	return delete_post_meta( $post_id, $key );
	    }

	    public function smUpdateOption( $key, $val ){
	    	$key = "_smcl-{$key}";
	    	return update_option( $key, $val );
	    }

	    public function smGetOption( $key ){
	    	$key = "_smcl-{$key}";
	    	return get_option( $key );
	    }	    

	    public function smAddImpo( $objstyle, $important ){
	    	if(empty( $objstyle ))
	    		return;
	    	$styles = '';
			foreach( (array)$objstyle as $cl=> $val ){
				if( !empty( $val )){
					$styles .= ".{$cl}\{{$val}\}";
				}
    		}
    		if( $important ){
    			$styles = str_replace( '!important', '', $style );
	    		$styles = str_replace('}', '!important}', $style );
    		}
			return str_replace( '\\', '', $styles );
	    }

	    public function smCleanMetaboxFunc($post){
	    	$post_id = $post->ID;
    		$origin = $this->smGetMeta( $post_id, 'origin' );
    		$newstyle =$this->smGetMeta( $post_id, 'newstyle' );
    		$important = isset( self::$_smclOP['post']['important'] ) && self::$_smclOP['post']['important'] == 1;
    		$newstyle = esc_attr( $this->smAddImpo( $newstyle, $important ) );
	    	?>
	    	<div class="smcl-metabox">
	    		<table id="smcl-result" class="smcl-report hidden">
	    			<caption><?php _e('COMPRESS STATEMENT','smclean');?></caption>
	    			<thead>
	    				<tr>
	    					<th><?php _e('Origin');?></th>
	    					<th><?php _e('Compress');?></th>
	    					<th><?php _e('Styles');?></th>
	    					<th><?php _e('Saved without styles');?></th>
	    					<th><?php _e('Saved');?></th>
	    					<th><?php _e('Saved Later(for 10 post reuse these styles)');?></th>
	    				</tr>
	    			</thead>
	    			<tbody>
	    				<tr id="smcl-body-result">
	    					<td><?php if( $origin ){echo $origin; }else{ echo $this->smContentSize( $post->post_content ); } ?> Bytes</td>
	    					<td></td>
	    					<td><?php echo $this->smContentSize( $newstyle );?></td>
	    					<td></td>
	    					<td></td>
	    					<td></td>
	    				</tr>
	    			</tbody>
	    		</table>
	    		<a href="#" id="smcl-optimize" class="button button-primary"><?php _e('Save compress code to my post', 'smcleanup');?></a>
	    		<p class="more-cleaner hidden">We need save to post to get cleaner code</p>
	    	</div>
	    	<h3>Compress code:</h3>
	    	<?php
	    	$dev = true;
	    	if( $dev ){
				wp_editor( '', 'smcl-editor-post', array('editor_height'=>250, 'media_buttons'=>false, 'editor_css'=>'<style>#wp-smcl-editor-post-editor-container .mce-toolbar-grp,#wp-smcl-editor-post-editor-container .quicktags-toolbar{display:none!important}</style>') );
	    	?>
	    	<h3>New style</h3>
	    		<textarea id="smcl-editor-css" name=""><?php echo $newstyle;?></textarea>
	    	<?php
	    	}
	    }

	    public function smSetPrefix( $v ){
	    	return $v.'-';
	    }

	    public function smContentSize( $content ){
	    	if( gettype( $content )!='string'){
	    		return 0;
	    	}
	    	if (function_exists('mb_strlen')) {
			    $content = mb_strlen($content, '8bit');
			} else {
			    $content = strlen($content);
			}
			return intval( $content );
	    }

	    public function smTimeFormat( $time ){
	    	$time = get_option( $time );
    		$wp_date_format = array('d','j','S','I','D','m','n','F','M','Y','y','a','A','g','h','i','s');
    		$tiny_date_format = array('%d','%d','%d','%A','%a','%m','%m','%B','%b','%Y','%y','%p','%p','%I','%M','%S');
    		return str_replace( $wp_date_format, $tiny_date_format, $time);
	    }

	    public function smRenderCleanup( $action, $slide = null ){
	    	$sm_action = sanitize_title($action);
	    	?>
	    	<div id="smclean-box" class="wrap">
	    		<h1><?php _e(  $action .' Cleaner', 'smclean' );?></h1>
	    		
	    		<form id="smcl-form-<?php echo $sm_action;?>"  method="post" action="options.php">
            		<?php
            			settings_fields( 'smcl-options' );
            			do_settings_sections( 'smcl-options' );
            			$empty = 1;
            			$copy = $important = $auto = 0;
            			$prefix = $margin = '';
            			$H1 = 30;
            			$H2 = 25;
            			$H3 = 20;
            			$H4 = 15;
            			$H5 = 5;
            			$P = 10;
            			$exclude = 'em, del, strong';
            			$align = 'text';
            			$padding = 'pd-left';
            			$decoration = 'u';
            			$color = 'color';
            			$bgk = 'bg';
            			$family = 'family';
            			$size = 'size';
            			$align = 'align';
            			$transform = 'txt';
            			$top = 'top';
            			$hei = 'hei';
            			$wid = 'wid';
            			$text = 'left';
            			$btn_size = '8px 10px 12px 13px 14px 16px 18px 20px';
            			$list_type = 'list';
            			$post_type = array('post','page');
            			$data_time = '"'. $this->smTimeFormat('date_format') .'", "'. $this->smTimeFormat('date_format') .' '. $this->smTimeFormat('time_format') .'", "'. $this->smTimeFormat('time_format') .'"';
				    	if(isset(  self::$_smclOP['post'] ) && !empty( self::$_smclOP['post']) ){
				    		extract( self::$_smclOP['post'] );
				    		if(is_array( $prefix ) ){
				    			extract( $prefix );
				    		}
				    		if( is_array( $margin ) ){
				    			extract( $margin );
				    		}
				    	}
				    	if( isset( self::$_smclOP['toolbar']) && !empty( self::$_smclOP['toolbar']) ){
				    		extract( self::$_smclOP['toolbar'] );
				    	}
				    	if( isset( self::$_smclOP['data']) && !empty( self::$_smclOP['data']) ){
				    		extract( self::$_smclOP['data'] );
				    	}
				    	$file = SMCL_PLUGIN_DIR . "assets/css/smcl-post.css";
	   					if( file_exists( $file ) ){
				    		$styles = file_get_contents( SMCL_PLUGIN_DIR.'assets/css/smcl-post.css' );
				    	}else{
    						$styles = $this->smAddImpo( self::$_smclST, $important );
				    	}
				    	// statement
				    	$origin = $compress = $cp_styles = 0;

			    		$args = array(
				    		'posts_per_page' => -1,
				    		'post_type' => $post_type,
				    		'meta_query' => array(
								array(
									'key'     => '_smcl-meta-origin',
									'value'   => 0,
									'compare' => '>',
								)
							)
				    	);
				    	$p_comp = new WP_Query( $args );
				    	if( $p_comp->have_posts() ){
				    		foreach( $p_comp->posts as $p ){
				    			$origin += $this->smGetMeta( $p->ID, 'origin' );
				    			$compress += $this->smContentSize( $p->post_content );
				    			$newstyle = $this->smAddImpo( $this->smGetMeta( $p->ID, 'newstyle' ), $important );
				    			$cp_styles += $this->smContentSize( $newstyle );
				    		}
				    	}
				    	
				    ?>
				    	<table class="widefat smcl-report">
			    			<caption><?php _e('COMPRESS STATEMENT','smclean');?></caption>
			    			<thead>
			    				<tr>
			    					<th><?php _e('Origin');?></th>
			    					<th><?php _e('Compress');?></th>
			    					<th><?php _e('Styles');?></th>
			    					<th><?php _e('Saved without styles');?></th>
			    					<th><?php _e('Saved');?></th>
			    				</tr>
			    			</thead>
			    			<tbody>
			    				<tr id="smcl-body-result">
			    					<td><?php echo $origin; ?> Bytes</td>
			    					<td><?php echo $compress;?> Bytes</td>
			    					<td><?php echo $cp_styles;?> Bytes</td>
			    					<td><?php echo ( $origin - $compress );?> Bytes</td>
			    					<td><?php echo ( $origin - $compress - $cp_styles );?> Bytes</td>
			    				</tr>
			    			</tbody>
			    		</table>
					    <table class="form-table">
					    	<tr><th colspan="2"><h2>Main setting</h2></th></tr>
					    	<tr>
						        <th scope="row"><?php _e( 'Automatic update compress to post ?', 'smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="checkbox" <?php checked( $auto, 1 );?> name="_smcl-options[post][auto]" value="1" /> Yes
						        	</label>
						        	<p class="description"><?php _e("Automatic update compress code to post instead of update compress to area SM Cleanup option.<br/><span style='color:#f00'>** Make sure that you've understood this action, please leave uncheck and try demo if you want everything to be safe!</span>");?></p>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Choose post type', 'smcleanup');?></th>
						        <td>
						        	<table>
						        		<tr>
						        			<?php $list_post_type = array_merge( array('post'=>'Post','page'=>'Page'), get_post_types( array('public'=>true, '_builtin'=>false) ) );
						        			foreach( $list_post_type as $k=>$p ):
						        			?>
						        				<td><label><input type="checkbox" <?php if( in_array( $k, $post_type )){echo 'checked';};?> name="_smcl-options[data][post_type][]" value="<?php echo $k;?>" /> <?php echo $p;?></label></td>
						        			<?php endforeach;?>
						        		</tr>
						        	</table>
						        	<p class="description"><?php _e('Set lists post type use SM Cleanup');?></span></p>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e('Empty/blank element','smcleanup');?></th>
						        <td class="smcl-more">
						        	<label class="sm-block">
						        		<input type="radio" class="show-more" <?php checked( $empty, 1);?> name="_smcl-options[post][empty]" value="1" />
						        		<?php _e( 'Remove blank tag and add margin', 'smcleanup' );?>
						        		<p class="description"><?php _e( "Ex: We have 2 blank H1 tag before P tag -> we\'ll remove blank H1, and add margin-top = margin H1 x 2","smcleanup");?></p>
						        	</label>
						        	<label class="sm-block">
						        		<input type="radio" <?php checked( $empty, -1);?> name="_smcl-options[post][empty]" value="-1" />
						        		<?php _e( 'Remove blank tag','smcleanup');?>
						        		<p class="description"><?php _e("We'll only remove all blank tag and don't add margin","smcleanup");?></p>
						        	</label>
						        	<label class="sm-block">
						        		<input type="radio" <?php checked( $empty, 0);?> name="_smcl-options[post][empty]" value="0" />
						        		<?php _e( "Keep blank tag","smcleanup");?>
						        	</label>
						        </td>
					        </tr>
					        <tr <?php if( $empty != 1 ){echo 'class="hidden"';}?>>
					        	<th scope="row">
					        		<?php _e("Set margin for blank tag (px)", "smcleanup");?>
					        		<p class="description"><?php _e("Each this blank tag, We look it like margin (margin-top)</p>", "smcleanup");?>
					        	</th>
					        	<td>
					        		<table class="widefat">
					        			<thead>
					        				<tr>
					        					<th>H1</th>
					        					<th>H2</th>
					        					<th>H3</th>
					        					<th>H4</th>
					        					<th>H5</th>
					        					<th>P</th>
					        				</tr>
					        			</thead>
					        			<tbody>
					        				<tr>
					        					<td><input type="text" name="_smcl-options[post][margin][H1]" value="<?php echo intval( $H1 );?>" size="3"></td>
					        					<td><input type="text" name="_smcl-options[post][margin][H2]" value="<?php echo intval( $H2 );?>" size="3"></td>
					        					<td><input type="text" name="_smcl-options[post][margin][H3]" value="<?php echo intval( $H3 );?>" size="3"></td>
					        					<td><input type="text" name="_smcl-options[post][margin][H4]" value="<?php echo intval( $H4 );?>" size="3"></td>
					        					<td><input type="text" name="_smcl-options[post][margin][H5]" value="<?php echo intval( $H5 );?>" size="3"></td>
					        					<td><input type="text" name="_smcl-options[post][margin][P]" value="<?php echo intval( $P );?>" size="3"></td>
					        				</tr>
					        			</tbody>
					        		</table>
					        	</td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'List exclude tag', 'smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][exclude]" value="<?php echo esc_attr( $exclude );?>" />
						        		<p class="description"><?php _e('We keep this by tag, ex: kepp &lt;strong><strong>this is strong</strong>&lt;/strong> instead of convert to class &lt;span class="font-bold"><b>this is strong</b>&lt;/span>','smcleanup');?></p>
						        	</label>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e('List prefix class name (unique)','smcleanup');?>
						        <p class="description"><?php _e('prefix-property ex: text-left, pd-left-30, ...','smcleanup');?>
						        </p>
						        </th>
						        <td>
						        <table class="widefat">
						        	<thead>
						        		<tr>
						        			<th>Color</th>
						        			<th>Background color</th>
						        			<th>Font family</th>
						        			<th>Font size</th>
						        			<th>Text align</th>
						        			<th>Padding left</th>
						        		</tr>
					        		</thead>
					        		<tr>
					        			<td><input type="text" name="_smcl-options[post][prefix][color]" value="<?php echo esc_attr( $color );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][bgk]" value="<?php echo esc_attr( $bgk );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][family]" value="<?php echo esc_attr( $family );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][size]" value="<?php echo esc_attr( $size );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][align]" value="<?php echo esc_attr( $align );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][padding]" value="<?php echo esc_attr( $padding );?>" /></td>
					        		</tr>
					        		<thead>
						        		<tr>
						        			<th>Text decoration</th>
						        			<th>Text transform</th>
						        			<th>Margin top</th>
						        			<th>Height</th>
						        			<th>Width</th>
						        			<th>List type</th>
						        		</tr>
					        		</thead>
					        		<tr>
					        			<td><input type="text" name="_smcl-options[post][prefix][decoration]" value="<?php echo esc_attr( $decoration );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][transform]" value="<?php echo esc_attr( $transform );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][top]" value="<?php echo esc_attr( $top );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][hei]" value="<?php echo esc_attr( $hei );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][wid]" value="<?php echo esc_attr( $wid );?>" /></td>
					        			<td><input type="text" name="_smcl-options[post][prefix][list_type]" value="<?php echo esc_attr( $list_type );?>" /></td>
					        		</tr>
						        </table>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Remove text-align:left/right ?', 'smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="radio" <?php checked( $text, 'left');?> name="_smcl-options[post][text]" value="left" /> Left
						        	</label>
						        	<label class="sm-block">
						        		<input type="radio"  <?php checked( $text, 'right');?> name="_smcl-options[post][text]" value="right" /> Right
						        	</label>
						        	<label class="sm-block">
						        		<input type="radio"  <?php checked( $text, 'none');?> name="_smcl-options[post][text]" value="none" /> None<br/>
						        		<p class="description"><?php _e('By default we use almost text left if direction is ltr, and right if direction is rtl, so we can remove it. But with special post you need it, please set again this option for this post, and should revert default after it done.','smcleanup');?></p>
						        	</label>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Using !important with CSS ?', 'smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="checkbox" <?php checked( $important, 1 );?> name="_smcl-options[post][important]" value="1" /> Yes
						        	</label>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e('I want copy style to my file ?','smcleanup');?></th>
						        <td class="smcl-more">
						        	<label class="sm-block">
						        		<input type="radio" class="show-more" <?php checked( $copy, 0);?> name="_smcl-options[post][copy]" value="0" />
						        		Yes
						        	</label>
						        	<label class="sm-block">
						        		<input type="radio" <?php checked( $copy, 1);?> name="_smcl-options[post][copy]" value="1" />
						        		<?php _e('No - creat new file for me.','smcleanup');?>
						        	</label>
						        </td>
					        </tr>
					        <tr <?php if( $copy == 1 ){echo 'class="hidden"';}?>>
						        <th scope="row"><?php _e('Copy css to my file','smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<textarea id="smcl-editor-css" rows="10" cols="80" name="" placeholder="All yourself style here..."><?php echo esc_attr( $styles );?></textarea>
						        	</label>
						        </td>
					        </tr>
					        <tr>
					        	<th colspan="2"><h2 class="text-center"><?php _e('My Toolbar','smcleanup');?></h2></th>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e('Font size','smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="text" size="50" name="_smcl-options[toolbar][btn_size]" value="<?php echo esc_attr( $btn_size );?>" />
						        		<p class="description"><?php _e('Add your custom font-size separated by white space [ ], we accept units (px | pt | em | rem | %)','smcleanup');?></p>
						        	</label>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e('Date time','smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="text" size="50" readonly="true" value="<?php echo esc_attr( $data_time );?>" />
						        		<p class="description"><?php echo sprintf( __('Please set date/time format at Dashboard -> <a target="_blank" href="%s" title="General setting">Settings</a>','smcleanup'), admin_url('options-general.php') );?></p>
						        	</label>
						        </td>
					        </tr>

					    </table>
					<?php
				    	submit_button(); ?>
	    		</form>
	    	</div>
	    	<?php
	    }	    
	}
	$smclean = SMCLeanup::instance();
}
 ?>
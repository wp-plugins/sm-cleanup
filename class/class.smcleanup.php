<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(! class_exists('SMCLeanup')){
	class SMCLeanup{
		
	    private static $_instance = null;

	    public static $domain = 'smcleanup';

	    public static $plugin_slug = 'sm-cleanup';

	    private static $_user_can = 'manage_options';

	    private static $_smclOP;

	    /**
	     * Constructor. Called when plugin is initialised
	     */
	    private function __construct() {
	    	if(is_admin()){
	    		add_action( 'admin_menu',array( &$this, 'smCreateMenu' ) );
				add_action( 'add_meta_boxes', array( &$this, 'smAddCleanMetaBox') );
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
	   			self::$_smclOP = $this->smGetOption( 'options' );
    			$opstyle = $this->smGetOption( 'opsmcl' );
	   			$important = isset( self::$_smclOP['post']['important'] ) && self::$_smclOP['post']['important'] == 1;
    			$styles = $this->smAddImpo( $opstyle, $important );
	    		file_put_contents( $file, $styles);
	   		}
	   		$url .= SMCL_PLUGIN_ASSETS . "css/smcl-post.css";
		 
		    return $url;
		}

	    public function smCreateMenu(){
	    	$hook = add_menu_page(
	    		__('SM Cleanup', self::$domain),
	    		__('SM Cleanup', self::$domain),
	    		self::$_user_can,
	    		self::$plugin_slug,
	    		array($this, 'smManagePage'),
	    		SMCL_PLUGIN_ASSETS .'menu-icon.jpg'
	    	);

    		if( $this->smIsEditPost() ){
    			add_filter('mce_css', array( &$this, 'smEnqueuePost' ) );
    		}
    		add_action( 'admin_init', array(&$this, 'smSetting') );
	    }

	    public function smSetting(){
	    	register_setting( 'smcl-options', '_smcl-options', array( &$this, 'smSanitize') );
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
	    					$ip = array_diff( $ip );
	    					foreach( $ip as $i => $m ){
	    						$new_input['post'][$k][$i] = sanitize_title( $m );
	    					}
	    					break;
	    				default:
	    					$new_input['post'][$k] = sanitize_text_field( $ip );
	    					break;
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
	    	return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	    }

	    public function smExistPost( $post_id ){
	    	global $wpdb;
	    	$sql = $wpdb->prepare( "SELECT count(*) FROM $wpdb->posts WHERE ID = %d", $post_id);
	    	return $wpdb->get_var( $sql );
	    }

		public function smEnqueuePublicScript(){
			self::$_smclOP = $this->smGetOption( 'options' );
			$pot = self::$_smclOP['post'];
		   	$auto = 1;
		   	if( isset( $pot['copy'] ) ){
		   		$auto = $pot['copy'];
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
	    		self::$_smclOP = $this->smGetOption( 'options' );
	    		$edit_margin = 1;
	    		$list_exclude = array( 'EM','DEL','STRONG' );
	    		$list_include = array();
	    		$text = 'left';
	    		$class_name = array(
	    			'color'=>'color-',
	    			'align' =>'text-',
	    			'transform' =>'txt-',
	    			'padding'=>'pd-left-',
	    			'decoration' => 'u-',
	    			'top' => 'top-',
	    			'family'=> 'family-',
	    			'size' => 'size-'
	    		);
	    		$sm_margin = "{'P': 10, 'H1':30, 'H2' : 25, 'H3':20, 'H4':15, 'H5':5}";
	    		$pot = isset( self::$_smclOP['post'] ) ?  self::$_smclOP['post'] : '';
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
	    		$this->smUpdateOrigin( $post_id, $content );
	    		$post_args = array(
	    			'ID' => $post_id,
	    			'post_content' => $content
	    		);
	    		$post_id = wp_update_post( $post_args );
	    		echo $post_id;
	    	}
	    	wp_die();
	    }

	    public function smUpdateOrigin( $id, $content, $op = null ){
	    	if( $this->smGetMeta( $id, 'origin' ) ){
	    		return;
	    	}
	    	if( $op == null ){
	    		$op = $this->smGetOption( 'options ');
	    	}
			$compress = $this->smContentSize( get_post_field( 'post_content', $id, 'edit') );
			$this->smUpdateMeta( $id, 'origin', $compress );
			$post_type = get_post_type( $id );
			if( $post_type ){
				$op['data']['post_type'] = isset( $op['data']['post_type'] ) ? wp_parse_args( array( $post_type ), (array)$op['data']['post_type'] ) : array( $post_type );
			}
			return $this->smUpdateOption( 'options', $op, 'no' );
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
	    		self::$_smclOP = $this->smGetOption('options');

	    		if( isset( self::$_smclOP['post']['auto'] ) && self::$_smclOP['post']['auto'] == 1 ){
	    			$content = $_POST['content'];
	    			$this->smUpdateOrigin( self::$_smclOP, $post_id, $content );
		    		$post_args = array(
		    			'ID' => $post_id,
		    			'post_content' => $content
		    		);
		    		$post_id = wp_update_post( $post_args );
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

			foreach ( $screens as $screen ) {
				add_meta_box(
					'smcl_mtb-type',
					__( 'SM Cleanup', self::$domain ),
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
    		if( $this->smUpdateOption('opsmcl', $opsmcl, 'no' ) ){
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

	    public function smUpdateOption( $key, $val, $auto='yes' ){
	    	$key = "_smcl-{$key}";
	    	return update_option( $key, $val, $auto );
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
	    	self::$_smclOP = $this->smGetOption('options');
	    	$post_id = $post->ID;
	    	$top = 'top-';
    		if( isset( self::$_smclOP['post']['prefix']['top'])){
    			$top = self::$_smclOP['post']['prefix']['top'];
    		}
    		$origin = $this->smGetMeta( $post_id, 'origin' );
    		$newstyle =$this->smGetMeta( $post_id, 'newstyle' );
    		$important = isset( self::$_smclOP['post']['important'] ) && self::$_smclOP['post']['important'] == 1;
    		$newstyle = esc_attr( $this->smAddImpo( $newstyle, $important ) );
	    	?>
	    	<div class="smcl-metabox">
	    		<table id="smcl-result" class="hidden">
	    			<caption><?php _e('Statement','smclean');?></caption>
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

	    public function smRenderCleanup( $action, $slide = null ){
	    	$sm_action = sanitize_title($action);
	    	?>
	    	<div id="smclean-box" class="wrap">
	    		<h2><?php _e(  $action .' Cleaner', 'smclean' );?></h2>
	    		<form id="smcl-form-<?php echo $sm_action;?>"  method="post" action="options.php">
            		<?php
            			settings_fields( 'smcl-options' );
            			do_settings_sections( 'smcl-options' );
            			self::$_smclOP = $this->smGetOption('options');
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
            			$family = 'family';
            			$size = 'size';
            			$align = 'align';
            			$transform = 'txt';
            			$top = 'top';
            			$text = 'left';
				    	if(isset(  self::$_smclOP['post'] ) && !empty( self::$_smclOP['post']) ){
				    		$smPost = self::$_smclOP['post'];
				    		extract( $smPost );
				    		if(is_array( $prefix ) ){
				    			extract( $prefix );
				    		}
				    		if( is_array( $margin ) ){
				    			extract( $margin );
				    		}
				    	}
				    	$file = SMCL_PLUGIN_DIR . "assets/css/smcl-post.css";
				    	$important = isset( $smPost['important'] ) && $smPost['important'] == 1;
	   					if( file_exists( $file ) ){
				    		$styles = file_get_contents( SMCL_PLUGIN_DIR.'assets/css/smcl-post.css' );
				    	}else{
				    		$opstyle = $this->smGetOption( 'opsmcl' );
    						$styles = $this->smAddImpo( $opstyle, $important );
				    	}

				    	// statement
				    	$origin = $compress = $cp_styles = 0;
			    		$args = array(
				    		'posts_per_page' => -1,
				    		'post_type' => self::$_smclOP['data']['post_type'],
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
				    	<table id="smcl-result">
			    			<caption><?php _e('Statement','smclean');?></caption>
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
					    	<tr>
						        <th scope="row"><?php _e( 'Automatic update compress to post ?', 'smcleanup');?></th>
						        <td>
						        	<label class="sm-block">
						        		<input type="checkbox" <?php checked( $auto, 1 );?> name="_smcl-options[post][auto]" value="1" /> Yes
						        	</label>
						        	<p class="description">Automatic update compress code to post instead of update compress to area SM Cleanup option.<br/><span style="color:#f00">** Make sure that you've understood this action, please leave uncheck and try demo if you want everything to be safe!</span></p>
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
					        		<label class="sm-block">
						        		H1 <input type="text" name="_smcl-options[post][margin][H1]" value="<?php echo intval($H1);?>" size="3" />
						        	</label>
						        	<label class="sm-block">
						        		H2 <input type="text" name="_smcl-options[post][margin][H2]" value="<?php echo intval( $H2 );?>" size="3" />
						        	</label>
						        	<label class="sm-block">
						        		H3 <input type="text" name="_smcl-options[post][margin][H3]" value="<?php echo intval( $H3 );?>" size="3" />
						        	</label>
						        	<label class="sm-block">
						        		H4 <input type="text" name="_smcl-options[post][margin][H4]" value="<?php echo intval( $H4 );?>" size="3" />
						        	</label>
						        	<label class="sm-block">
						        		H5 <input type="text" name="_smcl-options[post][margin][H5]" value="<?php echo intval( $H5 );?>" size="3" />
						        	</label>
						        	<label class="sm-block">
						        		P &nbsp;&nbsp; <input type="text" name="_smcl-options[post][margin][P]" value="<?php echo intval( $P );?>" size="3" />
						        	</label>
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
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][color]" value="<?php echo esc_attr( $color );?>" /> <?php _e('for color','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][family]" value="<?php echo esc_attr( $family );?>" /> <?php _e('for font-family','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][size]" value="<?php echo esc_attr( $size );?>" /> <?php _e('for font-size','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][align]" value="<?php echo esc_attr( $align );?>" /> <?php _e('for text-align','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][padding]" value="<?php echo esc_attr( $padding );?>" /> <?php _e('for padding-left','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][decoration]" value="<?php echo esc_attr( $decoration );?>" /> <?php _e('for text-decoration','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][transform]" value="<?php echo esc_attr( $transform );?>" /> <?php _e('for text-transform','smcleanup');?>
						        	</label>
						        	<label class="sm-block">
						        		<input type="text" name="_smcl-options[post][prefix][top]" value="<?php echo esc_attr( $top );?>" /> <?php _e('for margin-top','smcleanup');?>
						        	</label>
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
						        		<textarea rows="10" cols="80" name="" placeholder="All yourself style here..."><?php echo esc_attr( $styles );?></textarea>
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
<?php

/*
Plugin Name: Live Edit
Plugin URI: http://www.elliotcondon.com/
Description: Edit the title, content and any ACF fields from the front end of your website!
Version: 1.0.4
Author: Elliot Condon
Author URI: http://www.elliotcondon.com/
License: GPL
Copyright: Elliot Condon
*/

$live_edit = new live_edit();

class live_edit
{ 
	var $dir,
		$path,
		$version,
		$data;
	
	
	/*
	*  Constructor
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 23/06/12
	*/
	
	function __construct()
	{

		// vars
		$this->dir = plugins_url('',__FILE__);
		$this->path = plugin_dir_path(__FILE__);
		$this->version = '1.0.4';
		$this->defaults = array(
			'panel_width'	=>	600,
		);
		
		foreach( $this->defaults as $k => $v )
		{
			$db_v = get_option('live_edit_' . $k, $v);
			
			if( $db_v )
			{
				$v = $db_v;
			}
			
			$this->data[ $k ] = $v;
		}
		
		
		// set text domain
		load_plugin_textdomain('live-edit', false, basename(dirname(__FILE__)).'/lang' );
		
		
		// actions
		add_action('init', array($this,'init'));
		
		
		return true;
	}
	
	
	/*
	*  init
	*
	*  @description: 
	*  @created: 7/09/12
	*/
	
	function init()
	{
		// must be logged in
		if( is_user_logged_in() )
		{
			// actions
			add_action('admin_menu', array($this,'admin_menu'));
			add_action('admin_head', array($this,'admin_head'));
			add_action('wp_print_scripts', array($this,'wp_print_scripts'));
			add_action('wp_head', array($this,'wp_head'));
			add_action('wp_footer', array($this,'wp_footer'));
			add_action('wp_ajax_live_edit_update_width', array($this, 'ajax_update_width'));
		}
	}
	
	
	/*
	*  admin_head
	*
	*  @description:
	*  @since 1.0.0
	*  @created: 25/07/12
	*/
	
	function admin_head()
	{
		echo '<style type="text/css">#menu-settings a[href="options-general.php?page=live-edit-panel"] { display:none; }</style>';
	}
	
	/*
	*  admin_menu
	*
	*  @description:
	*  @since 1.0.0
	*  @created: 25/07/12
	*/
	
	function admin_menu()
	{
		$slug = add_options_page(__("Live Edit Panel",'le'), __("Live Edit Panel",'le'), 'edit_posts', 'live-edit-panel', array($this, 'view_panel'));
		
		// actions
		add_action('admin_print_scripts-'.$slug, array($this, 'admin_print_scripts_page'));
		add_action('admin_print_styles-'.$slug, array($this, 'admin_print_styles_page'));
		add_action('admin_head-'.$slug, array($this,'admin_head_page'));
	}
	
	function admin_print_scripts_page()
	{
  		do_action('acf_print_scripts-input');
	}
	
	function admin_print_styles_page()
	{
		do_action('acf_print_styles-input');
	}
	
	function admin_head_page()
	{	
		// save
		if( isset($_POST['post_id']) )
		{
			$post_id = $_POST['post_id'];
			
			
			// save post title
			if( isset($_POST['post_title']) || isset($_POST['post_content']) || isset($_POST['post_excerpt']) )
			{
				$my_post = array();
				$my_post['ID'] = $post_id;
				
				if( isset($_POST['post_title']) )
				{
					$my_post['post_title'] = $_POST['post_title'];
				}
				if( isset($_POST['post_content']) )
				{
					$my_post['post_content'] = $_POST['post_content'];
				}
				if( isset($_POST['post_excerpt']) )
				{
					$my_post['post_excerpt'] = $_POST['post_excerpt'];
				}
				
				wp_update_post( $my_post );
			}
			
			  
			// save acf fields
			do_action('acf_save_post', $post_id);
			
			$this->data['save_post'] = true;
		}
		
		
		// vars
		$options = array(
			'fields' => false,
			'post_id' => 0,
		);
		$options = array_merge($options, $_GET);
		
		
		// global vars
		global $acf;
		
	
		// Style
		echo '<link rel="stylesheet" type="text/css" href="'.$this->dir.'/css/style.admin.css?ver=' . $this->version . '" />';
	
	
		// Javascript
		echo '<script type="text/javascript" src="'.$this->dir.'/js/functions.admin.js?ver=' . $this->version . '" ></script>';
		echo '<script type="text/javascript">acf.post_id = ' . $options['post_id'] . '; acf.nonce = "' . wp_create_nonce( 'acf_nonce' ) . '";</script>';
		
		
		// add user js + css
		do_action('acf_head-input');
	}
	
	
	/*
	*  wp_print_scripts
	*
	*  @description:
	*  @since 1.0.0
	*  @created: 25/07/12
	*/
	
	function wp_print_scripts() {
		
		wp_enqueue_script(array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-widget',
			'jquery-ui-mouse',
			'jquery-ui-resizable'
		));

	}
	
	
	/*
	*  wp_head
	*
	*  @description:
	*  @since 1.0.0
	*  @created: 25/07/12
	*/
	
	function wp_head()
	{
		// Javascript
		echo '<script type="text/javascript">
			var live_edit = {
				ajaxurl : "' . admin_url( 'admin-ajax.php' ) . '",
				panel_url : "' . admin_url( 'options-general.php?page=live-edit-panel' ) . '",
				panel_width : ' . $this->data['panel_width'] . '
			};
		</script>';
		echo '<script type="text/javascript" src="' . $this->dir . '/js/functions.front.js?ver=' . $this->version . '" ></script>';
		
		
		// Style
		echo '<link rel="stylesheet" type="text/css" href="' . $this->dir . '/css/style.front.css?ver=' . $this->version . '" />';
		
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	wp_footer
	*
	*	@author Elliot Condon
	*	@since 1.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function wp_footer()
	{
		?>
		<div id="live_edit-panel">
			<div id="live_edit-iframe-cover"></div>
			<iframe id="live_edit-iframe"></iframe>
		</div>
		<div id="live_edit-vail"></div>
		<?php
		
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	ajax_update_width
	*
	*	@author Elliot Condon
	*	@since 1.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function ajax_update_width()
	{
		// vars
		$options = array(
			'live_edit_panel_width' => 600
		);
		
		$options = array_merge($options, $_POST);
		
		
		// update option
		update_option( 'live_edit_panel_width', $options['panel_width'] );
		
		
		echo "1";
		die;
	}
	
	
	/*
	*  render_fields_for_input
	*
	*  @description: slightly different from acf's render_fields_for_input
	*  @since 3.1.6
	*  @created: 23/06/12
	*/
	
	function render_fields_for_input($fields)
	{
		global $acf;
		
		
		// create fields
		if($fields)
		{
			foreach($fields as $field)
			{
				// if they didn't select a type, skip this field
				if(!$field['type'] || $field['type'] == 'null') continue;
				
				$required_class = "";
				$required_label = "";
				
				if($field['required'] == "1")
				{
					$required_class = ' required';
					$required_label = ' <span class="required">*</span>';
				}
				
				echo '<div id="acf-' . $field['name'] . '" class="field field-' . $field['type'] . ' field-'.$field['key'] . $required_class . '">';

					echo '<p class="label">';
						echo '<label for="fields[' . $field['key'] . ']">' . $field['label'] . $required_label . '</label>';
						echo $field['instructions'];
					echo '</p>';
					
					if( $field['name'] != 'post_title' && $field['name'] != 'post_content' && $field['name'] != 'post_excerpt' )
					{
						$field['name'] = 'fields[' . $field['key'] . ']';
					}

					$acf->create_field($field);
				
				echo '</div>';
				
			}
			// foreach($fields as $field)
		}
		// if($fields)
		
	}
	
	
	/*
	*  get_field_object
	*
	*  @description: slightly different from acf's get_field_object
	*  @since 3.1.6
	*  @created: 23/06/12
	*/
	
	function get_field_object($field_name, $post_id)
	{
		global $acf; 
		 
		
		// allow for option == options
		if( $post_id == "option" )
		{
			$post_id = "options";
		}
		
		 
		// get key
		$field_key = "";
		if( is_numeric($post_id) )
		{
			$field_key = get_post_meta($post_id, '_' . $field_name, true); 
		}
		else
		{
			$field_key = get_option('_' . $post_id . '_' . $field_name); 
		}
	
		
		// default return vaue
		$field = false;
		
		if($field_key != "") 
		{ 
			// we can load the field properly! 
			$field = $acf->get_acf_field($field_key); 
		} 
		

		return $field; 
	}
	
	
	/*
	*  render_live_edit_panel
	*
	*  @description: 
	*  @created: 7/09/12
	*/
	
	function view_panel()
	{
		global $acf;
		
		
		// vars
		$options = array(
			'fields' => false,
			'post_id' => 0,
		);
		$options = array_merge($options, $_GET);
		
		
		// validate
		if( !$options['post_id'] )
		{
			wp_die( "Error: No post_id parameter found" );
		}
		
		if( !$options['fields'] )
		{
			wp_die( "Error: No fields parameter found" );
		}
		
		
		// loop through and load all fields as objects
		$fields = explode(',',$options['fields']);

		if( $fields )
		{
			foreach( $fields as $k => $field_name )
			{
				$field = null;
				
				
				if( $field_name == "post_title" ) // post_title
				{
					$field = array(
						'key' => 'post_title',
						'label' => 'Post Title',
						'name' => 'post_title',
						'value' => get_post_field('post_title', $options['post_id']),
						'type'	=>	'text',
					);
				}
				elseif( $field_name == "post_content" ) // post_content
				{
					$field = array(
						'key' => 'post_content',
						'label' => 'Post Content',
						'name' => 'post_content',
						'value' => get_post_field('post_content', $options['post_id']),
						'type'	=>	'wysiwyg',
					);
				}
				elseif( $field_name == "post_excerpt" ) // post_excerpt
				{
					$field = array(
						'key' => 'post_excerpt',
						'label' => 'Post Excerpt',
						'name' => 'post_excerpt',
						'value' => get_post_field('post_excerpt', $options['post_id']),
						'type'	=>	'textarea',
					);
				}
				else // acf field
				{
					$field = $this->get_field_object( $field_name, $options['post_id'] );
					$field['value'] = $acf->get_value( $options['post_id'], $field ); 
				}
				
				$field = apply_filters('acf_load_field', $field);
				
				$fields[$k] = $field;
			}
		}
	
		// render fields
?>
<div class="wrap no_move">
	
	<?php if( isset($this->data['save_post']) ): ?>
		<div class="inner-padding">
			<div id="message" class="updated"><p><?php _e("Fields updated", 'live-edit'); ?></p></div>
		</div>
	<?php endif; ?>
			
	<form id="post" method="post" name="post">
	
		<div style="display:none;">
			<input type="hidden" name="post_id" value="<?php echo $options['post_id']; ?>" />
		</div>
		<div class="metabox-holder" id="poststuff">
				
			<!-- Main -->
			<div id="post-body">
			<div id="post-body-content">
				<div class="acf_postbox">
				
					<?php $this->render_fields_for_input( $fields, $options['post_id'] ); ?>	
									
					<div id="field-save">
						<ul class="hl clearfix">
							<li>
								<a class="le-button grey" href="#" id="live_edit-close">
									<?php echo isset($this->data['save_post']) ? __("Close", 'live-edit') : __("Cancel", 'live-edit') ?>
								</a>
							</li>
							<li class="right">
								<input type="submit" name="live_edit-save" class="le-button" id="live_edit-save" value="<?php esc_attr_e("Update", 'live-edit') ?>" />
							</li>
							<li class="right" id="saving-message">
								<?php _e("Saving", 'live-edit'); ?>...
							</li>
						</ul>
					</div>
					
				</div>
			</div>
			</div>
		
		</div>
	</form>
	
	<?php if( isset($this->data['save_post']) ): ?>
		<script type="text/javascript">
		(function($){
		
		// does parent exist?
		if( !parent )
		{
			return;
		}
		
		// update the div
		parent.live_edit.update_div();
		
		})(jQuery);
		</script>
	<?php endif; ?>

</div>
<?php

	}
	
	/*
	*  ajax_save_post
	*
	*  @description: 
	*  @created: 8/09/12
	*/
	
	function ajax_save_post()
	{
		global $acf;
		
		
		// validate
		if( !isset($_POST['fields']) )
		{
			wp_die("0");
		}
		

		// loop through and save
		if( $_POST['fields'] )
		{
			foreach( $_POST['fields'] as $key => $value )
			{
				// get field
				$field = $acf->get_acf_field($key);
				
				$acf->update_value($post_id, $field, $value);
			}
			// foreach($fields as $key => $value)
		}
		// if($fields)
		
		
		wp_die("1");
	}
}


/*
*  live_edit
*
*  @description:
*  @since 1.0.0
*  @created: 25/07/12
*/

function live_edit( $fields = false, $post_id = false )
{
	// validate fields
	if( !$fields )
	{
		return false;
	}
	
	
	// global post_id
	if( !$post_id )
	{
		global $post;
		$post_id = $post->ID;
	}
	
	
	// turn array into string
	if( is_array($fields) )
	{
		$fields = implode(',', $fields);
	}
	
	
	// remove any white spaces from $fields
	$fields = str_replace(' ', '', $fields);
	
	
	// build atts
	$atts = ' data-live_edit-fields="' . $fields . '" data-live_edit-post_id="' . $post_id . '" ';
	
	echo $atts;
	
}

?>
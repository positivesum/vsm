<?php
/*
Plugin Name: Visual Site Manager
Plugin URI: http://wordpress.org/extend/plugins/vsm/
Description: Visual Site Manager makes it easier to manage page and menu structure of large websites.
Version: 1.0
Author: Taras Mankovski
Author URI: http://taras.cc
*/

/* 
 * Build json of nav menus
 */
 
$mainmenu = 0;

function getDomain($url) {
    if(filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) === FALSE)
    {
        return false;
    }
    /*** get the url parts ***/
    $parts = parse_url($url);
    /*** return the host domain ***/
    return $parts['host'];
}

function vsm_get_menus(&$json_data, $menu_items, $parent) {
	global $mainmenu;
	$k = 0;
	foreach ($menu_items as $menu_item) {
		if ($menu_item->menu_item_parent ==  $parent) {
			$menu_item->menu = $mainmenu;
			$json_data['children'][] = array (
				'id' => $menu_item->ID,
				'name' => vsm_truncated_names($menu_item->title),
				'data' => $menu_item,
				'children' => array()
			);
			vsm_get_menus($json_data['children'][$k], $menu_items, $menu_item->ID);
			$k++;						
		}
	}	
}

// Generate truncated menu names
function vsm_truncated_names($name) {
	$truncated_name = trim( wp_html_excerpt( $name, 30 ) );
	if ( $truncated_name != $name )	$truncated_name .= '&hellip;';
	return $truncated_name;
}

function vsm_nav_menus() {
	global $mainmenu;
	$count = 0;
	// Get all nav menus
	$menus = wp_get_nav_menus(array('orderby' => 'name' ));
	$json_menus = array (
		'id' => 0,
		'name' => 'Menus',
		'data' => array(),
		'children' => array()
	);

	
	$k = 0;
	foreach ($menus as $menu ) {
		$menu = wp_get_nav_menu_object( $menu->term_id );
		if (is_nav_menu( $menu )) {
		$json_menus['children'][] = array (
			'id' => $menu->term_id,
			'name' => vsm_truncated_names($menu->name),
			'data' => $menu,
			'children' => array()
		);
		$mainmenu = $menu->term_id;
		$menu_items = wp_get_nav_menu_items( $menu->term_id, array('post_status' => 'any') );
		$count += count($menu_items);
		vsm_get_menus($json_menus['children'][$k], $menu_items, 0);
		$json_menus['children'][$k]['id'] = 'menu-' . $json_menus['children'][$k]['id'];
		$k++;
		}
	}
	$json_menus['count'] = $count;
	
	
	
	$menus = array (
		'id' => -1,
		'name' => getDomain(site_url()),
		'count' => 0,		
		'data' => array(),
	);	
	$menus['children'][] = $json_menus;
	return $menus;
}

function vsm_nav_menu($term_id) {
	global $mainmenu;
	$menu = wp_get_nav_menu_object( $term_id );
	if (is_nav_menu( $menu )) {
		$json_menus = array (
			'id' => $menu->term_id,
			'name' => $menu->name,
			'data' => $menu,
			'children' => array()
		);
		$mainmenu = $menu->term_id;
		$menu_items = wp_get_nav_menu_items( $menu->term_id, array('post_status' => 'any') );
		vsm_get_menus($json_menus, $menu_items, 0);
	} else {
		$json_menus = array ();
	}
	return $json_menus;
}

/**
 * Displays a box for the nav menu theme locations.
 */
function vsm_nav_menu_locations_box() {
	if ( ! current_theme_supports( 'menus' ) ) {
		// We must only support widgets. Leave a message and bail.
		echo '<div id="nav-menu-theme-locations" class="menu-item-settings">
				<h3 class="hndle"><span>Theme Locations</span></h3>
					<p class="howto">' . __('The current theme does not natively support menus, but you can use the &#8220;Custom Menu&#8221; widget to add any menus you create here to the theme&#8217;s sidebar.') . '</p>
	<div class="menu-item-actions description-wide submitbox">
		<a class="submitcancel" href="javascript:void(0)" onclick="$(\'#nav-menu-theme-locations\').fadeOut(400);">Cancel</a><div></div>';
		return;
	}

	$locations = get_registered_nav_menus();
	$menus = wp_get_nav_menus();
	$menu_locations = get_nav_menu_locations();
	$num_locations = count( array_keys($locations) );

	echo '<div id="nav-menu-theme-locations" class="menu-item-settings">
				<h3 class="hndle"><span>Theme Locations</span></h3>
				<p class="howto">' . sprintf( _n('Your theme supports %s menu. Select which menu you would like to use.', 'Your theme supports %s menus. Select which menu appears in each location.', $num_locations ), number_format_i18n($num_locations) ) . '</p><form><div class="panel"><input type="hidden" name="action" value="menu-locations-save" />';
	wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' );				
	foreach ( $locations as $location => $description ) {
		?>
		<p class="description description-thin">
			<label class="howto" for="locations-<?php echo $location; ?>">
				<span><?php echo $description; ?></span>
				<select name="menu-locations[<?php echo $location; ?>]" style="float:right;" id="locations-<?php echo $location; ?>">
					<option value="0"></option>
					<?php foreach ( $menus as $menu ) : ?>
					<option<?php selected( isset( $menu_locations[ $location ] ) && $menu_locations[ $location ] == $menu->term_id ); ?>
						value="<?php echo $menu->term_id; ?>"><?php
						$truncated_name = wp_html_excerpt( $menu->name, 40 );
						echo $truncated_name == $menu->name ? $menu->name : trim( $truncated_name ) . '&hellip;';
					?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
	<?php
	}
	?>
	</div>		
	</form>
	<div class="menu-item-actions description-wide submitbox">
		<a class="submitcancel" href="javascript:void(0)" onclick="saveMenuLocations();" >Save</a>
		<span class="meta-sep"> | </span> 		
		<a class="submitcancel" href="javascript:void(0)" onclick="$('#nav-menu-theme-locations').fadeOut(400);">Cancel</a>
	</div>	
	</div>		
	<?php
}
 
function vsm_nav_menus_show() {
?>


<div class="wrap" id="wrap">
<?php screen_icon(); ?>
<h2>Visual Admin Tree View</h2>
<div id="messages"></div>
<div id="canvas">
    <div class="canvasWrap">
        <div id="viewToggles">
        <h3 style="float: left;">View:</h3>
        <ul>
            <li id="mapView"><a href="javascript:void(0)" title="Map View">Map View</a></li>
            <li id="outlineView"><a href="javascript:void(0)" title="Outline View">Outline View</a></li>
            <li id="fullscreenView"><a href="javascript:void(0)" title="Full Screen View">Full Screen View</a></li>        </ul>
        </div>
        <div id="viewPort" class="outline"></div>
    </div>
</div>
</div>

<?php vsm_nav_menu_locations_box(); ?>

<div id="menu-item-settings" class="menu-item-settings" >
	<p class="description description-thin">
		<label for="edit-menu-item-title">
			Navigation Label<br>
			<input type="text" value="" name="menu-item-title" class="widefat edit-menu-item-title" id="edit-menu-item-title">
		</label>
	</p>
	<p class="description description-thin" id="menu-item-url">
		<label for="edit-menu-item-url">URL<br>
			<input type="text" value="" name="menu-item-url" class="widefat code edit-menu-item-url" id="edit-menu-item-url">
		</label>
	</p>	
	<div class="link-to-original" id="link-to-original">Original:
		<div style="float:right;" class="menu-item-actions description-wide submitbox">
			<a href="#" id="menu-item-edit" class="submitcancel">Edit</a><span class="meta-sep"> | </span> 		
			<a href="#" id="menu-item-preview" class="submitcancel">Preview</a>
		</div>
	</div>
	<p class="field-link-target description description-thin" id="menu-item-target">
		<label for="edit-menu-item-target">Link will
			<select name="menu-item-target" class="edit-menu-item-target" id="edit-menu-item-target">
				<option value="">open same window or tab.</option>
				<option selected="selected" value="_blank">open new window or tab.</option>
				<option value="_nothing">do nothing on click.</option>
			</select>
		</label>
	</p>			
	<div class="menu-item-actions description-wide submitbox">
		<a href="javascript:void(0)" id="menu-item-locations" class="submitcancel">Locations</a><span id="span-locations" class="meta-sep"> | </span> 		
		<span id="span-add"><a href="javascript:void(0)" id="menu-item-add" class="submitcancel">Add</a><span class="meta-sep"> | </span></span> 		
		<span id="span-save"><a href="javascript:void(0)" id="menu-item-save" class="submitcancel" >Save</a><span class="meta-sep"> | </span></span>		
		<a class="submitcancel" href="javascript:void(0)" onclick="$('#menu-item-settings').fadeOut(400);">Cancel</a>
		<span id="span-delete" style="float:right"><a href="javascript:void(0)" id="menu-item-delete" class="submitdelete">Remove</a></span>		
	</div>
</div><!-- .menu-item-settings-->

<div class="menu-item-settings" id="typediv">
		<ul class="add-menu-item-tabs" id="add-menu-item-tabs">
			<li class="tabs" id="tab-pages"><a href="javascript:void(0)" onclick="selectTab('pages');" class="nav-tab-link">Pages</a></li>		
			 <?php if ( !get_option('hide_link_tab') ) {	?>		
			<li class="" id="tab-links"><a href="javascript:void(0)" onclick="selectTab('links');" class="nav-tab-link">Links</a></li>
			 <?php  }	?>					
			 <?php if ( !get_option('hide_post_tab') ) {	?>					 
			<li class="" id="tab-posts"><a href="javascript:void(0)" onclick="selectTab('posts');" class="nav-tab-link">Posts</a></li>
			 <?php  }	?>								
		</ul>
		<div id="tabs-panel-pages" class="tabs-panel tabs-panel-active">
			<div id="titlediv" style="margin-bottom:10px;">
			<label title="new_page"><input type="radio" checked="checked" value="new_page" name="page" onclick="showPage(0);">New Page</label>							
			<div id="titlewrap">
				<label for="custom-menu-item-name" class="howto">
				<label for="title" id="title-prompt-text" style="" class="hide-if-no-js">Enter title here</label>
				<input type="text" class="code menu-item-textbox" autocomplete="off" id="title" value="" tabindex="1" size="30" name="post_title">
				</label>
			</div>
			</div>
			<label title="existing_page"><input type="radio" value="existing_page" name="page" onclick="showPage(1);">Existing Page</label>												
			<div id="menu-item-name-wrap" style="display:none;">
				<label for="custom-menu-item-name" class="howto">
					 <?php 
					$pages = wp_dropdown_pages(array('name' => 'pages-list', 'sort_column'=> 'menu_order, post_title', 'echo' => 0, 'show_option_none' => 'Select a page' ));
					if ( ! empty($pages) ) {

						echo $pages;
					} else {
						echo ('No items');
					}						 
					?>
				</label>
			</div>
		</div>
		<div class="tabs-panel  tabs-panel-inactive" id="tabs-panel-links">	
			<div class="customlinkdiv">
				<p id="menu-item-url-wrap">
					<label for="custom-menu-item-url" class="howto">
						<span>URL</span>
						<input type="text" value="http://" class="code menu-item-textbox" name="custom-menu-item-url" id="custom-menu-item-url">
					</label>
				</p><br/>
				<p id="menu-item-name-wrap">
					<label for="custom-menu-item-name" class="howto">
						<span>Label</span>
						<input type="text" title="Menu Item" class="regular-text menu-item-textbox input-with-default-title" name="custom-menu-item-name" id="custom-menu-item-name">
					</label>
				</p>
			</div>		
		</div>			
		<div class="tabs-panel tabs-panel-inactive" id="tabs-panel-posts">
			 <?php 
			 
				$args = array(
					'post_type' => 'post',
					'numberposts' => -1,
					'orderby' => 'date',		
					'post_status' => 'publish',
					'post_parent' => null, // any parent
					); 
				
			   $posts = get_posts($args);			 
			 if (count($posts) > 0): ?>
			<select class="select-nav-menu" id="posts-list">
			 <?php foreach( (array) $posts as $post ) : ?>
			 <option value="<?php echo esc_attr($post->ID) ?>"><?php echo esc_html($post->post_title); ?></option>
			<?php endforeach; ?>
			</select>			
			<?php else: ?> 
			    No items
			<?php endif; ?> 
			
		</div>
	<div class="menu-item-actions description-wide submitbox" style="padding-top:6px;">
		<a href="javascript:void(0)" id="submenu-item-add" class="submitcancel" onclick="">Add</a><span class="meta-sep"> | </span> 		
		<a class="submitcancel" href="javascript:void(0)" onclick="$('#typediv').fadeOut(400);">Cancel</a>
	</div>		
</div>

<?php

}

function vsm_plugin_admin_init()  {
	wp_register_style('vsmPluginStylesheet', WP_PLUGIN_URL . '/vsm/css/vsm.css');
	/* Register scripts. */
	wp_register_script('jquery142', WP_PLUGIN_URL . '/vsm/js/jquery-1.4.2.js');
	wp_register_script('jquery-cookie', WP_PLUGIN_URL . '/vsm/js/jquery.cookie.js');	
	wp_register_script('jit', WP_PLUGIN_URL . '/vsm/js/jit-1.1.3/jit.js');	
	wp_register_script('excanvas', WP_PLUGIN_URL . '/vsm/js/jit-1.1.3//Extras/excanvas.js');		
	wp_register_script('vsm', WP_PLUGIN_URL . '/vsm/js/vsm.js');		
}

function vsm_plugin_menu() {
	// $page = add_dashboard_page('Visual Admin', 'Visual Admin', 'publish_pages', 'vsm', 'vsm_plugin_theme');
	
	 // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	add_menu_page( 'Visual Admin', 'Visual Admin', 'manage_options', 'vsm', 'vsm_plugin_options');	
	
	// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function )
	$page = add_submenu_page( 'vsm', 'Tree View', 'Tree View', 'publish_pages', 'vsm-tree-view', 'vsm_tree_view' );
     /* Using registered $page handle to hook stylesheet loading */
	add_action('admin_print_styles-' . $page, 'vsm_plugin_admin_styles');
	add_action('admin_print_scripts-' . $page, 'vsm_plugin_admin_scripts');	
	
	$page = add_submenu_page( 'vsm', 'List View', 'List View', 'publish_pages', 'vsm-list-view', 'vsm_list_view' );
	add_action('admin_print_styles-' . $page, 'vsm_plugin_admin_styles_list');
	add_action('admin_print_scripts-' . $page, 'vsm_plugin_admin_scripts_list');	
	
}

function vsm_plugin_admin_styles() {
	wp_enqueue_style('vsmPluginStylesheet');
}

function vsm_plugin_admin_scripts() {
global $is_IE;
	if ($is_IE) {
		wp_enqueue_script('excanvas');	
	}
	wp_enqueue_script('jquery142');
	wp_enqueue_script('jquery-cookie');	
	wp_enqueue_script('jit');	
	wp_enqueue_script('vsm');	
}

function vsm_plugin_admin_styles_list() {
	wp_enqueue_style('vsm-list', WP_PLUGIN_URL . '/vsm/css/jquery-ui.css');
}

function vsm_plugin_admin_scripts_list() {
	wp_enqueue_script('vsm-list', WP_PLUGIN_URL . '/vsm/js/vsm-list.js', array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-dialog'), '1.0');
}

function vsm_tree_view() {
  if (!current_user_can('publish_pages'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
	vsm_nav_menus_show();
}

/*
function get_menuitem($post_id) {
	$menus = wp_get_nav_menus();
	$menuitem = '';
	foreach ($menus as $menu ) {
		$menu = wp_get_nav_menu_object( $menu->term_id );
		if (is_nav_menu( $menu )) {
			$menu_items = wp_get_nav_menu_items( $menu->term_id, array('post_status' => 'any') );
			foreach ($menu_items as $menu_item) {
				if ($menu_item->ID == $post_id) {
					return $menu_item;
				}
			}
		}
	}
	return false;
}
*/

function get_menuitem($menu_item_id) {
	$menu_item = get_post( $menu_item_id );
	if ($menu_item != null) {
		$menu_item->menu_item_parent = get_post_meta( $menu_item->ID, '_menu_item_menu_item_parent', true );
		$menu_item->object_id = get_post_meta( $menu_item->ID, '_menu_item_object_id', true );
		$menu_item->object = get_post_meta( $menu_item->ID, '_menu_item_object', true );
		$menu_item->type = get_post_meta( $menu_item->ID, '_menu_item_type', true );
		$menu_item->url = get_post_meta( $menu_item->ID, '_menu_item_url', true );
		$menu_item->target = get_post_meta( $menu_item->ID, '_menu_item_target', true );
		$menu_item->classes = (array) get_post_meta( $menu_item->ID, '_menu_item_classes', true );
		$menu_item->xfn = get_post_meta( $menu_item->ID, '_menu_item_xfn', true );
		$menu_item->title = '';
		if ($menu_item->post_title != '') {
			$menu_item->title = $menu_item->post_title;		
		} else {
			$post = get_post( $menu_item->object_id );
			if ($post != null ) {
				$menu_item->title = $post->post_title;		
			}
		}
		return $menu_item; 
	}
	return false;
}

function get_parents(&$output, $post_id) {
	$post = get_post($post_id);
	$link = '<a href="' . get_edit_post_link($post->ID, true) . '" title="' . esc_attr(__('Edit this item')) . '">' . trim($post->post_title) . '</a>';
	if ($output != '') {
		$output = $link . ' > ' . $output;
	} else {
		$output = $link;
	}
	if ($post->post_parent != null) {
		get_parents($output, $post->post_parent);
	}
}

function get_menu_item_parents(&$output, $menu_item_id) {
	$menu_item = get_menuitem($menu_item_id);
	$link = '<a href="' . $menu_item->url . '" title="' . esc_attr(__('Edit this item')) . '">' . trim($menu_item->title) . '</a>';
	if ($output != '') {
		$output = $link . ' > ' . $output;
	} else {
		$output = $link;
	}
	if ($menu_item->menu_item_parent > 0) {
		get_menu_item_parents($output, $menu_item->menu_item_parent);
	}
}

function menu_rows(&$output, $menus, $level, $edit) {
	static $rowclass;

	foreach ($menus as $menu) {
		if (isset($menu['name'])) {
			$name = $menu['name'];
			$type = '';
			$url = '';
			$status = '';
			$object_id = null;
			$link = '';
			$change = '';
			if (is_object($menu['data'])) {
				$data = $menu['data'];
				
				if (isset($data->term_id)) {
					$type = 'Menu';
					$name = '<b>' . $name . '</b>';
				} else {
					$type = $data->object;
					$url = '<a href="' . $data->url . '">' . $data->url . '</a>';
					$status = $data->post_status;
					$object_id = $data->object_id;
					if ($object_id != null) {
						get_parents($link, $object_id);
						$change = '<input type="button" value="Change" class="button-secondary" id="' . $menu['id'] . '|' . $object_id . '" name="">';
					}
				}
			}
			$rowclass = 'alternate' == $rowclass ? '' : 'alternate';
			if ($level != '') {
				$name = $level . ' > '. $name;
			}
			
			$output .= '<tr class="'.$rowclass.'">
							<td>' . $name .  '</td>
							<td>' . $type .  '</td>
							<td>' . $link .  '</td>
							<td>' . $change. '</td>
							<td>' . $url .   '</td>
							<td>' . $status .'</td>
						</tr>';
			if (is_array($menu['children'])) {
				menu_rows($output, $menu['children'], $name, $link);
			}
		}
	}
}

function vsm_list_view() {
  if (!current_user_can('publish_pages'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2>Visual Admin List View</h2>
		<div id="menu-management">
			<div class="nav-tabs-nav">
				<div class="nav-tabs-wrapper">
					<div class="nav-tabs" style="padding: 0px; margin-left: 0px;">
	<?php
	if (isset($_REQUEST['list-pages'])) {
	?>
						<a class="nav-tab hide-if-no-js" href="admin.php?page=vsm-list-view">Menus</a>
						<span class="nav-tab nav-tab-active">Pages</span>
					</div>
				</div>
			</div>
			<div class="menu-edit">
				<div id="nav-menu-header">
					<div style="padding: 5px 10px;"></div>
				</div>
				
				<table class="widefat fixed" cellspacing="0">
					<thead>
					<tr>
						<th width="30%">Page</th>
						<th width="30%">Menus</th>
						<th width="28%">Url</th>
						<th width="5%">Status</th>
						<th width="7%">&nbsp;</th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th>Page</th>
						<th>Menus</th>
						<th>Url</th>
						<th>Status</th>
						<th>&nbsp;</th>
					</tr>
					</tfoot>
					<tbody id="nav-page-items">
		<?php 
				$pages = get_pages(); 
				if (count($pages) > 0): 
		?>
				<?php foreach( (array) $pages as $p ) : 
					$menu = '';
					$menu_items = wp_get_associated_nav_menu_items( $p->ID );
					// $menuitem = get_menuitem($p->ID);
					if (count($menu_items) > 0) {
						get_menu_item_parents($menu, $menu_items[0]);
					}	
					$link = '';
					get_parents($link, $p->ID);
				?>
				<tr>
					<td><?php echo($link); ?></td>
					<td><?php echo($menu); ?></td>
					<td><a href="<?php echo get_page_link($p->ID); ?>"><?php echo get_page_link($p->ID); ?></a></td>
					<td><?php echo($p->post_status); ?></td>
					<td><input type="button" value="Delete" class="button-secondary" id="<?php echo($p->ID); ?>" name=""></td>
				</tr>
				<?php endforeach; ?>
				<?php else: ?> 
				<tr><td colspan="5">No Pages</td></tr>
				<?php endif; ?> 
					</tbody>
				</table>
			</div>
			
	<?php 
	} else {
	?>
						<span class="nav-tab nav-tab-active">Menus</span>
						<a class="nav-tab hide-if-no-js" href="admin.php?page=vsm-list-view&list-pages">Pages</a>
					</div>
				</div>
			</div>	
			<div class="menu-edit">
				<div id="nav-menu-header">
					<div style="padding: 5px 10px;">
						<label for="nav-menu">Menu: </label>
						<?php
						// Get all nav menus
						$menus = wp_get_nav_menus();
						if (count($menus) > 0): ?>
						<select class="select-nav-menu" id="nav-menu">
						<?php foreach( (array) $menus as $menu ) : ?>
						<option value="<?php echo esc_attr($menu->term_id) ?>"><?php echo esc_html($menu->name); ?></option>
						<?php endforeach; ?>
						</select>			
						<?php else: ?> 
						No Menus
						<?php endif; ?> 
					</div>
				</div>
				<table class="widefat fixed" cellspacing="0">
					<thead>
					<tr>
						<th width="24%">Menu Item</th>
						<th width="4%">Type</th>
						<th width="30%">Links To</th>
						<th width="7%">&nbsp;</th>
						<th width="30%">Url</th>
						<th width="5%">Status</th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th>Menu Item</th>
						<th>Type</th>
						<th>Links To</th>
						<th>&nbsp;</th>
						<th>Url</th>
						<th>Status</th>
					</tr>
					</tfoot>
					<tbody id="nav-menu-items" >
		<?php 
				
					$menus = vsm_nav_menu($menus[0]->term_id);
					$output = '';
					menu_rows($output, $menus['children'], '', '');
					echo($output);
		?>			
					</tbody>
				</table>
			</div>
		</div>		
	</div>
	<?php
	}
	?>
	<div id="pages-list-dialog" title="Change Menu Item Link: ">
		<div class="postbox">
			<label>Select a page that you would like this menu item to link to.</label>
			<div class="inside" id="pages-list-dialog-content"></div>
		</div>
	</div>							
<?php	
}


function vsm_options_menu() {
	if ( ! current_user_can('manage_options') )
		return;
    /* adds our admin panel */
	// $page = add_options_page('Visual Admin', 'Visual Admin', 'manage_options', 'vsm-manage-options', 'vsm_plugin_options');

	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'vsm-manage-options') {
		check_admin_referer('vsm-manage-options');
		set_option('hide_post_tab', $_REQUEST['hide_post_tab']);
		set_option('hide_link_tab', $_REQUEST['hide_link_tab']);		
	}	
}

function set_option ($option_name, $newvalue) {
	if ( get_option($option_name)  != $newvalue) {
		update_option($option_name, $newvalue);
	} else {
		$deprecated=' ';
		$autoload='no';
		add_option($option_name, $newvalue, $deprecated, $autoload);
	}		
} 

function vsm_plugin_options() {
	if ( ! current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
	?>		
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Visual Admin Settings</h2>
	<?php if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'vsm-manage-options')  { ?>
		<div id="message" class="updated"><p>Visual Admin Settings are updated</p></div>
	<?php }	?>		
	
	<form method="post" action="">		
	<input type="hidden" name="action" value="vsm-manage-options" />		
	<?php wp_nonce_field('vsm-manage-options'); ?>		
	<table class="form-table">
	<tr valign="top">
	<th scope="row">Hide Tab in Add New Menu</th>
	<td>
		<fieldset>
			<legend class="screen-reader-text"><span>Hide Tab in Add New Menu</span></legend>
			<label for="hide_link_tab">
				<input name="hide_link_tab" type="checkbox" id="hide_link_tab" value="1" <?php checked('1', get_option('hide_link_tab')); ?> />
				Link Tab
			</label><br/>						
			<label for="hide_post_tab">
				<input name="hide_post_tab" type="checkbox" id="hide_post_tab" value="1" <?php checked('1', get_option('hide_post_tab')); ?> />
				Post Tab
			</label>
		</fieldset>
	</td>
	</tr>
	</table>	
		<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>
	</form>	
	</div>
	<?php	
}


add_action('admin_init', 'vsm_plugin_admin_init');

add_action('admin_menu', 'vsm_plugin_menu');
add_action('admin_menu', 'vsm_options_menu');

add_action('wp_ajax_vsm', 'ajaxVsmNavmenus');

add_filter( 'walker_nav_menu_start_el', 'do_nothing_onclick'); // do nothing on click.

function do_nothing_onclick($item_output) {
	$pos = strrpos($item_output, "_nothing");
	if ($pos === false) { // note: three equal signs
		return $item_output;
	} else {
		$item_output =  preg_replace('/href/', 'href="javascript:void(0)" onclick="return false;"', $item_output);
	}
	return $item_output;
}

function ajaxVsmNavmenus() {
      // Load all the nav menu interface functions
      require_once(ABSPATH . 'wp-admin/includes/nav-menu.php');

	// Container for any messages displayed to the user
	$messages = array();

	// Container that stores the name of the active menu
	$nav_menu_selected_title = '';

	// The menu id of the current menu being edited
	$nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;

	// Allowed actions: add, update, delete
	$action = isset( $_REQUEST['operation'] ) ? $_REQUEST['operation'] : 'edit';

	switch ( $action ) {
	case 'add-menu-item' :
		if ( isset( $_REQUEST['menu-item'] ) ) {
			if (($_REQUEST['menu-item'][-1]['menu-item-object-id'] == 0) && ($_REQUEST['menu-item'][-1]['menu-item-type'] == 'post_type')) {
				$menu_obj = get_post( $_REQUEST['menu-item'][-1]['menu-item-parent-id'] );
				$menu_item = wp_setup_nav_menu_item( $menu_obj );
				$options = array(
				'post_status' => 'publish', 
				'post_type' => 'page',				
				'post_parent' => $menu_item->object_id,
				'post_title' => $_REQUEST['menu-item'][-1]['menu-item-title']);
				$_REQUEST['menu-item'][-1]['menu-item-object-id'] = wp_insert_post($options);
			} elseif ($_REQUEST['menu-item'][-1]['menu-item-object-id'] != 0) {
				$post_obj = get_post( $_REQUEST['menu-item'][-1]['menu-item-object-id'] );
				$_REQUEST['menu-item'][-1]['menu-item-title'] = $post_obj->post_title;			
			}
			$item_ids = wp_save_nav_menu_items( $nav_menu_selected_id, $_REQUEST['menu-item'] );
			if ( is_wp_error( $item_ids ) )
				die('-1');
		} else {
			$item_ids = array();
		}
		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj->post_status = 'publish'; //
				wp_update_post( $menu_obj ); //			
				$menu_items[] = wp_setup_nav_menu_item( $menu_obj );
			}
		}
		$response['menu_items'] = $menu_items;
		break;
		case 'delete-menu-item':
			$delete = false;
			$menu_items = explode(",", $_REQUEST['menu-item']);
			foreach ($menu_items as $key => $value) {
				$menu_item_id = (int) $value;
				if ( is_nav_menu_item( $menu_item_id ) ) {
					if ( wp_delete_post( $menu_item_id, true ) ) {
						$delete = true;
					}
				}
			}
			if ($delete) {
				$messages[] = '<div id="message" class="updated"><p>' . __('The menu item has been successfully deleted.') . '</p></div>';			
			}
			break;
		case 'delete':
			if ( is_nav_menu( $nav_menu_selected_id ) ) {
				$deleted_nav_menu = wp_get_nav_menu_object( $nav_menu_selected_id );
				$delete_nav_menu = wp_delete_nav_menu( $nav_menu_selected_id );

				if ( is_wp_error($delete_nav_menu) ) {
					$messages[] = '<div id="message" class="error"><p>' . $delete_nav_menu->get_error_message() . '</p></div>';
				} else {
					$messages[] = '<div id="message" class="updated"><p>' . __('The menu has been successfully deleted.') . '</p></div>';
					// Select the next available menu
					$nav_menu_selected_id = 0;
					$_nav_menus = wp_get_nav_menus( array('orderby' => 'name') );
					foreach( $_nav_menus as $index => $_nav_menu ) {
						if ( strcmp( $_nav_menu->name, $deleted_nav_menu->name ) >= 0
						|| $index == count( $_nav_menus ) - 1 ) {
							$nav_menu_selected_id = $_nav_menu->term_id;
							break;
						}
					}
				}
				unset( $delete_nav_menu, $deleted_nav_menu, $_nav_menus );
			} else {
				// Reset the selected menu
				$nav_menu_selected_id = 0;
				unset( $_REQUEST['menu'] );
			}
			
			break;
		case 'add-menu':
			// Add Menu
			$new_menu_title = esc_html( $_REQUEST['menu-name'] );
			if ( $new_menu_title ) {
				$_nav_menu_selected_id = wp_update_nav_menu_object( 0, array('menu-name' => $new_menu_title) );
				if ( is_wp_error( $_nav_menu_selected_id ) ) {
					$messages[] = '<div id="message" class="error"><p>' . $_nav_menu_selected_id->get_error_message() . '</p></div>';
				} else {
					if ( ( $_menu_locations = get_registered_nav_menus() ) && 1 == count( wp_get_nav_menus() ) )
						set_theme_mod( 'nav_menu_locations', array( key( $_menu_locations ) => $_nav_menu_selected_id ) );
					unset( $_menu_locations );
					$_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id );
					$nav_menu_selected_id = $_nav_menu_selected_id;
					$response['menu_object'] = $_menu_object;
					$nav_menu_selected_title = $_menu_object->name;
					$messages[] = '<div id="message" class="updated"><p>' . sprintf( __('The <strong>%s</strong> menu has been successfully created.'), $nav_menu_selected_title ) . '</p></div>';
				}
			} else {
				$messages[] = '<div id="message" class="error"><p>' . __('Please enter a valid menu name.') . '</p></div>';
			}
			break;		
		case 'update-menu':


		      $_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id );

		      $menu_title = trim( esc_html( $_POST['menu-name'] ) );
		      if ( ! $menu_title ) {
			      $messages[] = '<div id="message" class="error"><p>' . __('Please enter a valid menu name.') . '</p></div>';
			      $menu_title = $_menu_object->name;
		      }

		      if ( ! is_wp_error( $_menu_object ) ) {
			      $_nav_menu_selected_id = wp_update_nav_menu_object( $nav_menu_selected_id, array( 'menu-name' => $menu_title ) );
			      if ( is_wp_error( $_nav_menu_selected_id ) ) {
				      $_menu_object = $_nav_menu_selected_id;
				      $messages[] = '<div id="message" class="error"><p>' . $_nav_menu_selected_id->get_error_message() . '</p></div>';
			      } else {
				      $_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id );
				      $nav_menu_selected_title = $_menu_object->name;
				      $messages[] = '<div id="message" class="updated"><p>' . sprintf( __('The <strong>%s</strong> menu has been updated.'), $nav_menu_selected_title ) . '</p></div>';
			      }
		      }


			break;
		case 'update-menu-item':		
		$args = array(
			'menu-item-object-id' => $_REQUEST['menu-item-object-id'],
			'menu-item-object' => $_REQUEST['menu-item-object'],		
			'menu-item-parent-id' => $_REQUEST['menu-item-parent-id'],		
			'menu-item-type' => $_REQUEST['menu-item-type'],		
			'menu-item-target' => $_REQUEST['menu-item-target'],
			'menu-item-position' => $_REQUEST['menu-item-position'],
			'menu-item-url' => $_REQUEST['menu-item-url'],			
			'menu-item-db-id' => $_REQUEST['menu-item-db-id'],			
			'menu-item-title' => $_REQUEST['menu-item-title']);
			$menu_item_db_id = wp_update_nav_menu_item( $nav_menu_selected_id, $_REQUEST['menu-item-id'], $args );
		break;
		case 'load-nav-menus':		
			$json_menus = vsm_nav_menus();
			$response['json_menus'] = $json_menus;
		break;
		case 'get-menu':
			if ( is_nav_menu( $nav_menu_selected_id ) ) {
				$menus = vsm_nav_menu($nav_menu_selected_id);
				$output = '';
				menu_rows($output, $menus['children'], '', '');
				$response['json_menu'] = $output;
			}
		break;
		case 'get-pages-list':
			if ( is_nav_menu( $nav_menu_selected_id ) ) {
				$title = 'Change Menu Item Link: ';
				$menu_item = get_menuitem($_REQUEST['menu-item-id']);
				if ($menu_item) {
					$title .= $menu_item->title;
				}
				$pages = wp_dropdown_pages(array('name' => 'pages-list', 'selected' => $_REQUEST['menu-item-object-id'], 'sort_column'=> 'menu_order', 'echo' => 0, 'show_option_none' => 'Select a page' ));
				$response['menu_item_id'] = $_REQUEST['menu-item-id'];
				$response['title'] = $title;
				$response['pages'] = $pages;
			}
		break;
		case 'update-menu-item-list':
			if ( is_nav_menu( $nav_menu_selected_id ) ) {
				$menu_items = wp_get_nav_menu_items( $nav_menu_selected_id );
				foreach ($menu_items as $menu_item) {
					if ($menu_item->ID == $_REQUEST['menu-item-id']) {
						$args = array(
							'menu-item-object-id' => $_REQUEST['menu-item-object-id'],
							'menu-item-object' => $menu_item->object,		
							'menu-item-parent-id' => $menu_item->menu_item_parent,		
							'menu-item-type' => $menu_item->type,		
							'menu-item-target' => $menu_item->target,
							'menu-item-position' => $menu_item->menu_order,
							'menu-item-url' => $menu_item->url,			
							'menu-item-db-id' => $menu_item->db_id,			
							'menu-item-title' => $menu_item->title
						);
						$menu_item_db_id = wp_update_nav_menu_item( $nav_menu_selected_id, $_REQUEST['menu-item-id'], $args );
						break;
					}
				
				}
				$response['json_test'] = $menu_items;
			}
			$menus = vsm_nav_menu($nav_menu_selected_id);
			$output = '';
			menu_rows($output, $menus['children'], '', '');
			$response['json_menu'] = $output;
		break;
		case 'delete-page':
			wp_delete_post( $_REQUEST['post-id'] );
			$pages = get_pages(); 
			$output = '';
			if (count($pages) > 0) {
				foreach ($pages as $p) {
					$menu = '';
					$menu_items = wp_get_associated_nav_menu_items( $p->ID );
					if (count($menu_items) > 0) {
						get_menu_item_parents($menu, $menu_items[0]);
					}	
					$link = '';
					get_parents($link, $p->ID);
					$output .= '<tr>
									<td>' . $link . '</td>
									<td>' . $menu . '</td>
									<td><a href="' . get_page_link($p->ID) . '">' . get_page_link($p->ID) . '</a></td>
									<td>' . $p->post_status . '</td>
									<td><input type="button" value="Delete" class="button-secondary" id="' . $p->ID . '" name=""></td>
								</tr>';
				}
			} else {
				$output = '<tr><td colspan="5">No Pages</td></tr>';
			}
			$response['json_page'] = $output;
		break;
		
	}

	// Get all nav menus
	$nav_menus = wp_get_nav_menus( array('orderby' => 'name') );

	// Get recently edited nav menu
	$recently_edited = (int) get_user_option( 'nav_menu_recently_edited' );

	// If there was no recently edited menu, and $nav_menu_selected_id is a nav menu, update recently edited menu.
	if ( !$recently_edited && is_nav_menu( $nav_menu_selected_id ) ) {
		$recently_edited = $nav_menu_selected_id;

	// Else if $nav_menu_selected_id is not a menu and not requesting that we create a new menu, but $recently_edited is a menu, grab that one.
	} elseif ( 0 == $nav_menu_selected_id && ! isset( $_REQUEST['menu'] ) && is_nav_menu( $recently_edited ) ) {
		$nav_menu_selected_id = $recently_edited;

	// Else try to grab the first menu from the menus list
	} elseif ( 0 == $nav_menu_selected_id && ! isset( $_REQUEST['menu'] ) && ! empty($nav_menus) ) {
		$nav_menu_selected_id = $nav_menus[0]->term_id;
	}

	// Update the user's setting
	if ( $nav_menu_selected_id != $recently_edited && is_nav_menu( $nav_menu_selected_id ) )
		update_user_meta( $current_user->ID, 'nav_menu_recently_edited', $nav_menu_selected_id );

	// If there's a menu, get its name.
	if ( ! $nav_menu_selected_title && is_nav_menu( $nav_menu_selected_id ) ) {
		$_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id );
		$nav_menu_selected_title = ! is_wp_error( $_menu_object ) ? $_menu_object->name : '';
	}

	$response['messages'] = $messages;
	$response['recently_edited'] = $nav_menu_selected_id;
	echo (json_encode($response));
	die();	
}

?>
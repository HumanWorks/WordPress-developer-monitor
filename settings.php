<?php
function dev_create_menu() {

	//create new top-level menu
	add_options_page('Developer Monitor Plugin Settings', 'Developer Monitor', 'administrator', __FILE__, 'dev_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}
add_action('admin_menu', 'dev_create_menu');

function register_mysettings() {
	//register our settings
	register_setting( 'dev-settings-group', 'group_id' );
}

function dev_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page='.plugin_basename(__FILE__).'">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

function dev_settings_page() {
?>
<div class="wrap">
<h2>Developer Monitor Plugin</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'dev-settings-group' ); ?>
    <?php /*do_settings( 'dev-settings-group' );*/ ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Select the user role that will get the firebug data:</th>
        <td><select name="group_id" />
        <option value="0">None(deactivate)</option>
        <?php
        if ( !isset( $wp_roles ) )
		   $wp_roles = new WP_Roles();
		foreach ( $wp_roles->role_names as $role => $name ) {	
		   echo '<option value="'.$role.'"'.(get_option('group_id')==$role?' selected="selected"' : '').'>'.$name.'</option>';
		   echo $role;
		}
        ?>
        </select></td>
        </tr>
         
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php }
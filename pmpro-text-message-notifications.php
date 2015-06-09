<?php
/*
Plugin Name: Paid Memberships Pro - Text Message Notifications
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-text-message-notifications/
Description: Set up admin text message alerts by level for when members checkout out on your site.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

//http://martinfitzpatrick.name/list-of-email-to-sms-gateways/
$pmprosms_mobile_carriers = array('AT&T'=>'@txt.att.net','Boost'=>'@sms.myboostmobile.com','Metro PCS'=>'@mymetropcs.com','Sprint'=>'@pm.sprint.com','T-Mobile'=>'@tmomail.net','Verizon'=>'@vtext.com');

//init
function pmprosms_init()
{
	//get options for below
	$options = get_option("pmprosms_options");		
			
	//are we on the checkout page?
	$is_checkout_page = (isset($_REQUEST['submit-checkout']) || (isset($_REQUEST['confirm']) && isset($_REQUEST['gateway'])));
	
	//setup hooks for user_register
}
add_action("init", "pmprosms_init", 0);

//admin init. registers settings
function pmprosms_admin_init()
{
	//setup settings
	register_setting('pmprosms_options', 'pmprosms_options', 'pmprosms_options_validate');	
	add_settings_section('pmprosms_section_general', 'General Settings', 'pmprosms_section_general', 'pmprosms_options');	

	//only if PMPro is installed
	if(function_exists("pmpro_hasMembershipLevel"))
	{
		add_settings_field('pmprosms_option_admin_phone', 'Admin Mobile Number', 'pmprosms_option_admin_phone', 'pmprosms_options', 'pmprosms_section_general');	
		add_settings_field('pmprosms_option_admin_carrier', 'Admin Mobile Carrier', 'pmprosms_option_admin_carrier', 'pmprosms_options', 'pmprosms_section_general');	
		add_settings_field('pmprosms_option_sms_levels', 'Membership Levels', 'pmprosms_option_sms_levels', 'pmprosms_options', 'pmprosms_section_general');
	}
}
add_action("admin_init", "pmprosms_admin_init");

function pmprosms_option_sms_levels()
{	
	pmprosms_getPMProLevels();
	global $pmprosms_levels;
	$options = get_option('pmprosms_options');
		
	if(isset($options['sms_levels']) && is_array($options['sms_levels']))
		$selected_levels = $options['sms_levels'];
	else
		$selected_levels = array();
	
	if(!empty($pmprosms_levels))
	{
		echo "<p>Select the levels below to receive text message notification when a new member completes checkout.</p>";
		echo "<select style='margin-top: 1em; min-width: 30%;' multiple='yes' name=\"pmprosms_options[sms_levels][]\">";
		foreach($pmprosms_levels as $level)
		{
			echo "<option value='" . $level->id . "' ";
			if(in_array($level->id, $selected_levels))
				echo "selected='selected'";
			echo ">" . $level->name . "</option>";
		}
		echo "</select>";
	}
	else
	{
		echo "No levels found.";
	}	
}

function pmprosms_option_admin_phone()
{
	$options = get_option('pmprosms_options');	
	?>
	<input type="text" name="pmprosms_options[admin_phone]" value="<?php echo $options['admin_phone']; ?>">
	<?php
}

function pmprosms_option_admin_carrier()
{
	$options = get_option('pmprosms_options');	

	global $pmprosms_mobile_carriers, $admin_carrier;
	if(!empty($pmprosms_mobile_carriers))
	{
		echo "<select style='min-width: 30%;' name=\"pmprosms_options[admin_carrier][]\">";
		foreach($pmprosms_mobile_carriers as $mobile_carrier => $mobile_carrier_address)
		{
			echo "<option value='" . $mobile_carrier_address . "' ";
			if($mobile_carrier_address == $admin_carrier)
				echo "selected='selected'";
			echo ">" . $mobile_carrier . "</option>";
		}
		echo "</select>";
	}
}

//set the pmprosms_levels array if PMPro is installed
function pmprosms_getPMProLevels()
{	
	global $pmprosms_levels, $wpdb;	
	if(!empty($wpdb->pmpro_membership_levels))
		$pmprosms_levels = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id");			
	else
		$pmprosms_levels = false;
		
	return $pmprosms_levels;
}

//options sections
function pmprosms_section_general()
{	
?>
<p></p>
<?php
}


// validate our options
function pmprosms_options_validate($input) 
{
	$newinput['admin_phone'] = preg_replace("[^a-zA-Z0-9\-]", "", $input['admin_phone']);
	$newinput['admin_carrier'] = preg_replace("[^a-zA-Z0-9\-]", "", $input['admin_carrier']);
	
	//selected notification levels
	if(!empty($input['sms_levels']) && is_array($input['sms_levels']))
	{
		$count = count($input['sms_levels']);
		for($i = 0; $i < $count; $i++)
			$newinput['sms_levels'][] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['sms_levels'][$i]));	;
	}

	return $newinput;
}		

// add the admin options page	
function pmprosms_admin_add_page() 
{
	add_options_page('PMPro Member Checkout Text Message', 'PMPro Text Message', 'manage_options', 'pmprosms_options', 'pmprosms_options_page');
}
add_action('admin_menu', 'pmprosms_admin_add_page');

//html for options page
function pmprosms_options_page()
{
	global $pmprosms_levels, $msg, $msgt;
	
	//get options
	$options = get_option("pmprosms_options");
		
	//defaults
	if(empty($options))
	{
		update_option("pmprosms_options", $options);
	}
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Paid Memberships Pro - Member Checkout Admin Text Message Settings</h2>		
	
	<?php if(!empty($msg)) { ?>
		<div class="message <?php echo $msgt; ?>"><p><?php echo $msg; ?></p></div>
	<?php } ?>
	
	<form action="options.php" method="post">
			
		<?php settings_fields('pmprosms_options'); ?>
		<?php do_settings_sections('pmprosms_options'); ?>

		<p><br /></p>
						
		<div class="bottom-buttons">
			<input type="hidden" name="pmprot_options[set]" value="1" />
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Settings'); ?>">				
		</div>
		
	</form>
</div>
<?php
}

/*
Function to add links to the plugin action links
*/
function pmprosms_add_action_links($links) {
	
	$new_links = array(
			'<a href="' . get_admin_url(NULL, 'options-general.php?page=pmprosms_options') . '">Settings</a>',
	);
	return array_merge($new_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pmprosms_add_action_links');

/*
Function to add links to the plugin row meta
*/
function pmprosms_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-member-checkout-text-message.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-member-checkout-text-message/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprosms_plugin_row_meta', 10, 2);
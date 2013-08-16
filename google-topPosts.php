<?php
/*
  Plugin Name: Mindvalley Top Post Ranks
  Plugin URI: http://www.example.com
  Description: My first of many plugins,will be used to rank most viewed pages recived from google analytics
  Author: Inah B. Afen
  Version: 0.3
  Author URI: http://www.hire-inah.tk
  License: GPL2
 */

define('GOOGLE_MV-ANALYTICS_VERSION', '0.3');

define('GOOGLE_MV-ANALYTICS_CLIENTID', '920779528356-9p3ollu248gk117f2prle6vt0osh781r.apps.googleusercontent.com');
define('GOOGLE_MV-ANALYTICS_CLIENTSECRET', 'vsedOYCCFDcgYA2UoFsFttE3'); //don't worry - this don't need to be secret in our case
define('GOOGLE_MV-ANALYTICS_REDIRECT', 'urn:ietf:wg:oauth:2.0:oob');
define('GOOGLE_MV-ANALYTICS_SCOPE', 'https://www.googleapis.com/auth/analytics.readonly');

// Constants for enabled/disabled state
define("topPost_enabled", "enabled", true);
define("topPost_disabled", "disabled", true);

// Defaults, etc.
define("key_topPost_uid", "topPost_uid", true);
define("key_topPost_status", "topPost_status", true);
define("key_topPost_admin", "topPost_admin_status", true);
define("key_topPost_admin_disable", "topPost_admin_disable", true);
define("key_topPost_admin_role", "topPost_admin_role", true);
define("key_topPost_dashboard_role", "topPost_dashboard_role", true);
define("key_topPost_adsense", "topPost_adsense", true);
define("key_topPost_extra", "topPost_extra", true);
define("key_topPost_extra_after", "topPost_extra_after", true);
define("key_topPost_event", "topPost_event", true);
define("key_topPost_outbound", "topPost_outbound", true);
define("key_topPost_outbound_prefix", "topPost_outbound_prefix", true);
define("key_topPost_downloads", "topPost_downloads", true);
define("key_topPost_downloads_prefix", "topPost_downloads_prefix", true);
define("key_topPost_widgets", "topPost_widgets", true);
define("key_topPost_annon", "topPost_annon", true);

define("topPost_uid_default", "UA-XXXXXXXX-X", true);
define("topPost_google_token_default", "", true);
define("topPost_status_default", topPost_disabled, true);
define("topPost_admin_default", topPost_enabled, true);
define("topPost_admin_disable_default", 'remove', true);
define("topPost_adsense_default", "", true);
define("topPost_extra_default", "", true);
define("topPost_extra_after_default", "", true);
define("topPost_event_default", topPost_enabled, true);
define("topPost_outbound_default", topPost_enabled, true);
define("topPost_outbound_prefix_default", 'outgoing', true);
define("topPost_downloads_default", "", true);
define("topPost_downloads_prefix_default", "download", true);
define("topPost_widgets_default", topPost_enabled, true);

// Create the default key and status
add_option( 'topPost_version', GOOGLE_MV-ANALYTICS_VERSION );
add_option(key_topPost_status, topPost_status_default, '');
add_option(key_topPost_uid, topPost_uid_default, '');
add_option(key_topPost_admin, topPost_admin_default, '');
add_option(key_topPost_admin_disable, topPost_admin_disable_default, '');
add_option(key_topPost_admin_role, array('administrator'), '');
add_option(key_topPost_dashboard_role, array('administrator'), '');
add_option(key_topPost_adsense, topPost_adsense_default, '');
add_option(key_topPost_extra, topPost_extra_default, '');
add_option(key_topPost_extra_after, topPost_extra_after_default, '');
add_option(key_topPost_event, topPost_event_default, '');
add_option(key_topPost_outbound, topPost_outbound_default, '');
add_option(key_topPost_outbound_prefix, topPost_outbound_prefix_default, '');
add_option(key_topPost_downloads, topPost_downloads_default, '');
add_option(key_topPost_downloads_prefix, topPost_downloads_prefix_default, '');
add_option(key_topPost_widgets, topPost_widgets_default, '');
add_option(key_topPost_annon, false );
add_option('topPost_defaults', 'yes' );
add_option('topPost_google_token', '', '');

 $useAuth = ( get_option( 'topPost_google_token' ) == '' ? false : true );


# Check if we have a version of WordPress greater than 2.8
if ( function_exists('register_widget') ) {

	# Check if widgets are enabled and the auth has been set!
	if ( get_option(key_topPost_widgets) == 'enabled'  && $useAuth ) {

		# Include Google Analytics Stats widget
		require_once('google-analytics-stats-widget.php');

//		# Include the Google Analytics Summary widget
//		require_once('google-analytics-summary-widget.php');
//		$google_analytics_summary = new GoogleAnalyticsSummary();

	}

}

// Create a option page for settings
add_action('admin_init', 'topPost_admin_init');
add_action('admin_menu', 'add_topPost_option_page');

// Initialize the options
function topPost_admin_init() {
	# Load the localization information
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('google-topPosts', 'wp-content/plugins/' . $plugin_dir . '/localizations', $plugin_dir . '/localizations');
}

# Add the core Google Analytics script, with a high priority to ensure last script for async tracking
add_action('wp_head', 'add_google_analytics',99);
add_action('login_head', 'add_google_analytics', 999999);

# Initialize outbound link tracking
add_action('init', 'topPost_outgoing_links');

// Hook in the options page function
function add_topPost_option_page() {

	$plugin_page = add_options_page(__('Google Top-Posts Settings', 'google-topPosts'), 'Google Analytics', 'manage_options', basename(__FILE__), 'topPost_options_page');
	add_action('load-'.$plugin_page, 'topPost_pre_load' );

        $activate_page = add_submenu_page( null, 'Activation', 'Google Analytics', 'manage_options', 'topPost_activate' , 'topPost_activate');


        $reset_page = add_submenu_page(null, 'Reset', 'Reset', 'activate_plugins', 'topPost_reset', 'topPost_reset' );
        add_action('load-'.$reset_page, 'topPost_do_reset' );

}

add_action('plugin_action_links_' . plugin_basename(__FILE__), 'topPost_filter_plugin_actions');

function topPost_pre_load()
{

    if( isset( $_POST['key_topPost_google_token'] ) ):

        check_admin_referer('google-topPosts-update_settings');

        // Nolonger defaults
        update_option('topPost_defaults', 'no');

        // Update GA Token
        update_option('topPost_google_token', $_POST['key_topPost_google_token']);


    endif;

    if( get_option('topPost_defaults') == 'yes' ):

        wp_redirect( admin_url('options-general.php?page=topPost_activate') );
        exit;

    endif;
}

function topPost_activate()
{

if (! function_exists('curl_init')) {
  print('Google PHP API Client requires the CURL PHP extension');
  return;
}

if (! function_exists('json_decode')) {
  print('Google PHP API Client requires the JSON PHP extension');
  return;
}

if (! function_exists('http_build_query')) {
  print('Google PHP API Client requires e PHP APIhttp_build_query()');
  return;
}

$url = http_build_query( array(
                                'next' => admin_url('/options-general.php?page=google-topPosts.php'),
                                'scope' =>'https://www.googleapis.com/auth/analytics.readonly',
                                'response_type'=>'code',
                                'redirect_uri'=>'urn:ietf:wg:oauth:2.0:oob',
                                'client_id'=>'920779528356-9p3ollu248gk117f2prle6vt0osh781r.apps.googleusercontent.com'
                                )
                        );

    ?>
    <div class="wrap">

        <h2>Activate Google Analytics</h2>

            <p><strong>Google Authentication Code </strong> </p>

        <p>You need to sign in to Google and grant this plugin access to your Google Analytics account</p>

        <p>
            <a
                onclick="window.open('https://accounts.google.com/o/oauth2/auth?<?php echo $url ?>', 'activate','width=700, height=600, menubar=0, status=0, location=0, toolbar=0')"
                target="_blank"
                href="javascript:void(0);"> Click Here </a> - <small> Or <a target="_blank" href="https://accounts.google.com/o/oauth2/auth?<?php echo $url ?>">here</a> if you have popups blocked</small>
        </p>

        <div  id="key">

            <p>Enter your Google Authentication Code in this box. This code will be used to get an Authentication Token so you can access your website stats.</p>
            <form method="post" action="<?php echo admin_url('options-general.php?page=google-topPosts.php');?>">
                <?php wp_nonce_field('google-topPosts-update_settings'); ?>
                <input type="text" name="key_topPost_google_token" value="" style="width:420px;"/>
                <input type="submit"  value="Save &amp; Continue" />
            </form>
        </div>

		<br /><br /><br />
		<hr />
		<br />

<!--            <p><strong>I Don't Want To Authenticate Through Google </strong> </p>
            
            <p>If you don't want to authenticate through Google and only use the tracking capability of the plugin (<strong><u>not the dashboard functionality</u></strong>), you can do this by clicking the button below. </p>
            <p>You will be asked on the next page to manually enter your Google Analytics UID.</p>
            <form method="post" action="<?php echo admin_url('options-general.php?page=google-topPosts.php');?>">
            <input type="hidden" name="key_topPost_google_token" value="" />
            <?php wp_nonce_field('google-topPosts-update_settings'); ?>
            <input type="submit"  value="Continue Without Authentication" />
            </form>-->


    </div>

    <?php
}

// Add settings option
function topPost_filter_plugin_actions($links) {
	$new_links = array();

	$new_links[] = '<a href="' . admin_url('options-general.php?page=google-topPosts.php').'">' . __('Settings', 'google-topPosts') . '</a>';
        $new_links[] = '<a href="' . admin_url('options-general.php?page=topPost_reset">') . __('Reset', 'google-topPosts') . '</a>';

	return array_merge($new_links, $links);
}

function topPost_do_reset()
{
    global $wpdb;
    
    // Delete all GA options.
    delete_option(key_topPost_status);
    delete_option(key_topPost_uid);
    delete_option(key_topPost_admin);
    delete_option(key_topPost_admin_disable);
    delete_option(key_topPost_admin_role);
    delete_option(key_topPost_dashboard_role);
    delete_option(key_topPost_adsense);
    delete_option(key_topPost_extra);
    delete_option(key_topPost_extra_after);
    delete_option(key_topPost_event);
    delete_option(key_topPost_outbound);
    delete_option(key_topPost_outbound_prefix);
    delete_option(key_topPost_downloads);
    delete_option(key_topPost_downloads_prefix);
    delete_option(key_topPost_widgets);
    delete_option(key_topPost_annon);
    delete_option('topPost_defaults');
    delete_option('topPost_google_token');
    delete_option('topPost_google_authtoken');
    delete_option('topPost_profileid');
    delete_transient('topPost_admin_stats_widget');
    
    // Need to remove cached items from GA widgets 
    $wpdb->query( "delete from $wpdb->options where `option_name` like 'google_stats_visitsGraph_%'");
 
    wp_redirect( admin_url( 'options-general.php?page=topPost_activate' ) );
    exit;
}

function topPost_reset(){ /* Wont ever run. */ }


function topPost_options_page() {

	// If we are a postback, store the options
	if (isset($_POST['info_update'])) {
		# Verify nonce
		check_admin_referer('google-topPosts-update_settings');

                update_option('topPost_defaults', 'no');


		// Update the status
		$topPost_status = wp_filter_kses( $_POST[key_topPost_status] );
		if (($topPost_status != topPost_enabled) && ($topPost_status != topPost_disabled))
			$topPost_status = topPost_status_default;
		update_option(key_topPost_status, $topPost_status);

		// Update the UID
		$topPost_uid = wp_filter_kses( $_POST[key_topPost_uid] );
		if ($topPost_uid == '')
			$topPost_uid = topPost_uid_default;
		update_option(key_topPost_uid, $topPost_uid);

		
		// Update the event tracking
		$topPost_event = $_POST[key_topPost_event];
		if (($topPost_event != topPost_enabled) && ($topPost_event != topPost_disabled))
			$topPost_event = topPost_event_default;
		update_option(key_topPost_event, wp_filter_kses ( $topPost_event ) );

		
	}


        // Are we using the auth system?
        $useAuth = ( get_option( 'topPost_google_token' ) == '' ? false : true );


	// Output the options page
	?>

		<div class="wrap">
                    <div class="icon32" id="icon-options-general"></div>
		<h2><?php _e('Google Top-Posts Settings', 'google-topPosts'); ?></h2>
		<form method="post" action="<?php echo admin_url('options-general.php?page=google-topPosts.php');?>">
			<?php
			# Add a nonce
			wp_nonce_field('google-topPosts-update_settings');
			?>

			<?php if (get_option(key_topPost_status) == topPost_disabled) { ?>
				<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
				<?php _e('Google Analytics integration is currently <strong>DISABLED</strong>.', 'google-topPosts'); ?>
				</div>
			<?php } ?>
			<?php if ((get_option(key_topPost_uid) == "XX-XXXXX-X") && (get_option(key_topPost_status) != topPost_disabled)) { ?>
				<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
				<?php _e('Google Analytics integration is currently enabled, but you did not enter a UID. Tracking will not occur.', 'google-topPosts'); ?>
				</div>
			<?php } ?>
			<table class="form-table" cellspacing="2" cellpadding="5" width="100%">

                            <tr>
                                <td colspan="2">
                                    <h3><?php _e('Basic Settings', 'google-topPosts'); ?></h3>
                                </td>
                            </tr>

				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for="<?php echo key_topPost_status ?>"><?php _e('Google Analytics logging is', 'google-topPosts'); ?>:</label>
					</th>
					<td>
						<?php
						echo "<select name='".key_topPost_status."' id='".key_topPost_status."'>\n";

						echo "<option value='".topPost_enabled."'";
						if(get_option(key_topPost_status) == topPost_enabled)
							echo " selected='selected'";
						echo ">" . __('Enabled', 'google-topPosts') . "</option>\n";

						echo "<option value='".topPost_disabled."'";
						if(get_option(key_topPost_status) == topPost_disabled)
							echo" selected='selected'";
						echo ">" . __('Disabled', 'google-topPosts') . "</option>\n";

						echo "</select>\n";
						?>
					</td>
				</tr>
				<tr id="topPost_ajax_accounts">
					<th valign="top" style="padding-top: 10px;">
						<label for="<?php echo key_topPost_uid; ?>"><?php _e('Google Analytics UID', 'google-topPosts'); ?>:</label>
					</th>
					<td>
                                            <?php

                                            if( $useAuth ):
                                                
                                                $uids = topPost_get_analytics_accounts();

                                                echo "<select name='".key_topPost_uid."'> ";

                                                $hasSelected = false; // Will be set to true once a match is found. Cant echo selected twice.

                                                foreach($uids as $id=>$domain):

                                                    echo '<option value="'.$id.'"';
                                                    // If set in DB.
                                                    if( get_option(key_topPost_uid) == $id ) { $hasSelected=true; echo ' selected="selected"'; }
                                                    // Else if the domain matches the current domain & nothing set in DB.
                                                    elseif( ( $_SERVER['HTTP_HOST'] == $domain ) && ( ! $hasSelected ) ) { $hasSelected=true; echo ' selected="selected"'; }
                                                    echo '>'.$domain.'</option>';

                                                endforeach;
                                                
                                                echo '</select>';

                                            else:

                                                echo '<input type="text" name="'.key_topPost_uid.'" value="'. get_option( key_topPost_uid ) .'" />';

                                            endif;
                                            ?>
					</td>
				</tr>
                                

                                <tr>
                                    <td colspan="2">
                                        <h3>Link Tracking Settings</h3>
                                    </td>
                                </tr>

				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for="<?php echo key_topPost_event ?>"><?php _e('Event tracking', 'google-topPosts'); ?>:</label>
					</th>
					<td>
						<?php
						echo "<select name='".key_topPost_event."' id='".key_topPost_event."'>\n";

						echo "<option value='".topPost_enabled."'";
						if(get_option(key_topPost_event) == topPost_enabled)
							echo " selected='selected'";
						echo ">" . __('Enabled', 'google-topPosts') . "</option>\n";

						echo "<option value='".topPost_disabled."'";
						if(get_option(key_topPost_event) == topPost_disabled)
							echo" selected='selected'";
						echo ">" . __('Disabled', 'google-topPosts') . "</option>\n";

						echo "</select>\n";
						?>
						<p  class="setting-description"><?php _e('Enabling this option will treat outbound links and downloads as events instead of pageviews. Since the introduction of <a href="https://developers.google.com/analytics/devguides/collection/gajs/eventTrackerGuide">event tracking in Analytics</a>, this is the recommended way to track these types of actions. Only disable this option if you must use the old pageview tracking method.', 'google-topPosts'); ?></p>
					</td>
				</tr>


				</table>
			<p class="submit">
				<input type="submit" class="button button-primary" name="info_update" value="<?php _e('Save Changes', 'google-topPosts'); ?>" />
			</p>

                        <a href="<?php echo admin_url('/options-general.php?page=topPost_reset'); ?>"><?php _e('Deauthorize &amp; Reset Google Top-Posts.', 'google-topPosts'); ?></a>

                </form>

                 </form>
 
 
<?php  if (!get_option('wpm_o_user_id')): ?>
<!--    <img src="<?php echo plugins_url('ga-plugin-advert.jpg', __FILE__ ); ?>" alt="Google Analytics Getting It Right" />
    <form accept-charset="utf-8" action="https://app.getresponse.com/add_contact_webform.html" method="post" onsubmit="return quickValidate()" target="_blank">
    <div style="display: none;">
        <input type="hidden" name="webform_id" value="416798" />
    </div>
    <table style="text-align:center;margin-left: 20px;">
    <tr>
    <td><label class="previewLabel" for="awf_field-37978044"><strong>Name: </strong></label><input id="sub_name" type="text" name="name" class="text"  tabindex="500" value="" /></td>
    <td><label class="previewLabel" for="awf_field-37978045"><strong>Email: </strong></label> <input class="text" id="sub_email" type="text" name="email" tabindex="501"  value="" /></td>
    <td><span class="submit"><input name="submit" type="image" alt="submit" tabindex="502" src="<?php echo plugins_url('download-button.png', __FILE__); ?>" width="157" height="40" style="background: none; border: 0;" /></span></td>
    </tr>
    <tr>
    <td colspan="3" style="padding-top: 20px;">
    <a title="Privacy Policy" href="http://www.getresponse.com/permission-seal?lang=en" target="_blank"><img src="<?php echo plugins_url('privacy.png', __FILE__); ?>"  alt="" title="" /></a>
    </td>
    </tr>
    </table>
    </form>-->

    <script type="text/javascript">
	function quickValidate()
	{
	        if (! jQuery('#sub_name').val() )
	            {
	                alert('Your Name is required');
	                return false;
	            }
	        if(! jQuery('#sub_email').val() )
	            {
	                alert('Your Email is required');
	                return false;
	            }

	            return true;
	}
	</script>
<?php endif;?>


<?php
}

function topPost_sort_account_list($a, $b) {
	return strcmp($a['title'],$b['title']);
}

/**
 * Checks if the WordPress API is a valid method for selecting an account
 *
 * @return a list of accounts if available, false if none available
 **/
function topPost_get_analytics_accounts()
{
	$accounts = array();

	# Get the class for interacting with the Google Analytics
	require_once('class.analytics.stats.php');

	# Create a new Gdata call
	if ( isset($_POST['token']) && $_POST['token'] != '' )
		$stats = new GoogleAnalyticsStats($_POST['token']);
	elseif ( trim(get_option('topPost_google_token')) != '' )
		$stats = new GoogleAnalyticsStats();
	else
		return false;

	# Check if Google sucessfully logged in
	if ( ! $stats->checkLogin() )
		return false;

	# Get a list of accounts
	$accounts = $stats->getAllProfiles();

        natcasesort ($accounts);

	# Return the account array if there are accounts
	if ( count($accounts) > 0 )
		return $accounts;
	else
		return false;
}

/**
 * Add http_build_query if it doesn't exist already
 **/
if ( !function_exists('http_build_query') ) {
	function http_build_query($params, $key = null)
	{
		$ret = array();

		foreach( (array) $params as $name => $val ) {
			$name = urlencode($name);

			if ( $key !== null )
				$name = $key . "[" . $name . "]";

			if ( is_array($val) || is_object($val) )
				$ret[] = http_build_query($val, $name);
			elseif ($val !== null)
				$ret[] = $name . "=" . urlencode($val);
		}

		return implode("&", $ret);
	}
}

/**
 * Echos out the core Analytics tracking code
 **/
function add_google_analytics()
{
	# Fetch variables used in the tracking code
	$uid = stripslashes(get_option(key_topPost_uid));
	$extra = stripslashes(get_option(key_topPost_extra));
	$extra_after = stripslashes(get_option(key_topPost_extra_after));
	$extensions = str_replace (",", "|", get_option(key_topPost_downloads));

	# Determine if the GA is enabled and contains a valid UID
	if ( ( get_option(key_topPost_status) != topPost_disabled ) && ( $uid != "XX-XXXXX-X" ) )
	{
		# Determine if the user is an admin, and should see the tracking code
		if ( ( get_option(key_topPost_admin) == topPost_enabled || !topPost_current_user_is(get_option(key_topPost_admin_role)) ) && get_option(key_topPost_admin_disable) == 'remove' || get_option(key_topPost_admin_disable) != 'remove' )
		{
			# Disable the tracking code on the post preview page
			if ( !function_exists("is_preview") || ( function_exists("is_preview") && !is_preview() ) )
			{
				# Add the notice that Google Top-Posts tracking is enabled
				//echo "<!-- Google Analytics Tracking by Google Top-Posts " . GOOGLE_MV-ANALYTICS_VERSION . ": http://www.videousermanuals.com/google-topPosts/ -->\n";

				# Add the Adsense data if specified
				if ( get_option(key_topPost_adsense) != '' )
					echo '<script type="text/javascript">window.google_analytics_uacct = "' . get_option(key_topPost_adsense) . "\";</script>\n";

				# Include the file types to track
				$extensions = explode(',', stripslashes(get_option(key_topPost_downloads)));
				$ext = "";
				foreach ( $extensions AS $extension )
					$ext .= "'$extension',";
				$ext = substr($ext, 0, -1);

				# Include the link tracking prefixes
				$outbound_prefix = stripslashes(get_option(key_topPost_outbound_prefix));
				$downloads_prefix = stripslashes(get_option(key_topPost_downloads_prefix));
				$event_tracking = get_option(key_topPost_event);
                                
                                
                                $need_to_annon = get_option(key_topPost_annon);
 
                                
				?>
<script type="text/javascript">
	var analyticsFileTypes = [<?php echo strtolower($ext); ?>];
<?php if ( $event_tracking != 'enabled' ) { ?>
	var analyticsOutboundPrefix = '/<?php echo $outbound_prefix; ?>/';
	var analyticsDownloadsPrefix = '/<?php echo $downloads_prefix; ?>/';
<?php } ?>
	var analyticsEventTracking = '<?php echo $event_tracking; ?>';
</script>
<?php
				# Add the first part of the core tracking code
				?>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '<?php echo $uid; ?>']);
       // _gaq.push(['_addDevId', 'i9k95']);  Google Top-Posts App ID with Google 
<?php if ($need_to_annon == '1' ): ?>
        _gaq.push(['_gat._anonymizeIp']);
<?php endif; ?>        
<?php

    # Add any tracking code before the trackPageview
    do_action('google_topPosts_extra_js_before');
    if ( '' != $extra )
            echo "	$extra\n";

    # Add the track pageview function
    echo "	_gaq.push(['_trackPageview']);\n";

    # Disable page tracking if admin is logged in
    if ( ( get_option(key_topPost_admin) == topPost_disabled ) && ( topPost_current_user_is(get_option(key_topPost_admin_role)) ) )
            echo "	_gaq.push(['_setCustomVar', 'admin']);\n";

    # Add any tracking code after the trackPageview
    do_action('google_topPosts_extra_js_after');
    if ( '' != $extra_after )
            echo "	$extra_after\n";

    # Add the final section of the tracking code
    ?>

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>
<?php
			}
		} else {
			# Add the notice that Google Top-Posts tracking is enabled
			
			echo "	<!-- " . __('Tracking code is hidden, since the settings specify not to track admins. Tracking is occurring for non-admins.', 'google-topPosts') . " -->\n";
		}
	}
}

/**
 * Adds outbound link tracking to Google Top-Posts
 **/
function topPost_outgoing_links()
{
	# Fetch the UID
	$uid = stripslashes(get_option(key_topPost_uid));

	# Check if GA is enabled and has a valid key
	if (  (get_option(key_topPost_status) != topPost_disabled ) && ( $uid != "XX-XXXXX-X" ) )
	{
		# If outbound tracking is enabled
		if ( get_option(key_topPost_outbound) == topPost_enabled )
		{
			# If this is not an admin page
			if ( !is_admin() )
			{
				# Display page tracking if user is not an admin
				if ( ( get_option(key_topPost_admin) == topPost_enabled || !topPost_current_user_is(get_option(key_topPost_admin_role)) ) && get_option(key_topPost_admin_disable) == 'remove' || get_option(key_topPost_admin_disable) != 'remove' )
				{
					add_action('wp_print_scripts', 'topPost_external_tracking_js',99999);
				}
			}
		}
	}
}

/**
 * Adds the scripts required for outbound link tracking
 **/
function topPost_external_tracking_js()
{
	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	wp_enqueue_script('ga-external-tracking', plugins_url("/google-topPosts/external-tracking{$suffix}.js"), array('jquery'), GOOGLE_MV-ANALYTICS_VERSION);
}

/**
 * Determines if a specific user fits a role
 **/
function topPost_current_user_is($roles)
{
	if ( !$roles ) return false;

	global $current_user;
	get_currentuserinfo();
	$user_id = intval( $current_user->ID );

	if ( !$user_id ) {
		return false;
	}
	$user = new WP_User($user_id); // $user->roles

	foreach ( $roles as $role )
		if ( in_array($role, $user->roles) ) return true;

	return false;
}

<?php


class GoogleStatsWidget extends WP_Widget
{
    var $api = false;
    var $id = false;
    var $qa_selecteddate, $date_before, $date_yesterday;
	function GoogleStatsWidget() {
		$widget_ops = array('classname' => 'widget_google_stats', 'description' => __("Top viewed pages retrived from google ", 'google-topPosts') );
		$control_ops = array('width' => 500, 'height' => 400);
                
		$this->WP_Widget('googlestats', __('Google Analytics Stats', 'google-topPosts'), $widget_ops, $control_ops);
	}
	
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
		$acnt = false;
		$timeFrame = empty($instance['timeFrame']) ? '1' : $instance['timeFrame'];
		$pageBg = empty($instance['pageBg']) ? 'fff' : $instance['pageBg'];
		$widgetBg = empty($instance['widgetBg']) ? '999' : $instance['widgetBg'];
		$innerBg = empty($instance['innerBg']) ? 'fff' : $instance['innerBg'];
		$font = empty($instance['font']) ? '333' : $instance['font'];
		$line1 = empty($instance['line1']) ? 'Unique' : $instance['line1'];
		$line2 = empty($instance['line2']) ? 'Visitors' : $instance['line2'];
		
                #update retrival date range
                $this->qa_selecteddate = isset( $timeFrame ) ? wp_filter_kses( $timeFrame ) : '31';
        $this->date_before    = date('Y-m-d', strtotime( '-'.$this->qa_selecteddate.' days', strtotime( current_time( 'mysql' ) ) ) );
        $this->date_yesterday = date('Y-m-d', strtotime( '-1 days', strtotime( current_time( 'mysql' ) ) ) );
                
		# Before the widget
		echo $before_widget;
		
		# The title
		if ( $title )
			echo $before_title . $title . $after_title;
		
		# Make the stats chicklet
		//echo '<!-- Data gathered from last ' . number_format($timeFrame) . ' days using Google Analyticator -->';
		$this->initiateBackground($pageBg, $font);
		$this->beginWidget($font, $widgetBg);
		$this->getUniqueVisitors($acnt, $timeFrame);
		$this->endWidget();
		
		# After the widget
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['account'] = strip_tags(stripslashes($new_instance['account']));
		$instance['timeFrame'] = strip_tags(stripslashes($new_instance['timeFrame']));
		$instance['pageBg'] = strip_tags(stripslashes($new_instance['pageBg']));
		$instance['widgetBg'] = strip_tags(stripslashes($new_instance['widgetBg']));
		$instance['innerBg'] = strip_tags(stripslashes($new_instance['innerBg']));
		$instance['font'] = strip_tags(stripslashes($new_instance['font']));
		$instance['line1'] = strip_tags(stripslashes($new_instance['line1']));
		$instance['line2'] = strip_tags(stripslashes($new_instance['line2']));
		
		return $instance;
	}
	
	function form($instance) {
		//Defaults array content
		$instance = wp_parse_args( (array) $instance, array('title'=>'', 'account'=>'', 'timeFrame'=>'1', 'pageBg'=>'fff', 'widgetBg'=>'999', 'innerBg'=>'fff', 'font'=>'333', 'line1'=>'Unique', 'line2'=>'Visitors') );
		
		$title = htmlspecialchars($instance['title']);
		$acnt = htmlspecialchars($instance['account']);
		$timeFrame = htmlspecialchars($instance['timeFrame']);
		$pageBg = htmlspecialchars($instance['pageBg']);
		$widgetBg = htmlspecialchars($instance['widgetBg']);
		$innerBg = htmlspecialchars($instance['innerBg']);
		$font = htmlspecialchars($instance['font']);
		$line1 = htmlspecialchars($instance['line1']);
		$line2 = htmlspecialchars($instance['line2']);
		
		$accounts = array();
		
		# Get the current memory limit
		$current_mem_limit = substr(ini_get('memory_limit'), 0, -1);

		# Check if this limit is less than 96M, if so, increase it
		if ( $current_mem_limit < 96 || $current_mem_limit == '' ) {
			if ( function_exists('memory_get_usage') )
				@ini_set('memory_limit', '96M');
		}
		
		# Get the class for interacting with the Google Analytics
		require_once('class.analytics.stats.php');

		# Create a new Gdata call
		$stats = new GoogleAnalyticsStats();
		
		# Check if Google sucessfully logged in
		$login = $stats->checkLogin();
	
                if( !$login )
                    return false; 

		# Get a list of accounts
		//$accounts = $stats->getAnalyticsAccounts();
		$accounts = $stats->getSingleProfile();


		
		# Output the options
		echo '<p style="text-align:right;"><label for="' . $this->get_field_name('title') . '">' . __('Title', 'google-topPosts') . ': <input style="width: 250px;" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';
		# Time frame
		echo '<p style="text-align:right;"><label for="' . $this->get_field_name('timeFrame') . '">' . __('Days of data to get', 'google-topPosts') . ': <input style="width: 150px;" id="' . $this->get_field_id('timeFrame') . '" name="' . $this->get_field_name('timeFrame') . '" type="text" value="' . $timeFrame . '" /></label></p>';		
		# Page background
		
	}
        
        
        function getAnalyticsAccount()
    {
        $accounts = array();
        
        # Get the class for interacting with the Google Analytics
        require_once('class.analytics.stats.php');
        
        # Create a new Gdata call
        $this->api = new GoogleAnalyticsStats();
        
        # Check if Google sucessfully logged in
        if (!$this->api->checkLogin())
            return false;
        
        # Get a list of accounts
        //$accounts = $this->api->getAnalyticsAccounts();
        $accounts = $this->api->getSingleProfile();
        
        # Check if we actually have accounts
        if (!is_array($accounts))
            return false;
        
        # Check if we have a list of accounts
        if (count($accounts) <= 0)
            return false;
        
        # Loop throught the account and return the current account
        foreach ($accounts AS $account) {
            # Check if the UID matches the selected UID
            if ($account['ga:webPropertyId'] == get_option('topPost_uid'))
                return $account['id'];
        }
        
        return false;
    }
	
	/**
	 * This function is used to display the background color behind the widget. This is necessary
	 * for the Google Analytics text to have the same background color as the page.
	 *
	 * @param $font_color - Hexadecimal value for the font color used within the Widget (does not effect "Powered By Google Analytics Text"). This effects border color as well.
	 * @param $page_background_color - Hexadecimal value for the page background color
	 * @return void
	 **/
	function initiateBackground($page_background_color = 'FFF', $font_color = '000')
	{
		echo '<br />';
		echo '<div style="background:#' . $page_background_color . ';font-size:12px;color:#' . $font_color . ';font-family:\'Lucida Grande\',Helvetica,Verdana,Sans-Serif;">';
	}

	/**
	 * This function starts the widget. The font color and widget background color are customizable.
	 *
	 * @param $font_color - Hexadecimal value for the font color used within the Widget (does not effect "Powered By Google Analytics Text"). This effects border color as well.
	 * @param $widget_background_color - Hexadecimal value for the widget background color.
	 * @return void
	 **/
	function beginWidget($font_color = '000', $widget_background_color = 'FFF')
	{
		echo '<table style="width:auto!important;border-width:2px;border-color:#' . $font_color . ';border-style:solid;background:#' . $widget_background_color . ';"><tr>';
	}

	/**
	 * This function encases the text that appears on the right hand side of the widget.
	 * Both lines of text are customizable by each individual user.
	 *
	 * It also displays the visitor count that was pulled from the user's Google Analytics account.
	 *
	 * @param $visitor_count - Number of unique visits to the site pulled from the user's Google Analytics account.
	 * @param $line_one - First line of text displayed on the right hand side of the widget.
	 * @param $line_two - Second line of text displayed on the right hand side of the widget.
	 * @param $inner_background_color - Hexadecimal value for the background color that surrounds the Visitor Count.
	 * @param $font_color - Hexadecimal value for the font color used within the Widget (does not effect "Powered By Google Analytics Text"). This effects border color as well
	 * @return void
	 **/
	function widgetInfo($visitor_count, $line_one = 'Unique', $line_two = 'Visitors', $inner_background_color = 'FFF', $font_color = '000')
	{

		//echo '<td style="width:auto!important;border-width:1px;border-color:#' . $font_color . ';border-style:solid;padding:0px 5px 0px 5px;text-align:right;background:#' . $inner_background_color . ';min-width:80px;*width:80px!important;"><div style="min-width:80px;">'. $visitor_count . '</div></td>';

		//echo '<td style="width:auto!important;padding:0px 5px 0px 5px;text-align:center;font-size:11px;">' . $line_one . '<br />' . $line_two . '</td>';

	}

	/**
	 * The function is used strictly for visual appearance. It also displays the Google Analytics text.
	 *
	 * @return void
	 **/
	function endWidget()
	{
		// This closes off the widget.
		echo '</tr></table>';

		}

	/**
	 * Grabs the cached value of the unique visits for the previous day
	 *
	 * @param account - the account to get the unique visitors from
	 * @param time - the amount of days to get
	 * @return void
	 **/
	function getUniqueVisitors($account, $time = 1)
	{
            // IF we have a cached version, return that, if not, continue on.
            if ( get_transient( 'google_pageview_stat' ) )
                return get_transient( 'google_pageview_stat' );

            # Get the class for interacting with the Google Analytics
            require_once('class.analytics.stats.php');
            
            
            $doing_transient = false; 
        
        if ( ( defined('WP_DEBUG') && WP_DEBUG ) || ( false === ( $topPost_output = get_transient( 'google_pageview_stat'. GOOGLE_MV-ANALYTICS_VERSION  . $this->qa_selecteddate) ) ) ) {
            ob_start();
            # Attempt to login and get the current account
            $account = $this->getAnalyticsAccount();

            $this->id = $account;

            $this->api->setAccount($this->id);

            # Check that we can display the widget before continuing
            if ($account == false || $this->id == false) {
                # Output error message
                echo '<p style="margin: 0;">' . __('No Analytics account selected. Double check you are authenticated with Google on plugin\'s settings page and make sure an account is selected.', 'google-topPosts') . '</p>';
                # Add Javascript variable to prevent breaking the Javascript
                echo '<script type="text/javascript">var topPost_visits = [];</script>';
                die;
            }
           
            # Add the top 10 posts
            $this->getTopPages();

           

            # Grab the above outputs and cache it!
            $topPost_output = ob_get_flush();

            // Cache the admin dashboard for 6 hours at a time. 
            set_transient( 'google_pageview_stat'. GOOGLE_MV-ANALYTICS_VERSION . $this->qa_selecteddate , $topPost_output, 60*60*6 );
            $doing_transient = true;

        } else {
			$topPost_output = get_transient( 'google_pageview_stat'. GOOGLE_MV-ANALYTICS_VERSION . $this->qa_selecteddate , $topPost_output);	
		}
         
        if( ! $doing_transient )
            echo $topPost_output;
        
        die();
	}

        
        /**
     * Get the top pages
     **/
    function getTopPages()
    {
        # Get the metrics needed to build the top pages
        $stats     = $this->api->getMetrics('ga:pageviews', $this->date_before, $this->date_yesterday, 'ga:pageTitle,ga:pagePath', '-ga:pageviews', 'ga:pagePath!=/', 10); //'ga:pagePath!%3D%2F'
        $rows = $stats->getRows();

        # Check the size of the stats array
        if (count($rows) <= 0 || !is_array($rows)) {
            $return = '<p>' . __('There is no data for view.', 'google-topPosts') . '</p>';
        } else {
            # Build the top pages list
            $return = '<ol>';

            # Set variables needed to correct (not set) bug
            $new_stats    = array();
            $notset_stats = array();

            # Loop through each stat and create a new array
            foreach ($rows AS $stat) {
                # If the stat is not set
                if ($stat[0] == '(not set)') {
                    # Add it to separate array
                    $notset_stats[] = $stat;
                } else {
                    # Add it to new array with index set
                    $new_stats[$stat[1]] = $stat;
                }
            }

            # Loop through all the (not set) stats and attempt to add them to their correct stat
            foreach ($notset_stats AS $stat) {
                # If the stat has a "partner"
                if ($new_stats[$stat[1]] != NULL) {
                    # Add the pageviews to the stat
                    $new_stats[$stat[1]][2] = $new_stats[$stat[1]][2] + $stat[2];
                } else {
                    # Stat goes to the ether since we couldn't find a partner (if anyone reads this and has a suggestion to improve, let me know)
                }
            }

            # Renew new_stats back to stats
            $stats = $new_stats;

            # Sort the stats array, since adding the (not set) items may have changed the order
            usort($stats, array(
                $this,
                'statSort'
            ));

            # Since we can no longer rely on the API as a limiter, we need to keep track of this ourselves
            $stat_count = 0;

            # Loop through each stat for display
            foreach ($stats AS $stat) {
                $return .= '<li><a href="' . esc_url($stat[1]) . '">' . esc_html($stat[0]) . '</a> </li>';

                # Increase the stat counter
                $stat_count++;

                # Stop at 10
                if ($stat_count >= 10)
                    break;
            }

            # Finish the list
            $return .= '</ol>';
        }
        echo $return; 
    }
    function statSort($x, $y)
    {
        if ($x[2] == $y[2])
            return 0;
        elseif ($x[2] < $y[2])
            return 1;
        else
            return -1;
    }
    
}// END class
	
/**
* Register Google Analytics Stat Widget.
*/
function GoogleStatsWidget_init() {
	register_widget('GoogleStatsWidget');
}	

add_action('widgets_init', 'GoogleStatsWidget_init');

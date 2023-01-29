<?php
/**
 * Plugin Name: CiviCRM Event Calendar
 * Plugin URI: https://github.com/prdufresne/civi-event-calendar
 * Description: Display CiviCRM events as schedule in WordPress
 * Version: 0.1
 * Text Domain: civi-event-calendar
 * Author: Paul Dufresne, Eastern Ontario TrailBlazers
 * Author URI: https://www.eotb.ca
 */

function civi_event_calendar($atts) {
	$Content = '<h3>Event Calendar</h3>';
	 
    return $Content;
}

add_shortcode('civi-event-calendar', 'civi_event_calendar');
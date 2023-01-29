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

 /*
  * Copyright 2023 Eastern Ontario TrailBlazers 4x4 Club Inc. (info@eotb.ca)
  * 
  * This program has been licensed using the MIT license included in this repository.
  *
 */

// This could probably be optimized to only enqueue the style sheet when the shortcode is present,
// but doing so has proven difficult.
function civi_event_calendar_enqueue() {
		wp_enqueue_style("civi-event-calendar-style", plugins_url("civi-event-calendar.css", __FILE__), array(), "1.0", "all");
}

add_action('init', 'civi_event_calendar_enqueue' );

function civi_event_calendar($user_atts = [], $content = null, $tag = '') {
    
    // Access CiviCRM data 
    require_once 'CRM/Utils/System.php';

	// Normalize attribute keys to lowercase
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );

    //Add default attributes and override with user attributes
    $atts = shortcode_atts(
        array(
            'showHeader' => 1,
            'header' => 'Event Calendar',
        ), $user_atts, $tag
    );

    // Open calendar object
    $Content = '<div class="civi-event-calendar">';

    // Add header
    if($atts['showHeader'] > 0) {
        $header = $atts['header'];
	    $Content .= "    <h3>$header</h3>";
    }

    $eventList = CRM_Event_BAO_Event::getCompleteInfo();
    $currentMonth = "";

    foreach ( $eventList as $event ) {
        $title = CRM_Utils_Array::value( 'title', $event );
        $summary = CRM_Utils_Array::value( 'summary', $event, '' );
        $url = CRM_Utils_Array::value( 'url', $event );

        $startString = CRM_Utils_Array::value( 'start_date', $event );
        $start = date_create_from_format('Y-m-d H:i:s',$startString);
        $startMonth = date_format($start, 'F');
        $startDay = date_format($start, 'j');
        $startWeekday = date_format($start, 'l');

        $endString = CRM_Utils_Array::value( 'start_date', $event );
        $end = date_create_from_format('Y-m-d H:i:s',$endString);
        $endMonth = date_format($end, 'F');
        $endDay = date_format($end, 'j');
        $endWeekday = date_format($end, 'l');

        // $Content .= "    <p>Title: $title</p>";
        // $Content .= "    <p>Event Date: $startWeekday $startMonth $startDay ($startString)</p>";

        // Insert a monthly header 
        if ($startMonth != $currentMonth) {
            $currentMonth = $startMonth;
            $content .= "<h3>$startMonth</h3>";
        }

        // Start the event row (we use a complete table for each event for formatting reasons);
        $row = "<table class=\"civi-event-calendar-event member\">";
        $row .= "<tbody";
        $row .= "<tr>";
        
        // Add the date block

        $row .= "<td class=\"civi-event-calendar-cell-date\">";
        if ($startDay == $endDay && $startMonth == $endMonth) {
            // single day event
            $row .= "<div class=\"civi-event-calendar-weekday\">$startWeekday</div>";
            $row .= "<div class=\"civi-event-calendar-day\">$startDay</div>";
        } else {
            // multi-day event
            $row .="<p>end date is $end</p>";
        }
        $row .= "</td>";

        // Add the title and summary

        $row .= "<td class=\"civi-event-calendar-cell-body\">";
        $row .= "<div class=\"civi-event-calendar-title\"><a href=\"$url\">$title</a></div>";
        $row .= "<div class=\"civi-event-calendar-description\">$summary</div>";
        $row .= "</td>";

        // Add the Register button

        $id = $event['event_id'];
        $reglink = CRM_Utils_System::url( 'civicrm/event/register', "reset=1&id=$id" );

        $row .= "<td class=\"civi-event-calendar-cell-register\">";
        $row .= "<div class=\"civi-event-calendar-register\" onclick=\"window.location.href='$reglink\'\">Register</div>";
        $row .= "</td>";

        // Close Row
        $row .= "</tr>";
        $row .= "</tbody>";
        $row .= "</table>";


        $Content .= $row;
    }

    // Close calendar object
    $Content .= '</div>';

    return $Content;
}

add_shortcode('civi-event-calendar', 'civi_event_calendar');
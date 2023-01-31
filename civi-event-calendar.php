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
    
	// Normalize attribute keys to lowercase
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );

    //Add default attributes and override with user attributes
    $atts = shortcode_atts(
        array(
            'showheader' => 1,
            'header' => 'Upcoming Events',
            'showical' => 1,
        ), $user_atts, $tag
    );

    // Open calendar object
    $Content = '<div class="civi-event-calendar">';

    // Add header
    if($atts['showheader'] > 0) {
        $header = $atts['header'];
	    $Content .= "    <h3>$header</h3>";
    }

    // Get the current user's contact ID
    // We'll use the ID to check if user is registered for an event and maybe other stuff...
    $current_user = wp_get_current_user();
    $userEmail = $current_user->user_email;

    $Content .="<div>Debug: user email: $userEmail</div>";

    $contactRecord = \Civi\Api4\UFMatch::get(FALSE)
        ->addSelect('*')
        ->addWhere('uf_name', '=', $userEmail)
        ->setLimit(25)
        ->execute();
    $contactId = $contactRecord[0]['contact_id'];


    $Content .="<div>Debug: user id: $contactId</div>";

    // Get events starting today or after ordered by start date
    $eventList = \Civi\Api4\Event::get(FALSE)
        ->addSelect('*', 'event_type_id:label', 'registration_link_text')
        ->addOrderBy('start_date', 'ASC')
        ->addOrderBy('end_date', 'ASC')
        ->addWhere('start_date', '>=', date('Y-m-d'))
        ->execute();
    $currentMonth = "";

    foreach ( $eventList as $event ) {
        $title = $event['title'];
        $summary = $event['summary'];
        $typeLabel = $event['event_type_id:label'];
        $id = $event['id'];
        $url = CRM_Utils_System::url( 'civicrm/event/info', "reset=1&id=$id" );

        $startString = $event['start_date'];
        $start = date_create_from_format('Y-m-d H:i:s',$startString);
        $startMonth = date_format($start, 'F');
        $startDay = date_format($start, 'j');
        $startWeekday = date_format($start, 'l');

        $endString = $event['start_date'];
        $end = date_create_from_format('Y-m-d H:i:s',$endString);
        $endMonth = date_format($end, 'F');
        $endDay = date_format($end, 'j');
        $endWeekday = date_format($end, 'l');

        // Insert a monthly header 
        if ($startMonth != $currentMonth) {
            $currentMonth = $startMonth;
            $Content .= "<h3>$startMonth</h3>";
        }

        // Start the event row (we use a complete table for each event for formatting reasons);
        $row = "<div class=\"civi-event-calendar-event $typeLabel\">";
        
        // Add the date block

        $row .= "<div class=\"civi-event-calendar-cell-date\">";
        if ($startDay == $endDay && $startMonth == $endMonth) {
            // single day event
            $row .= "    <div class=\"civi-event-calendar-weekday\">$startWeekday</div>";
            $row .= "    <div class=\"civi-event-calendar-day\">$startDay</div>";
        } else {
            // multi-day event
            $row .="<p>end date is $end</p>";
        }
        $row .= "</div>";

        
        // Add the Register button

        $isRegistration = $event['is_online_registration'];
        $maxParticipants = $event['max_participants'];
        $registeredParticipants = count( \Civi\Api4\Participant::get(FALSE)->addSelect('id')->addWhere('event_id', '=', 101)->execute() );
        $isFull = ($registeredParticipants >= $maxParticipants);

        if( $isRegistration && !$isFull ) {
            $reglink = CRM_Utils_System::url( 'civicrm/event/register', "reset=1&id=$id" );

            $row .= "<div class=\"civi-event-calendar-cell-register\">";
            $row .= "    <a href=\"$reglink\">";
            $row .= "        <div class=\"civi-event-calendar-register\">Register</div>";
            $row .= "    </a>";
            $row .= "</div>";

        } elseif ( $isFull ) {
            $row .= "<div class=\"civi-event-calendar-cell-register\">";
            $row .= "        <div class=\"civi-event-calendar-register full\">Event Full</div>";
            $row .= "</div>";
        }

        // Add the title and summary

        $row .= "<div class=\"civi-event-calendar-title\"><a href=\"$url\">$title</a></div>";
        $row .= "<div class=\"civi-event-calendar-description\">$summary</div>";

        // Close Row

        $row .= "</div>";

        $Content .= $row;
    }

    // Add ical Link

    if ( $atts['showical'] > 0 ) {

        $icalLink = CRM_Utils_System::url( 'civicrm/event/ical' );
        $Content .= "<a href=\"$icalLink\">";
        $Content .= "<span class=\"fa-stack\" aria-hidden=\"true\"><i class=\"crm-i fa-calendar-o fa-stack-2x\"></i><i style=\"top: 15%;\" class=\"crm-i fa-link fa-stack-1x\"></i></span>";
        $Content .= "<span class=\"label\">iCalendar feed for current and future public events</span>";
        $Content .= "</a>";

    }


    // Close calendar object

    $Content .= '</div>';

    return $Content;
}

add_shortcode('civi-event-calendar', 'civi_event_calendar');
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

namespace CiviEventCalendar;

function enqueue() {
		wp_enqueue_style("civi-event-calendar-style", plugins_url("civi-event-calendar.css", __FILE__), array(), "1.0", "all");
}

add_action('init', __NAMESPACE__.'\enqueue' );

function console_log($label, $output, $with_script_tags = true) {
    $js_code = 'console.log("' . $label .'",' . json_encode($output, JSON_HEX_TAG) . ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

function render_calendar($user_atts = [], $content = null, $tag = '') {
    
	// Normalize attribute keys to lowercase
	$user_atts = array_change_key_case( (array) $user_atts, CASE_LOWER );

    //Add default attributes and override with user attributes
    $atts = shortcode_atts(
        array(
            'showheader' => 1,
            'header' => 'Upcoming Events',
            'showical' => 1,
            'limit' => 0,
            'widget' => 0,
        ), $user_atts, $tag
    );

    $styleModifier = $atts['widget'] == 1 ? 'widget' : '';

    // Open calendar object
    $Content = "<div class=\"civi-event-calendar $styleModifier\">";

    // Add header
    if($atts['showheader'] > 0) {
        $header = $atts['header'];
        $headerClass = $atts['widget'] == 1 ? "widget-title" : "";
	    $Content .= "    <h3 class=\"$headerClass\">$header</h3>";
    }

    // Get events starting today or after ordered by start date
    $eventList = \Civi\Api4\Event::get(FALSE)
        ->addSelect('*', 'event_type_id:label', 'registration_link_text')
        ->addOrderBy('start_date', 'ASC')
        ->addOrderBy('end_date', 'ASC')
        ->addWhere('start_date', '>=', date('Y-m-d'))
        ->setLimit($atts['limit'])
        ->execute();
    $currentMonth = "";

    foreach ( $eventList as $event ) {
        $title = $event['title'];
        $summary = $event['summary'];
        $typeLabel = $event['event_type_id:label'];
        $id = $event['id'];
        $url = \CRM_Utils_System::url( 'civicrm/event/info', "reset=1&id=$id" );

        $startString = $event['start_date'];
        $start = date_create_from_format('Y-m-d H:i:s',$startString);
        $startMonth = date_format($start, 'F');
        $startDay = date_format($start, 'j');
        $startWeekday = date_format($start, 'l');

        // Check for an end date then validate if this is a multi-day event.
        $endString = $event['end_date'];
        $multiday = false;
        if($endString != null ) {
            $end = date_create_from_format('Y-m-d H:i:s',$endString);
            $endMonth = date_format($end, 'F');
            $endDay = date_format($end, 'j');
            $endWeekday = date_format($end, 'l');

            $startDate = explode(" ", $startString)[0];
            $endDate = explode(" ", $endString)[0];
            $multiday = $startDate != $endDate;
        }

        // Insert a monthly header 
        if ($startMonth != $currentMonth) {
            $currentMonth = $startMonth;
            $Content .= "<h3>$startMonth</h3>";
        }

        // Start the event row (we use a complete table for each event for formatting reasons);
        $row = "<div class=\"civi-event-calendar-event $typeLabel\">";
        
        // Add the date block

        $dateStyle = "";
        $dayString = $startDay;
        $weekdayString = $startWeekday;

        // If the start day and end day are different, this is a multi-day event.
        if ($multiday) {
            $dateStyle ="multiday";
            $dayString .=  "-".$endDay;
            $weekdayString = date_format($start, 'D')."-".date_format($end, 'D');
        }

        $row .= "<div class=\"civi-event-calendar-cell-date\">";
        $row .= "    <div class=\"civi-event-calendar-weekday\">$weekdayString</div>";
        $row .= "    <div class=\"civi-event-calendar-day $dateStyle\">$dayString</div>";
        $row .= "</div>";

        
        // Add the Register button

        $isRegistration = $event['is_online_registration'];

        if( $isRegistration) {

            // Check if the event is full
            $maxParticipants = $event['max_participants'];
            $registeredParticipants = count( \Civi\Api4\Participant::get(FALSE)->addSelect('id')->addWhere('event_id', '=', 101)->execute() );
            $isFull = ($registeredParticipants >= $maxParticipants);

            // Check to see if this user is already registered for the event
            $participants = \Civi\Api4\Participant::get(FALSE)
                ->addSelect('id')
                ->addWhere('contact_id', '=', 'user_contact_id')
                ->addWhere('event_id', '=', $id)
                ->setLimit(25)
                ->execute();

            $linkOpen = '';
            $linkClose = '';
            $label = 'Register';
            $style = '';

            if (count($participants) > 0) {
                $label = "Registered";
                $style = "registered";
            } elseif ($isFull) {
                $label = "Full";
                $style = "full";
            } else {
                $reglink = \CRM_Utils_System::url( 'civicrm/event/register', "reset=1&id=$id" );
                $linkOpen = "    <a href=\"$reglink\">";
                $linkClose = "    </a>";
            }

            $row .= "<div class=\"civi-event-calendar-cell-register\">";
            $row .= $linkOpen;
            $row .= "        <div class=\"civi-event-calendar-register $style\">$label</div>";
            $row .= $linkClose;
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

        $icalLink = \CRM_Utils_System::url( 'civicrm/event/ical' );
        $Content .= "<a href=\"$icalLink\">";
        $Content .= "<span class=\"fa-stack\" aria-hidden=\"true\"><i class=\"crm-i fa-calendar-o fa-stack-2x\"></i><i style=\"top: 15%;\" class=\"crm-i fa-link fa-stack-1x\"></i></span>";
        $Content .= "<span class=\"label\">iCalendar feed for current and future public events</span>";
        $Content .= "</a>";

    }


    // Close calendar object

    $Content .= '</div>';

    return $Content;
}

add_shortcode('civi-event-calendar', __NAMESPACE__.'\render_calendar');
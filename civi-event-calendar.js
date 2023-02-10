// Render the App component into the DOM if it exists
const civiReactEvents = document.getElementById('civi-events-calendar');
import { render } from '@wordpress/element';

if(civiReactEvents) {
    render(civi_events_calendar_render(), civiReactEvents);
}

function civi_events_calendar_render() {

}
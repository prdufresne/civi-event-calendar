# civi-event-calendar
This plugin will show civiCRM events in WordPress using a shortcode in a page or post.

# Using the Plugin
The plugin is invoked using a WordPress shortcode:

```
[civi-event-calendar]
```

## Plugin attributes

The following attributes can ben used to customize the plugin appearance.

- `showheader` (int) Determines whether the header is shown. Valid values: [0, 1], default: 1
- `header` (string) The header text to be shown. Default: 'Upcoming Events'
- 'showical' (int) Determines whether or not to show the ical link. Valid values: [0, 1], default: 1
- `limit` (int) Sets the maximum number of events to retrieve. Default: 0 (unlimited)

## Styling Events by type

You can apply different styles to events based on event type. The event type label will be added to the event class. If you plan to use this feature, your event types should not have spaces. We recommend you replace spaces with dashes or underscores.

Custom styles can be added to the custom CSS section of your theme. For example:

```css
  .civi-event-calendar-event.Member-Run {
    background-color: #fdd;
  }
  
  .civi-event-calendar-event.Open-Run {
    background-color: #dfd;
  }
  ```
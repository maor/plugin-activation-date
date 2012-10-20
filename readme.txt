=== Plugin Activation Date ===
Contributors: maor
Tags: plugins, plugin info
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Keep track of plugins you activate or deactivate through the dashboard by viewing when each and every one of them was enabled or disabled.

== Description ==

Current features:

* Adds a new column on the plugins dashboard screen that shows when each plugin was activated, only if PAD was installed before
* Control whether you'd like to see a relative date or a "regular" date as set under Settings > General

While by default the date & time settings are being used, you can always change the date format by filtering 'pad_date_time_format'. For instance:

`
<?php

add_filter( 'pad_date_time_format', 'my_pad_date_time_format' );
function my_pad_date_time_format( $date_format ) {
	return 'm-d-Y';
}
`

Here's some more info on [date & time formatting in WordPress](http://codex.wordpress.org/Formatting_Date_and_Time)

Feel free to post your feature requests, issues and pull requests to [Plugin Activation Date on GitHub](https://github.com/maor/plugin-activation-date "PAD on GitHub").

== Installation ==

1. Download and extract PAD in your `wp-content/plugins` directory
1. Activate the plugin through the Plugins menu in WordPress
1. You're all set!

== Screenshots ==

1. The "Last Activated" column in the plugins dashboard screen

== Changelog ==

= 1.1 =
* Code optimization and documentation fixes

= 1.0 =
* Initial release
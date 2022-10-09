=== Maintenance & Coming Soon Redirect Animation ===
Contributors: yasinedr
Author URI: https://profiles.wordpress.org/yasinedr/
Tags: maintenance, animation, redirect, maintenance mode, admin, administration, unavailable, coming soon, landing page, under construction, contact form, subscribe, countdown
Requires at least: 4.6
Tested up to: 6.0.2
Requires PHP: 5.2.4
Stable tag: 1.1.2
Donate link: https://www.paypal.me/yassineidrissi
Text Domain: lottie-maintenance-mode-animation
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Make your website under maintenance in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode .

== Description ==
This super lightweight plugin is intended primarily for developers , designers and site administrators that need to allow clients to preview site before being available to the general public or to temporarily hide it while undergoing major updates.

Any logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings in the plugin. The exact privilege can be set using a filter hook - see FAQs.

The behaviour of this can be enabled or disabled at any time without losing any of settings configured in its settings pane. However, deactivating the plugin is recommended versus having it activated while disabled.

When redirect is enabled it can send 2 different header types. “200 OK” is best used for when the site is under development and “503 Service Temporarily Unavailable” is best for when the site is temporarily taken offline for small amendments. If used for a long period of time, 503 can damage your Google ranking.

A list of IP addresses can be set up to completely bypass maintenance mode. This option is useful when needing to allow a client’s entire office to access the site while in maintenance mode without needing to maintain individual access keys.

Access keys work by creating a key on the user’s computer that will be checked against when maintenance mode is active. When a new key is created, a link to create the access key cookie will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key.
 
**`Animations :`**

Animation on the web is not only fun, but engaging in such a way that it has converted site visitors into customers even before will be available to the general public and it will make them eagerly await its launch.

You can choose your animation from the library ; this animation will be shown in front of your site when it is undergoing maintenance.

**` Notes :`**  
✔ This plugin will override any other maintenance plugin you use.
✔ All settings are auto-updated , you don't need to save anything .


== Installation ==

1. Unpack the download package.
2. Upload the `maintenance-coming-soon-redirect-animation` folder to your plugins directory (usually `/wp-content/plugins/`).
3. Activate the plugin through the `Plugins` menu in WordPress.
4. You can turn on the maintenance mode in one click from Top bar icon.
5. For more customization , go to `Settings` page from the link in the activation notice or through the `Maintenance` Settings panel just below the dashboard icon.


== Frequently Asked Questions ==
= How can I bypass the redirect programatically ? =

There is a filter which allows you to programatically bypass the redirection block:

**`wploti_matches`**

This allows you to run pretty much any test you like, although be aware that the whole redirection thing runs *before* the `$post` global is set up, so WordPress conditionals such as `is_post()` and `is_tax()` are not available. 

This example looks in the `$_SERVER` global to see if any part of the URL contains "hello-world" ( default wordpress post for example )

	function my_wploti_matches( $wploti_matches ) {
		if ( stristr( $_SERVER['REQUEST_URI'], 'hello-world' ) ) 
			$wploti_matches[] = "<!-- hello-world -->";
		return $wploti_matches;
	}
	add_filter( "wploti_matches", "my_wploti_matches" );

You can follow the same process for pages, posts, categories, taxonomies, custom post types ,etc.


= How can I let my logged-in user see the front end ? =

By default, Maintenance & Coming Soon Redirect Animation uses the `manage_options` capability, but that is normally only applied to administrators. As it stands, a user with a lesser permissions level, such as editor, is able to view the admin side of the site, but not the front end. You can change this using this filter :

**`wploti_user_can`**

This filter is used to pass a different WordPress capability to check if the logged-in user has permission to view the site and thus bypass the redirection, such as `edit_posts`. Note that this is run before `$post` is set up, so WordPress conditionals such as `is_post()` and `is_tax()` are not available. However, it's not really meant for programatically determining whether a user should have access, but rather just changing the default capability to be tested, so you don't really need to do anything other than the example below.

	function my_wploti_user_can( $capability ) {
		return "edit_posts";
	}
	add_filter( "wploti_user_can", "my_wploti_user_can" );

== Screenshots ==

1. Welcome screen after installation
2. Activate Maintenance Mode & Header Type
3. Unrestricted IP adresses & Acess keys
4. Default Active animation
5. Selected animation from the animations Library
6. Animations for different purposes : Update, security, coming soon, under construction ..
7. Maintenance message displayed under animation
8. Send an Access key to specific user
9. Access key link email
10. How maintenance page looks like

== Changelog ==


= 1.1.1 =
* First Final release. No Changes Yet.

== Upgrade Notice ==
Now translatable!
=== Glossy ===
Contributors: Columcille
Tags: glossary, dictionary, tooltip, popup
Requires at least: 3.3
Tested up to: 3.6.1
Stable tag: 2.3.5

Glossy allows you to define information text that can be used throughout your site with a simple shortcode.

== Description ==

Glossy provides an easy way to insert predefined text for use throughout your site. If Tippy is installed, definitions can show via a tooltip. Provides an easy way to specify if tooltips should show in a tooltip or inline by default. Also provides a shortcode attribute for changing the inline setting on a per-term basis.

Glossy adds the shortcodes gs and glossy. For example:

[gs NYSE] - this is the quick way.
[glossy term="NYSE"] - a longer form, does the same as [gs NYSE]

You can also specify the inline attribute:

[glossy term="NYSE" inline="false"] - show the definition in a tooltip
[glossy term="NYSE" inline="true"] - show the definition in the body of your post content.
[gs inline="true" nyse] - Same as above.

Terms are defined in the WordPress dashboard and can be used throughout your site.

The tooltip is created by the Tippy plugin, so showing definitions in a tooltip requires Tippy in order to work.

If you want to create a page that shows visitors an index of all your Glossy entries, use the [glossyindex] shortcode. Just insert [glossyindex] into any post or page and it will take care of the rest. By default, it shows a header with an index of first characters. You can turn it off by using [glossyindex header="off"]

== Installation ==

Upload the plugin into your wp-content/plugins directory
Activate the plugin through the dashboard
Make sure you have <a href="http://croberts.me/tippy-for-wordpress/">Tippy</a> installed and activated
Visit the Glossy settings in the Dashboard sidebar and configure as desired

== Changelog ==

= 2.3.5 =
* Fixed an issue setting the width and height

= 2.3.4 =
* Fixed how the options are passed to Tippy.

= 2.3.3 =
* Updated to work with the latest version of Tippy.

= 2.3.2 =
* Fixed some errors with CSV import/export

= 2.3.1 =
* Tweak so processed shortcodes won't be recognized if wrapped in double brackets - ie, [[glossy]] - often used for demonstration purposes.

= 2.3.0 =
* Added a new option to use Tippy's experimental content method. Will allow users to display content that otherwise might not work.

= 2.2.1 =
* Fixed an issue with UTF8 characters in the glossyindex

= 2.2.0 =
* Shortcode once again works when passed through the do_shortcode() function.

= 2.1.1 =
* Fixed glitch when glossy entries contain a $ sign.

= 2.1.0 =
* New import/export option
* New access option - choose which user role has access to manage entries
* Glossy can now receive any attribute available for Tippy
* New attributes useful with inline glossyindex: showTerm, beforeDef, afterDef, beforeTerm, afterTerm
* Improvements to admin loading

= 2.0.0 =
* Fix issue causing extra output when plugin activated
* New checks to ensure Tippy is installed
* New checks for the Glossy db table when plugin activated
* Improved handling of gs, glossy, and glossyindex shortcodes - gs and glossy can be used interchangeably
* Can specify header="on/off" for each Glossy term.
* Global setting for header on/off in the Glossy dashboard settings page.
* New options for glossyindex - these are not yet finished, pushing out version 2 early to address some bugs
* Glossy is now OOP.
* Upcoming: export/import of terms. Easily backup terms, load into a new site, or even load new terms from a csv file.
* Upcoming: additional access control options, allow more of your users access to Glossy terms.

= 1.5.7 =
* Fix issue with plugin crashing install when Tippy is not installed

= 1.5.6 =
* Fix glossyindex

= 1.5.5 =
* Additional shortcode work. [gs] and [glossy] are now processed the same way so both support full attributes and title swap (ie: [glossy term="myterm"]show this title[/glossy])

= 1.5.4 = 
* Fix issue with gs shortcode. Note this reverts gs to its previous behavior - attributes cannot be passed to it at this time.

= 1.5.3 =
* Additional tweaks to inline terms
* Fix to restore tooltip previews in the admin Manage Glossy area

= 1.5.2 =
* Added the inline attribute to glossyindex

= 1.5.1 =
* Tweak to fix inline settings for [glossyindex] and admin display

= 1.5.0 =
* Added a new option to turn on wpautop() formatting for glossy content.

= 1.4.0 =
* Improved the gs shortcode so it can be used with optional term and inline attributes.

= 1.3.0 =
* Added new Inline option under Manage entries
* Added inline attribute for the shortcode; inline="true" or inline="false" specifies whether to show the definition inline or in a tooltip.

= 1.2.8 =
* Glossy tables are now created with utf8 character set (thanks to DonySuXX for the suggestion)

= 1.2.7 =
* Fixed a bug introduced with 1.2.6
* Fixed a compatibility issue with the latest version of Tippy

= 1.2.6 = 
* $wpdb->prepare tweaks

= 1.2.5 =
* Fixes the issue where extra content was being sent to WordPress.

= 1.2.4 =
* Fixed bug when the same entry is used more than once in a post
* Tweaks for custom title text; it replaces the Tippy anchor, not the Tippy title.

= 1.2.3 =
* Fixed bug when trying to use an entry name that does not exist
* Fixed bug when using gs with an entry name that has two or more words
* Fixed bug when gs was not used as a self-closing tag (ie: [gs myName /])
* Added the ability to use custom title text: [gs myName]Display this text[/gs]

= 1.2.2 =
* Tweak to entry search method

= 1.2.1 =
* Fixed bug on dashboard page when no entries are in the system
* Fixed index page bug when entry title contains an apostraphe

= 1.2.0 =
* Improved management of entries

= 1.1.3 =
* Should fix sorting issue with entries on a glossary page. Please let me know if you see additional sorting problems.

= 1.1.2 =
* Fixed an issue when showing an index of entries that don't have a title specified.

= 1.1.0 =
* Added a new glossyindex shortcode to allow the creation of an index page
* Added the WYSIWYG editor for adding Glossy content (Note that WP 3.3 is required because of this addition)
* Added the glossy shortcode as an alternative to the gs shortcode
* Glossy should now handle any shortcodes included in the entry

= 1.0.1 =
* Fixed tagging glitch

= 1.0.0 =
* Initial release

== Screenshots ==

1. Visiting the Glossy entries list for the first time.
2. Adding a new Glossy entry
3. Data filled in for a Glossy entry
4. Visiting the Glossy entries list with an entry
5. Setting up a post using the Glossy shortcode [gs NYSE]
6. Viewing the tooltip generated by Glossy + Tippy

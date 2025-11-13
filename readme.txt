=== VK Dynamic If Block ===
Contributors: vektor-inc,kurudrive,doshimaf,toro_unit
Tags: dynamic block, if, Conditional branch, Conditional Display, Custom Field, Full Site Editing
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

VK Dynamic If Block displays its Inner Blocks based on specified conditions, such as whether the current page is the front page or a single post, the post type, or the value of a Custom Field.

== Description ==

VK Dynamic If Block is a custom WordPress block, primarily designed for FSE, that allows users to display Inner Block based on specified conditions. With this block, you can show or hide Inner Block depending on various conditions, such as whether the current page is the front page or a single post, the post type, or the value of a Custom Field.

== Installation ==

1. Upload the plugin files to the '/wp-content/plugins/vk-dynamic-if-block' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the block editor to add a "Dynamic If" block to your post or page.

== Frequently Asked Questions ==

= Can I use multiple conditions? =

You cannot specify too many conditions.
However, by nesting Dynamic If Blocks, various conditional branching can be handled.

== Screenshots ==

1. Block settings in the editor sidebar.
2. Dynamic If block in the site editor.
3. Dynamic If block in the site editor.

== Changelog ==

[ Bug fix ] Fix console warning message.

= 1.5.0 =
[ Add Function ] Added specific page selection functionality for Page Type conditions.

= 1.4.2 =
[ Bug fix ] Fix an issue where else block content is cut off in the middle for complex HTML structures.

= 1.4.1 =
[ Bug fix ] Fix an issue where the date input field is not displayed when "Direct input in this block" is initially selected for display period conditions.

= 1.4.0 =
[ Add Function ] Add Else block functionality to display alternative content when conditions are not met.

= 1.3.2 =
[ Bug fix ] Fix an issue where the condition label is not translated.
[ Bug fix ] Fix Language condition error

= 1.3.1 =
[ Bug fix ] Fix translation not working for some labels.

= 1.3.0 =
[ Add Function ] Added condition to display content based on specific taxonomy and terms.
[ Add Function ] Added condition to display only to mobile devices.
[ Other ] Fix translation

= 1.2.0 =
[ Other ] Prevent automatic migration of old block structure on frontend and migrate to new structure when saving in editor.

= 1.1.0 =
[ Add Function ] Add page hierarchy conditions (parent/child page) to page type and post type conditions for pages.
[ Bug fix ] Fix an issue where duplicating the VK Dynamic If Block caused all instances to share the same condition.
[ Bug fix ] Fix Label color

= 1.0.0 =
[ Specification Change ] Changed UI to stacked condition format.

= 0.9.4 =
[ Other ] Update alert message

= 0.9.3 =
[ Specification change ] Change version 1 download url to GitHub from .org
[ Bug fix ] Fix Label color

= 0.9.2 =
[ Specification change ] Added update notification for version 1.

= 0.9.1 =
[ Specification change ] Add the condition !is_year() && !is_month() && !is_date() for Post Type Archives.

= 0.8.6 =
[ Specification change ][ Author Archive ] Changed to target only users with the role of Contributor or higher who have at least one published article.

= 0.8.5 =
[ Add Function ][ Author Archive ] Allow specifying the author.
[ Bug Fix ] Fix readme typo

= 0.8.4 =
[ Add Function ] Add exclusion indicator to condition labels.

= 0.8.3 =
[ Specification change ] Fixed the zoom-out toggle not always displaying in the editor toolbar (updated blocks.json API version from 2 to 3).

= 0.8.1 =
[ Bug Fix ] Fixed an issue where en_US could not be specified in the language selection options.

= 0.8.0 =
[ Add Function ] Add a conditional branching function based on language.

= 0.7.0 =
[ Add Function ] Added condition to display only to login user.
[ Specification Change ] Fix WordPress 6.3 transforms settings.

= 0.6.3 =
[ Fix ] Fixed a bug related to the period setting when referencing a custom field.

= 0.6.2 =
[ Fix ] Fix the bug in conditional branching based on user roles.

= 0.6.1 =
[ Fix ] Correct the translation

= 0.6.0 =
[ Add Function ] Added user roles condition
[ Add Function ] Added date condition
[ Fix ] Added UserRole label to block.

= 0.5.0 =
[ Add Function ]Added transforms settings to wrap and unwrap.

= 0.4.3 =
* [ Bug fix ] Fixed bug in conditional branching based on custom field values

= 0.4.1 =
* Update descriptions

= 0.4.0 =
* Add custom field conditions

= 0.3.1 =
* Fix translate

= 0.3.0 =
* Add exclusion setting

= 0.2.7 =
* Set text domain for translations

= 0.2.6 =
* Set text domain for translations

= 0.2.4 =
* Fix readme

= 0.2.3 =
* Add fallback for vendor files failed to deliver or load.

= 0.2.1 =
* Add default paragraph block

= 0.2.0 =
* Add conditions

= 0.1.0 =
* Initial release

== Upgrade Notice ==

= 0.1.0 =
* Initial release

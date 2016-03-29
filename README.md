# top-shared-posts-on-facebook-widget
Top shared posts on Facebook fetches total share counts for your blog posts on Facebook using Facebook Graph API and sorts the most shared ones in an ascending order together with their total share counts.

=== Top Shared Posts on Facebook Widget ===
Contributors: elhardoum
Tags: Facebook, count, social, popular, widget, posts, share, social share, top, top-posts, popular-posts, facebook-shares, count-shares, social-widgets, social-plugins
Requires at least: 3.0.1
Tested up to: 4.4.2
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A fancy widget showing your top shared posts on Facebook with their share counts and like/share buttons

== Description ==

Top shared posts on Facebook fetches total share counts for your blog posts on Facebook using Facebook Graph API and sorts the most shared ones in an ascending order together with their total share counts.

As of ver 2.0, the plugin allows you to show Facebook like and share buttons beside the other widget elements, and that way you can earn more engagement and social shares.

There is also a shortcode you can use to get the counts for each post.

Another handy tool is filtering posts by category, for example when you want to show the top shared posts from a specific category..

<h4>Features:</h4>

* Display the top shared posts you have
* Switch between simple list style with no thumbnail, or fancy one with post thumbnail and count on top of it
* Include Facebook like and share buttons to get more Facebook shares
* Filter posts by categories.
* Customizable settings.

<h4>How does it work:</h4>

Once installed, the plugin will create a Cron Job which runs once every 24 hours to update the data (counts). For the first installation, you will be prompted to manually process the first data insert which will get the counts from through Facebook Graph API.

You can update the cron running time (interval) and set it to a custom value rather than the default 24 hours.

Once the data are there, the posts are getting sorted based on their share counts and you can then see them through the widget content.

<strong>The widget:</strong>

While adding the widget, you can choose the maximum posts to show, the list style, and format the output. You can also filter posts by categories, add counts on top of thumbnails, include Facebook like and/or share buttons beside the other content.

You can add as many widgets as you want, and there is nothing to worry about.

<strong>Admin settings:</strong>

Through the admin settings panel you can:

* Set the default thumbnail while some of your posts might not have a thumbnail associated
* Update the cron interval: update data (counts) every [X = customizable] hour(s)
* 2 more advanced settings, how many posts per batch while updating data, and the delay between these requests so our server can rest AND we won't get banned (403) by the FB Graph API servers.

<strong>Try it out</strong>

Try it out and if there is anything that bothers you about it or you need support, please start a new thread here in the support section, or post us some feedbacks and reviews.. Otherwise if you want to let us know about anything, you can drop us few lines through our contact page which you find in <a href="http://samelh.com">our website</a>.

Thank You :)

== Installation ==

* Install, activate, add widget:
1. Upload the plugin to your plugins directory, or use the WordPress installer.
2. Activate the plugin through the \'Plugins\' menu in WordPress.
3. Add the plugin widget, update settings and, Enjoy!

== Screenshots ==

1. The widget
2. Admin screen
3. Widget in action, little preview

== Changelog ==

= 2.0 =
* Rewritten the plugin, improved everything, from performance to output and design..

= 0.1.3 =
* Fixed a sorting bug in share count, thanks to <a href="https://profiles.wordpress.org/deprims/">deprims</a> who has reported it !!

= 0.1.2 =
* Fixed a social share count bug

= 0.1 =
* Initial release.

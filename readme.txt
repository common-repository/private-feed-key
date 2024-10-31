=== Private Feed Key ===
Contributors: mikegrant
Donate link: http://www.poeticcoding.co.uk/plugins/private-feed-key/
Tags: access, admin, authentication, feed, feeds, feedkey, key, rss, restrict, registration, members, url
Requires at least: 3.6
Tested up to: 3.6
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Private Feed Key adds a 32bit (or 40bit) key for each of your users, creating a unique feed url for every registered user on the site.

== Description ==
Private Feed Key adds a 32bit (or 40bit) key for each of your users, creating a unique feed url for every registered user on the site. This allows you to restrict access to your feeds, to registered users only. The plugin will also work with plugins that filter access to posts to certain user levels, only allowing the user to see that content .

== Installation ==

1. Install Private Feed Key either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Activate the plugin.
3. In your *WordPress Administration Area*, go to the *Plugins* page and click *Activate* for *Feed Key*

Once you have _Private Feed Key_ installed and activated your feeds will only be accessable when using a valid _Feed Key_.

== Frequently asked questions ==

*What are Feed Keys?*

_Feed Keys_, are unique 32bit (or 40bit) keys that are added to your blog's URL in order to give every registered user a custom feed URL. 
A Feed Key looks something like this: *`206914af21373cc4792a057b067d2448`*

This is then appended to the feed url for your user in their User Profile, like the examples below, either without permalinks...

*`http://example.com/?feed=rss2&feedkey=206914af21373cc4792a057b067d2448`*

...or with permalinks

*`http://example.com/feed/?feedkey=206914af21373cc4792a057b067d2448`*

== Changelog ==

#### 0.1
Initial Release
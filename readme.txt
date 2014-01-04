=== Bookmark Me ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: http://www.semiologic.com/partners/
Tags: bookmark-me, social-bookmarking, social-media, google+, delicious, digg, facebook, pinterest, reddit, stumbleupon, twitter, bookmarking, widget, semiologic
Requires at least: 2.8
Tested up to: 3.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds buttons that let your visitors share your content on social media sites.


== Description ==

The Bookmark Me plugin will add buttons that let your visitors share your content on [social media sites](http://www.semiologic.com/resources/blogging/help-with-social-media-sites/) such as Buzz Up!, Delicious, Digg, Facebook, Mixx, Reddit and StumbleUpon.

Share by Email and Print buttons are also added when the plugin is called within the loop.

Hovering the initial set of buttons will reveal many more services. Only major services are included in the complete list of services. (Two exceptions were made for specialized sites.)

These services are added through the use of widgets. This makes it especially useful for [Semiologic theme](http://www.semiologic.com/software/sem-theme/) users.

Users of other themes can add the following call within the loop instead:

    <php the_bookmark_links(); ?>

The call accepts an optional argument, which sets the widget's title.

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 5.3.1 =

- WP 3.8 compat

= 5.3 =

- WP 3.6 compat
- PHP 5.4 compat

= 5.2.3 =

- Removed the "What's This?' link

= 5.2.2 =

- Ensure bookmark popup is on top of other elements

= 5.2.1 =

- Fixed broken Google+ url being generated

= 5.2 =

- Added Pinterest as top level service

= 5.1.1 =

- Updated google plus bookmark link

= 5.1 =

- WP 3.5 compat
- Changed top level services to Facebook, Twitter and Google+
- Updated secondary services to current offering.  Removed out-dated/not in service services

= 5.0.2 =

- Fix occasional invalid HTML on manual calls

= 5.0.1 =

- Apply filters to permalinks
- Fix cache flushing

= 5.0 =

- Complete rewrite
- WP_Widget class
- Drop all options except title (nofollow is always enabled)
- Smaller, better list of services
- Add Email and Print services
- Smaller number of image depends
- Use jQuery, insert script in footer
- Localization
- Code enhancements and optimizations

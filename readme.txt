=== ABT Relative Urls ===
Contributors: atlanticbt, zaus
Donate link: http://atlanticbt.com
Tags: development, relative urls, absolute urls, urls, alters
 content, alters post-meta, scrub, domain, domain change, link, links, update links, move wordpress
Requires at least: 2.8
Tested up to: 3.3.3
Stable tag: trunk
License: GPLv2 or later

Replaces default absolute, self-referencing urls in post content with the relative path "/" instead.  Works on images and post-meta.

== Description ==

By default, WP inserts absolute urls into post content; this includes the protocol and domain, which is based on the `home` Admin setting.  This plugin replace all self-referencing (domain) links with relative paths "/" instead.  Works when inserting images into posts, and on the actual `save_post` action it scrubs the *content*, *excerpt*, and *post_meta* fields.

Especially helpful when developing on a DEV site with the intention of transfering to a LIVE domain.  Please note that you should use this plugin _before_ you start adding content, or you'll have to resave everything later.

Includes code based on [Relative Image URLs][] plugin, which strips domain when inserting images from the Media Library.  A similar idea to the following, but acts as a preventative measure (while you're working), works automatically, and handles meta fields:

* [Go Live Update URLS][]
* [Absolute to Relative URLs][]
* [Domain Name and Path Fixer][]

[Absolute to Relative URLs]: http://wordpress.org/extend/plugins/absolute-to-relative-urls/ "Programmatic plugin for removing URLs"
[Relative Image URLs]: http://wordpress.org/extend/plugins/relative-image-urls/ "Alternate Plugin, not as much functionality"
[Go Live Update URLS]: http://wordpress.org/extend/plugins/go-live-update-urls/ "Change URLs when pushing LIVE"
[Domain Name and Path Fixer]: http://wordpress.org/extend/plugins/domain-name-path-fixer-plugin/ "Change URLs path when pushing LIVE"

== Installation ==

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Activate plugin
3. Create content - view HTML source to ensure that domains have been stripped from content.

== Frequently Asked Questions ==

= How does it work? =

First, it determines the absolute URL from the admin settings (`home` key, via `home_url()`), as this is what Wordpress uses when hardcoding links.

On `save_post` action, it examines both the `post_content` and `post_excerpt` submissions and strips the current domain/protocol from:

1. `href` attributes
2. `src` attributes
3. all other instances of the domain

It then retrieves all of the `postmeta`, scans through the array, and removes the domain from any values.  Since it's a direct dump, it `maybe_unserialize`s each value before recursively scrubbing the content.

= Can I use the absolute URL? =

Yes with protected shortcode:

    [abt_absolute_url trailing="/suffix/"]

where the attribute `trailing` is optional, and would append whatever is given to the absolute url.  Really it's just provided as a "just-in-case", as you could write `[abt_absolute_url]/suffix/` just the same.

= Can I change what's replaced? =

By default, the following:

    
    array( 
      'src="' . $absolute_path_prefix
      , 'href="' . $absolute_path_prefix
      , esc_attr( $absolute_path_prefix )
    )

are replaced with

    array( 
      'src="' . $relative_path_prefix
      , 'href="' . $relative_path_prefix
      , esc_attr( $relative_path_prefix )
     )

Two hooks are provided to alter these defaults:

* `abt_relative_urls_get_search_for`: the first "search" array
* `abt_relative_urls_get_replace_with`: the second "replace" array

The reason it looks for the `src`, then `href`, then the actual attribute has to do with compatibility with the included relative image url plugin.

= Can I exclude some meta fields? =

Using the hook `abt_relative_urls_exclude_meta`, you can remove fields from the meta array before they are processed.  Anything not in the array won't get re-saved later, so it's effectively ignored.

Ex)

    add_filter('abt_relative_urls_exclude_meta', 'excludepress_meta');
    function excludepress_meta($meta, $post_ID) {
    	unset( $meta['press'] );
    	return $meta;
    }//--	fn	excludepress_meta

You could also perform any custom scrubbing+saving within this hook as well, just make sure to remove it from the list so it's not automatically processed afterwards.

= Developers =

Check out our other developer-centric plugin, [WP-Dev-Library].  Suggestions/improvements welcome!

[WP-Dev-Library]: http://wordpress.org/extend/plugins/wp-dev-library/ "WP Developer Library - the plugin with the mostest"


== Screenshots ==

1. Normal text entry - notice from side-by-side with HTML source that absolute links are present
2. Resulting output after saving (and scrubbing) - notice that in the Firebug HTML output, links are relative except where shortcode used.

== Changelog ==

= 0.3.2 =

* fixed incorrect logic whereby postmeta only updated if it was an array; should update regardless, but now only updates if value was changed

= 0.3.1 =

* Added filter `abt_relative_urls_exclude_meta` to allow exclusion/special meta field processing.

= 0.3 =

* cleanup
* WP submission
* correct use of `home_url()` instead of "manual" domain+protocol
* shortcode

= 0.2 =

* refactoring
* post-meta fields

= 0.1 =

* proof-of-concept

== Upgrade Notice ==

None


== About AtlanticBT ==

From [About AtlanticBT][].

= Our Story =

> Atlantic Business Technologies, Inc. has been in existence since the relative infancy of the Internet.  Since March of 1998, Atlantic BT has become one of the largest and fastest growing web development companies in Raleigh, NC.  While our original business goal was to develop new software and systems for the medical and pharmaceutical industries, we quickly expanded into a business that provides fully customized, functional websites and Internet solutions to small, medium and larger national businesses.

> Our President, Jon Jordan, founded Atlantic BT on the philosophy that Internet solutions should be customized individually for each client's specialized needs.  Today we have expanded his vision to provide unique custom solutions to a growing account base of more than 600 clients.  We offer end-to-end solutions for all clients including professional business website design, e-commerce and programming solutions, business grade web hosting, web strategy and all facets of internet marketing.

= Who We Are =

> The Atlantic BT Team is made up of friendly and knowledgeable professionals in every department who, with their own unique talents, share a wealth of industry experience.  Because of this, Atlantic BT always has a specialist on hand to address each client's individual needs.  Due to the fact that the industry is constantly changing, all of our specialists continuously study the latest trends in all aspects of internet technology.   Thanks to our ongoing research in the web designing, programming, hosting and internet marketing fields, we are able to offer our clients the most recent and relevant ideas, suggestions and services.

[About AtlanticBT]: http://www.atlanticbt.com/company "The Company Atlantic BT"


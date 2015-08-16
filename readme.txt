=== SM Cleanup ===
Contributors: Simon Jan
Tags: compress minify css external style attributes minify optimize clean reuse code class SEO html css
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=FVK9AZJVKK69C
Requires at least: 3.4
Tested up to: 4.2.4
Stable tag: 1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple way optimize your web, clean your site from code, don't need knowledge on html and css, convert style attributes (font-size, font-family, colors, styles etc..) to class attributes, use external style instead of inline style, reuse your style, this  improves the load time of your page. Ex: inline style style="color:#ff0000" now is: external css file: .color-f00{color:#f00}, ...


== Description ==

= Can SM Cleanup help You ? =

* Do you often use custom your style by toolbar of wp_editor ?
* Are you working with plugin WP Edit or Ultimate TinyMCE ?
* INLINE VS INTERNAL VS EXTERNAL STYLE ? Is External style the best option for you ?

->If you want compress styles attribute and use as external style, this is a simple tool for you! ;)


= Major features in SM Cleanup include: =
* Can use SM Cleanup for edit post or add new post. It's only change your post when you submit at button 'Save compress code to my post' (except you checked at 'Automatic update compress to post' on setting options)
* Automatically checks all your content and convert attributes style to class attributes in style sheet file. The CSS file is downloaded and cached on the user's hard drive. This improves the load time of the page; Higher page ranking for SEO.
* Check exist class, if exist don't add more; remove blank tag but still keep margin if you want (you can config at setting page); Remove some nested span tag not necessary; use shorthand hex color. So you can save more.
* Support convert some attributes style in wp_editor toolbar: ex: colors, text-align, padding-left, text-decoration, text-transform, margin-top, fonts...

== Installation ==
1. Upload SM Cleanup plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Goto SM CLeanup and config your Options, then go to edit/addnew and ---- and ------ and -------- You're done! -> TRY use SM Cleanup now, thanks :))

== Screenshots ==
1. Detail how to SM Cleanup compress your code
2. List option
3. Before use SM Cleanup compress (origin)
4. After use SM Cleanup compress
5. New post reuse these style

== Changelog ==
= 1.1 =
* Update unique prefix class
* Update remove class is not necessary, ex:&lt;p class="text-right color-f00 text-left"> => now: &lt;p class="color-f00 text-left">
* Added table compress size report
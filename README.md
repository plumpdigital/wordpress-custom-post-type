Wordpress Custom Post Helper
=====================

WordPress custom post type and meta box helper classes.

Include custom-post-type.php within your theme's functions.php or your plugin.

Instantiating CustomPostType will add a custom post type. The first parameter specifies the name for the custom post type, the second is arguments as defined by the [register_post_type function](http://codex.wordpress.org/Function_Reference/register_post_type).

	$project = new CustomPostType('Project', array(
		'has_archive' => 'work',
		'hierarchical' => 'true',
		'rewrite' => array('slug' => 'work', 'with_front' => false),
		'supports' => array('title', 'editor', 'thumbnail', 'page-attributes')
	));
	
The post type registered with WordPress is the lowercase form of the descriptive name with spaces replaced by -. For instance, 'Movie Review' will become 'movie-review' so the single template would be single-movie-review.php
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

Metaboxes can be added to custom post types by using the MetaBox class:

	$detailsMetaBox = new MetaBox('Project Details');
	$project->addMetaBoxObject($detailsMetaBox);
	
Metaboxes can have fields contained within them:

	$detailsMetaBox->addFields(array(
		'Link'				=> 'text',
		'Quote'				=> 'textarea',
		'Include Related'	=> 'checkbox'
	));
	
Supported field types are currently 'text', 'textarea' and 'checkbox'.

Post metadata is saved as the metabox name combined with the field name, lowercase with spaces replaced with _. For instance, the 'Include Related' field above will become 'project_details_include_related'.

Checkbox values store a 1 as the metadata value if checked and will delete the metadata entry if not set.
	
Alternatively, the MetaBox class can instantiated with the fields as the 2nd parameter:

	$detailsMetaBox = new MetaBox('Project Details', array(
		'Link'				=> 'text',
		'Quote'				=> 'textarea'
	));
	$project->addMetaBoxObject($detailsMetaBox);
	
Metaboxes can be split into columns of fields (left / right) by passing a 2nd parameter to addFields for the column index (starting at 0):

	$detailsMetaBox->addFields(array(
		'Link'				=> 'text',
		'Quote'				=> 'textarea',
		'Include Related'	=> 'checkbox'
	), 0);
	
	$detailsMetaBox->addFields(array(
		'Link2'				=> 'text',
		'Quote2'			=> 'textarea'
	), 1);
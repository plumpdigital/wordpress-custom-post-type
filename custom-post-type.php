<?php

class CustomPostType {
	
	private $postTypeName;
	private $postTypeArgs;
	
	private $metaActionsRegistered = false;
	
	private $taxonomies = array();
	private $metaBoxes = array();
	
	/**
	 * Constructor
	 * 
	 * @param $name Name of the post in human-readable format e.g. Book
	 * @param $args Arguments for the register_post_type method. Merged with defaults
	 * @param $labels Labels for the post type. Merged with auto-generated labels based on post name
	 */
	public function CustomPostType($name, $args = array(), $labels = array()) {
		
		$this->postTypeName = self::sanitize($name);
	
		if (!post_type_exists($this->postTypeName)) {
			
			add_action('init', array($this, 'register'));
			
			$plural = $name . 's'; //TODO must be a better way
			
			//merge labels with defaults
			$labels = array_merge(array(
				'name' 					=> _x($plural, 'post type general name'),
				'singular_name' 		=> _x($name, 'post type singular name'),
				'add_new' 				=> _x('Add new', strtolower($name)),
				'add_new_item' 			=> __('Add New ' . $name),
				'edit_item' 			=> __('Edit ' . $name),
				'new_item' 				=> __('New ' . $name),
				'all_items' 			=> __('All ' . $plural),
				'view_item' 			=> __('View ' . $name),
				'search_items' 			=> __('Search ' . $plural),
				'not_found' 			=> __('No ' . strtolower( $plural ) . ' found'),
				'not_found_in_trash' 	=> __('No ' . strtolower( $plural ) . ' found in Trash'), 
				'parent_item_colon' 	=> '',
				'menu_name' 			=> $plural
			), $labels);
			
			//merge arguments with defaults
			$this->postTypeArgs = array_merge(array(
				'label' 			=> $plural,
				'labels' 			=> $labels,
				'public' 			=> true,
				'show_ui' 			=> true,
				'supports' 			=> array('title', 'editor'),
				'show_in_nav_menus' => true,
				'_builtin' 			=> false
			), $args);
			
		}
	}

	/**
	 * Adds a taxonomy for the custom post type
	 * 
	 * @param $name Taxonomy name in human readable format e.g. Author
	 * @param $args Arguments for the register_taxonomy method. Merged with defaults
	 * @param $labels Labels for the taxonomy. Merged with auto-generated labels based on post name
	 */
	public function addTaxonomy($name, $args = array(), $labels = array()) {
		
		if (!empty($name)) {
			
			$taxonomyName = self::sanitize($name);
			$plural = $name . 's'; //TODO must be a better way
			
			//merge labels with defaults
			$labels = array_merge(array(
				'name' 				=> _x($plural, 'taxonomy general name'),
				'singular_name' 	=> _x($name, 'taxonomy singular name'),
			    'search_items' 		=> __('Search ' . $plural),
			    'all_items' 		=> __('All ' . $plural),
			    'parent_item' 		=> __('Parent ' . $name),
			    'parent_item_colon' => __('Parent ' . $name . ':'),
			    'edit_item' 		=> __('Edit ' . $name), 
			    'update_item' 		=> __('Update ' . $name),
			    'add_new_item' 		=> __('Add New ' . $name),
			    'new_item_name' 	=> __('New ' . $name . ' Name'),
			    'menu_name' 		=> __($name),
			), $labels);
			
			//merge arguments with defaults
			$args = array_merge(array(
				'label'				=> $plural,
				'labels'			=> $labels,
				'public' 			=> true,
				'show_ui' 			=> true,
				'show_in_nav_menus' => true,
				'_builtin' 			=> false,
			), $args);
			
			$this->taxonomies[] = array('name' => $taxonomyName, 'args' => $args);
			
		}
		
	}

	/**
	 * Adds a metabox to the custom post edit page
	 * 
	 * @param $title Title in human-readable format, e.g. Publisher Details
	 * @param $fields Array of fields to add to the metabox
	 */
	public function addMetaBox($title, $fields) {
		$this->addMetaboxObject(new MetaBox($title, $fields));
	}

	public function addMetaboxObject($metaBox) {
		$this->metaBoxes[$metaBox->id] = $metaBox;
		
		if (!$this->metaActionsRegistered) {
			add_action('add_meta_boxes', array($this, 'registerMetaBoxes'));
			add_action('save_post', array($this, 'saveMeta'));
			$this->metaActionsRegistered = true;
		}
	}

	public function register() {
		
		//register the taxonomies first then add to the post type so that
		//taxonomy rewrite rules take presedence
		
		if (count($this->taxonomies) > 0) {
			foreach ($this->taxonomies as $taxonomy) {
				register_taxonomy($taxonomy['name'], null, $taxonomy['args']);
			}
		}

		register_post_type($this->postTypeName, $this->postTypeArgs);
		
		if (count($this->taxonomies) > 0) {
			foreach ($this->taxonomies as $taxonomy) {
				register_taxonomy_for_object_type($taxonomy['name'], $this->postTypeName);
			}
		}
		
	}
	
	public function registerMetaBoxes() {
		
		foreach ($this->metaBoxes as $metabox) {
			add_meta_box($metabox->id, $metabox->title, array($this, 'metaboxContent'), $this->postTypeName, $metabox->context);
		}
		
	}
	
	public function metaboxContent($post, $args) {
		
		$metaBoxId = $args['id'];
		$metaBox = $this->metaBoxes[$metaBoxId];
		
		wp_nonce_field(plugin_basename( __FILE__ ), 'custom_post_type');

		$metaBox->output(get_post_custom($post->ID));
	}
	
	public function saveMeta($postID) {
		
		//the usual checks
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (!wp_verify_nonce($_POST['custom_post_type'], plugin_basename(__FILE__))) return;
		
		if (isset($_POST) && (get_post_type($postID) == $this->postTypeName)) {
			
			//loop through each metabox, saving its fields
			foreach ($this->metaBoxes as $metaBoxId => $metaBox) {
				foreach ($metaBox->getFields() as $field => $type) {
					$name = self::fieldName($metaBoxId, $field);
					if (isset($_POST['custom_meta'][$name])) {
						update_post_meta($postID, $name, $_POST['custom_meta'][$name]);
					} else {
						delete_post_meta($postID, $name);
					}
				}
			}
			
		}
		
	}
	
	private static function fieldName($metaBoxId, $field) {
		return str_replace('-', '_', $metaBoxId) . '_' . self::sanitize($field, '_');	
	}
	
	private static function sanitize($name, $replace = '-') {
		return str_replace(' ', $replace, strtolower($name));
	}
	
}

class MetaBox {
	
	public $id;
	public $title;
	public $context = 'advanced';
	
	private $fields = array();
	
	private $cols = array();
	
	public function __construct($title, $fields = array()) {
		$this->id = self::sanitize($title);
		$this->title = $title;
		$this->addFields($fields);
	}
	
	public function addFields($fields, $col = 0) {
		if (is_array($this->cols[$col])) {
			$this->cols[$col] += $fields;
		} else {
			$this->cols[$col] = $fields;
		}
		$this->fields += $fields;
	}
	
	public function getFields() {
		return $this->fields;
	}

	public function output($values) {
			
		echo '<div style="overflow:hidden">';
		foreach ($this->cols as $fields) {
			echo '<div style="width: 50%; float: left;">';
			foreach ($fields as $field => $type) {
				echo '<p>';
				$name = self::fieldName($this->id, $field);
				$value = array_key_exists($name, $values) ? $values[$name][0] : null;
				$this->outputField($type, $name, $field, $value);
				echo '</p>';
			}
			echo '</div>';
		}
		echo '</div>';
		
	}

	public function outputField($type, $name, $label, $value = null) {
		$this->outputLabel($label, $name);
		switch ($type) {
			case 'text':
				echo "<input type=\"text\" id=\"$name\" name=\"custom_meta[$name]\" value=\"$value\" size=\"50\" />";
				break;
			case 'textarea':
				echo "<textarea id=\"$name\" name=\"custom_meta[$name]\" cols=\"50\" rows=\"5\">$value</textarea>"; 
				break;
			case 'checkbox':
				echo "<input type=\"checkbox\" id=\"$name\" name=\"custom_meta[$name]\" value=\"1\"" . (($value == 1) ? ' checked="checked"' : '') . ' />'; 
				break;
		}
	}
	
	public function outputLabel($label, $name) {
		echo "<label for=\"$name\" style=\"float: left; width: 10em;\">$label</label>";
	}
	
	public function setContext($context) {
		$this->context = $context;
	}
	
	private static function sanitize($name, $replace = '-') {
		return str_replace(' ', $replace, strtolower($name));
	}

	private static function fieldName($metaBoxId, $field) {
		return str_replace('-', '_', $metaBoxId) . '_' . self::sanitize($field, '_');	
	}
	
}

?>
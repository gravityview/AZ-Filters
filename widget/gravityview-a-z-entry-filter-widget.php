<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A-Z Entry Filter Widget Extension
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_A_Z_Entry_Filter extends GravityView_Widget {

	private $search_filters = array();

	function __construct() {
		$postID = isset($_GET['post']) ? intval($_GET['post']) : NULL;
		$formid = gravityview_get_form_id( $postID );

		$default_values = array( 'header' => 1, 'footer' => 0 );

		$settings = array(
			'show_all_letters' => array(
				'type' => 'checkbox',
				'label' => __( 'Show all letters even if entries are empty.', 'gravity-view-az-entry-filter' ),
				'default' => true
			),
			'filter_field' => array(
				'type' => 'select',
				'choices' => $this->get_filter_fields( $formid ),
				'label' => __( 'Which field do you wish to filter?', 'gravity-view-az-entry-filter' ),
				'default' => ''
			),
			'localization' => array(
				'type' => 'select',
				'choices' => $this->load_localization(),
				'label' => __( 'Localization', 'gravity-view-az-entry-filter' ),
				'default' => WPLANG
			),
			'uppercase' => array(
				'type' => 'checkbox',
				'label' => __( 'Uppercase A-Z', 'gravity-view-az-entry-filter' ),
				'default' => true
			),

		);

		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );

		parent::__construct( __( 'A-Z Entry Filter', 'gravity-view-az-entry-filter' ), 'page_letters', $default_values, $settings );
	}

	// This loads the languages we can display the alphabets in.
	function load_localization() {
		$local = apply_filters( 'gravityview_az_entry_filter_localization', array(
			'' => __( 'English', 'gravity-view-az-entry-filter' ),
			'fr_FR' => __( 'French', 'gravity-view-az-entry-filter' ),
			'it_IT' => __( 'Italian', 'gravity-view-az-entry-filter' ),
		) );
		return $local;
	}

	function get_filter_fields( $formid ) {
		// Get fields with sub-inputs and no parent
		$fields = gravityview_get_form_fields( $formid, true, false );

		$default_output = array(
			'' => __( 'Default', 'gravity-view-az-entry-filter' ),
			//'date_created' => __( 'Date Created', 'gravity-view-az-entry-filter' )
		);

		$output = array();

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array( 'list', 'textarea' ) );

			foreach( $fields as $id => $field ) {
				if( in_array( $field['type'], $blacklist_field_types ) ) { continue; }

				$output[$id] = esc_attr( $field['label'] );
			}

			$output = array_merge($default_output, $output);

		}
		else{
			$output = $default_output;
		}

		$output = array_merge($default_output, $output);

		return $output;
	}

	function filter_entries( $search_criteria ) {
		global $gravityview_view;

		// Search by Number
		if( !empty( $_GET['number'] ) ) {

			$numbers = explode( ' ', $_GET['number'] );

			foreach( $numbers as $number ) {
				$search_criteria['field_filters'][] = array(
					'key' => NULL, // The field ID to search
					'value' => esc_attr( $number ), // The value to search
					'operator' => 'is', // What to search in. Options: `is` or `contains`
				);
			}
		}

		// Search by Letter
		if( !empty( $_GET['letter'] ) ) {
			$search_criteria['field_filters'][] = array(
				'key' => NULL, // The field ID to search
				'value' => esc_attr( $_GET['letter'] ), // The value to search
				'operator' => 'contains', // What to search in. Options: `is` or `contains`
			);
		}

		// add specific fields search
		$search_filters = $this->get_search_filters();

		if( !empty( $search_filters ) && is_array( $search_filters ) ) {

			foreach( $search_filters as $l => $filter ) {

				if( !empty( $filter['value'] ) ) {

					if( false === strpos('.', $filter['key'] ) && ( $this->settings['filter_field'] === $filter['type'] ) ) {
						unset($filter['type']);

						$value = $filter['value'];

						if( strlen( $value ) > 1 ) {

							$numbers = explode( ' ', $value );

							foreach( $numbers as $number ) {

								if( !empty( $number ) && strlen( $number ) == 1 ) {

									// Keep the same key, label for each filter
									$filter['value'] = $letter;

									// Add a search for the value
									$search_criteria['field_filters'][] = $filter;

								}

							}

						}
						else{

							$letter = $value;

							if( !empty( $letter ) && strlen( $letter ) == 1 ) {

								// Keep the same key, label for each filter
								$filter['value'] = $letter;

								// Add a search for the value
								$search_criteria['field_filters'][] = $filter;

							}

						}

						// next field
						continue;

					}

					unset( $filter['type'] );

					$search_criteria['field_filters'][] = $filter;
				}
			}
		}

		return $search_criteria;
	}

	// Displays the A-Z Filter
	public function render_frontend( $widget_args, $content = '', $context = '') {
		global $gravityview_view;

		if( empty( $gravityview_view ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class( $this ) ) );
			return;
		}

		$atts = shortcode_atts( array(
			'show_all_letters' => !empty( $this->settings['show_all_letters']['default'] )
		), $widget_args, 'gravityview_widget_a_z_entry_filter' );

		$show_all_letters = $widget_args['show_all_letters'];
		$filter_field = $widget_args['filter_field'];
		$localization = $widget_args['localization'];
		$uppercase = $widget_args['uppercase'];

		$curr_letter = empty( $_GET['letter'] ) ? '' : $_GET['letter'];

		$letter_links = array(
			'current_letter' => $curr_letter,
			'show_all' => !empty( $atts['show_all_letters'] ),
		);

		$letter_links = $this->render_alphabet_letters( $letter_links, $show_all_letters, $localization, $uppercase);

		if( !empty( $letter_links ) ) {
			echo '<div class="gv-widget-letter-links">' . $letter_links . '</div>';
		} else {
			do_action( 'gravityview_log_debug', 'GravityView_Widget_A_Z_Entry_Filter[render_frontend] No letter links; render_alphabet_letters() returned empty response.' );
		}
	}

	// Renders the alphabet letters
	function render_alphabet_letters( $args = '', $show_all_letters = true, $charset = 'en', $uppercase = true ) {
		global $gravityview_view, $post;

		$form_id = gravityview_get_form_id( $post->ID );

		if( empty($charset) ) { $charset = 'en'; } // Loads english by default.

		include( GV_AZ_Entry_Filter_Plugin_Dir_Path . 'alphabets/alphabets-' . $charset . '.php' );

		$defaults = array(
			'base' => add_query_arg('letter','%#%'),
			'format' => '&letter=%#%',
			'add_args' => array(), //
			'current_letter' => $this->get_first_letter_localized( $charset ),
			'show_all' => false,
			'before_first_letter' => apply_filters('gravityview_az_entry_filter_before_first_letter', NULL),
			'after_last_letter' => apply_filters('gravityview_az_entry_filter_after_last_letter', NULL),
			'first_letter' => $this->get_first_letter_localized( $charset ),
			'last_letter' => $this->get_last_letter_localized( $charset ),
		);

		$args = wp_parse_args( $args, $defaults );
		extract($args, EXTR_SKIP);

		// First we check that we have entries to begin with.
		$total = $gravityview_view->total_entries;
		if( empty( $total ) ) {
			$output = '<ul class="gravityview-alphabet-filter">';

			$output .= '<li class="last"><span class="show-all"><a href="' . remove_query_arg('number', remove_query_arg('letter') ) . '">' . __( 'Show All', 'gravity-view-az-entry-filter' ) . '</a></span></li>';

			$output .= '</ul>';

			return $output;
		}

		$entries = $gravityview_view->entries; // Fetches all entries.

		$output = '<ul class="gravityview-alphabet-filter">';

		$output .= $before_first_letter;

		$other_chars = apply_filters( 'gravityview_az_entry_filter_other_chars', array( '&#35;' ) );
		$alphabet_chars = $this->get_localized_alphabet( $charset );
		$alphabet_chars = array_merge( $other_chars, $alphabet_chars );

		foreach( $alphabet_chars as $char ) { // This is more suited for any alphabet

			$class = '';
			$link = '&#35;'; // Hashtag

			//$entries = $this->find_entries_under_letter( $form_id, $char ); // This checks if there are any entries under the letter.

			// If hashtag '#' = '&#35;'
			if( $char == '&#35;' ) {
				$numbers = array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 );
				$number = implode( ",", $numbers );
				// If entries exist then change the link for the number.
				if( $entries > 0 ) $link = remove_query_arg('letter', add_query_arg('number', $number) );
			}
			else{
				// If entries exist then change the link for the letter.
				if( $entries > 0 ) $link = remove_query_arg('number', add_query_arg('letter', $char) );
			}

			// If all letters are set to show even if the entries are empty then we add a class to disable the linked letter.
			if( empty( $entries ) || $entries < 1 && $show_all_letters == 1 )
				$class = ' class="gv-disabled"';
			// If letters are not set to show then we hide the letter with a little css.
			if( empty( $entries ) || $entries < 1 && $show_all_letters == 0 )
				$class = ' class="gv-hide"';

			// Outputs the letter to filter the results on click.
			$output .= '<li><span' . $class . '><a href="' . $link . '">';

			if( $uppercase ) {
				$char = ucwords( $char );
			}

			// If the current letter matches then put it in bold.
			if( $current_letter == $char ) $char = '<strong>' . $char . '</strong>';

			$output .= $char; // Returns the letter after it's modifications.

			$output .= '</a></span></li>';
		}

		$output .= $after_last_letter;

		$output .= '<li class="last"><span class="show-all"><a href="' . remove_query_arg('number', remove_query_arg('letter') ) . '">' . __( 'Show All', 'gravity-view-az-entry-filter' ) . '</a></span></li>';

		$output .= '</ul>';

		return $output;
	}

	//////

	private function get_search_filters() {
		global $gravityview_view;

		if( !empty( $this->search_filters ) ) {
			return $this->search_filters;
		}

		if( empty( $gravityview_view ) ) { return; }

		// get configured search filters (fields)
		$search_filters = array();
		$view_fields = $gravityview_view->fields;
		$form = $gravityview_view->form;

		if( !empty( $view_fields ) && is_array( $view_fields ) ) {
			foreach( $view_fields as $t => $fields ) {
				foreach( $fields as $field ) {
					if( !empty( $field['search_filter'] ) ) {
						$key = str_replace( '.', '_', $field['id'] );
						$value = esc_attr(rgget('filter_'. $key ) );
						$form_field = gravityview_get_field( $form, $field['id'] );

						$search_filters[] = array( 'key' => $field['id'], 'label' => $field['label'], 'value' => $value, 'type' => $form_field['type'] );
					}
				}
			}
		}

		$this->search_filters = $search_filters;

		return $search_filters;
	}

	/////

	function get_localized_alphabet( $charset ) {
		return alphabet_letters();
	}

	function get_first_letter_localized( $charset ) {
		return first_letter();
	}

	function get_last_letter_localized( $charset ) {
		return last_letter();
	}

	function find_entries_under_letter( $form_id, $char = '' ){
	}

} // GravityView_Widget_A_Z_Entry_Filter
new GravityView_Widget_A_Z_Entry_Filter;

?>
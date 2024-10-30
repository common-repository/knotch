<?php
/**
 * Plugin Name: Knotch
 * Description: Adds the ability to include Knotch widgets. Official Knotch plugin.
 * Version: 0.3.3
 * Author: Knotch
 * Author URI: https://www.knotch.it
 * License: GPL2
 */

class Knotch {
	const OPTIONS_GROUP = 'knotch_api_options_group';
	const OPTIONS_PAGE = 'knotch_options';
	const API_OPTIONS_NAME = 'knotch_api_options';

	public static function loadPreviewScript( $hook ) {
		if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
			return;
		}

		wp_enqueue_script(
			'knotch_preview_js',
			plugins_url( '/js/show-preview.js', __FILE__ )
		);

		wp_enqueue_style(
			'knotch_preview_css',
			plugins_url( '/css/show-preview.css', __FILE__ )
		);
	}

	public static function addBoxes( $post ) {
		add_meta_box(
			'knotch-add-widget',
			'Knotch Widget',
			array( 'Knotch', 'renderWidgetMetaBox' ),
			'post',
			'normal',
			'high'
		);
	}

	public static function renderWidgetMetaBox( $post ) {
		$post_id = $post->ID;
		$disabled_setting = get_post_meta( $post_id, '_knotch_disable_widget', true );
		$topic_name = get_post_meta( $post_id, '_knotch_topic_name', true );
		$topic_id = get_post_meta( $post_id, '_knotch_topic_id', true );

		$options = get_option( self::API_OPTIONS_NAME );
		$default_disabled = $options['defaultOff'];

		echo '<div class="knotch-wrapper">';

		echo '<input type="hidden" class="knotch-topic-name" name="knotch_topic_name" value="' . esc_attr( $topic_name ) . '" />';

		echo '<div class="knotch-topic-step">';
		echo '<div class="knotch-step-header">1. <strong>Select a topic:</strong></div>';

		echo '<div class="knotch-topics-container">';
		$tag_msg_style = $topic_name ? '' : ' style="display: block"';
		$loading_icon_style = $topic_name ? '' : ' style="display: none"';
		echo '<div class="knotch-loading-block"' . $tag_msg_style . '>';

		echo '<div class="knotch-loading-message">Add some tags to your post so we can find the topics for you!</div>';
		echo '<span class="knotch-loading"' . $loading_icon_style . '></span>';
		echo '</div>';
		echo '<div class="knotch-suggestion-message">Here are some relevant topics</div>';

		echo '<div class="knotch-topic-suggestions">';
		if ( $topic_id ) {
			$html_id = 'knotch-suggested-topic-' . esc_attr( $topic_id );
			echo '<div class="knotch-suggested-topic">';
			echo '<input type="radio" class="knotch-topic-id-radio" name="knotch_topic_id" value="' . esc_attr( $topic_id ) .
				'" id="' . $html_id . '" checked="1">';
			echo '<label for="' . $html_id . '">' . htmlspecialchars( $topic_name ) . '</label>';
			echo '</div>';
		}
		echo '</div>'; // End knotch-topic-suggestions

		echo '<div class="knotch-refresh-wrapper"><a class="knotch-suggest-topics button">Refresh</a></div>';

		echo '<div class="knotch-suggested-topic">';
		$other_text = ( $topic_name && ! $topic_id ) ? ' value="' . esc_attr( $topic_name ) . '"' : '';
		$other_checked = $other_text ? ' checked="1"' : '';
		echo '<div class="knotch-center-label">Or create your own...</div>';
		echo '<input type="radio" class="knotch-topic-id-radio knotch-other-radio" name="knotch_topic_id" value="other"'.
			$other_checked . '>';
		echo '<input type="text" class="knotch-topic-name-other" name="knotch_topic_name_other"' .
			$other_text . '>';
		echo '</div>';

		echo '</div>'; // End knotch-topics-container
		echo '</div>'; // End step 1

		Knotch::renderPromptStep();
		Knotch::renderPreviewStep();

		$disabled = $disabled_setting || ( !$topic_name && $default_disabled );
		$disabled_html = checked( $disabled, '1', false );
		echo '<div class="knotch-bottom-shelf">';
		echo '<input type="checkbox" class="knotch-disable-widget" name="knotch_disable_widget" id="knotch-disable-widget" ' . $disabled_html . ' /><label for="knotch-disable-widget">Don&lsquo;t render a widget</label>';
		echo '</div>';

		echo '</div>'; // End knotch-wrapper
	}

	public static function renderPromptStep() {
		$prompt_type = get_post_meta( $post_id, '_knotch_prompt_type', true );
		echo '<div class="knotch-prompt-step">';
		echo '<div class="knotch-step-header">2. <strong>Choose a prompt:</strong></div>';

		echo '<div class="knotch-prompt-wrapper">';

		$default_selected = ' checked="checked"';
		$interest_selected = '';
		if ( $prompt_type == 'interest' ) {
			$default_selected = '';
			$interest_selected = ' checked="checked"';
		}
		echo '<div class="knotch-suggested-topic">';
		echo '<input type="radio" id="knotch-prompt-default" name="knotch_prompt_type" value="default"' . $default_selected . '/>';
		echo '<label for="knotch-prompt-default">How do you feel about...</label>';
		echo '</div>';

		echo '<div class="knotch-suggested-topic">';
		echo '<input type="radio" id="knotch-prompt-interest" name="knotch_prompt_type" value="interest"' . $interest_selected . ' />';
		echo '<label for="knotch-prompt-interest">Are you interested in...</label>';
		echo '</div>';

		echo '</div>';

		echo '</div>'; // End knotch-prompt-step
	}

	public static function renderPreviewStep() {
		echo '<div class="knotch-preview-step">';
		echo '<div class="knotch-step-header"><strong>Live preview of knotch widget:</strong></div>';
		echo '<div class="knotch-widget-preview">';
		echo '<div class="knotch-empty-preview"></div>';
		echo '</div>';
		echo '</div>';
	}

	public static function addOptionsMenu() {
		add_options_page(
			'Settings Admin',
			'Knotch',
			'manage_options',
			self::OPTIONS_PAGE,
			array( 'Knotch', 'renderOptionsPage' )
		);
	}

	public static function registerSettings() {
		$section_id = 'kn_api_setting_section';

		register_setting( self::OPTIONS_GROUP, self::API_OPTIONS_NAME, array( 'Knotch', 'sanitizeSettings' ) );

		add_settings_section(
			$section_id,
			'API settings',
			array( 'Knotch', 'renderApiSettings' ),
			self::OPTIONS_PAGE
		);

		add_settings_field(
			'clientId',
			'Client ID',
			array( 'Knotch', 'renderClientId' ),
			self::OPTIONS_PAGE,
			$section_id
		);

		add_settings_field(
			'secret',
			'API Secret',
			array( 'Knotch', 'renderApiSecret' ),
			self::OPTIONS_PAGE,
			$section_id
		);

		add_settings_field(
			'defaultOff',
			'Default Post Settings',
			array( 'Knotch', 'renderDefaultOff' ),
			self::OPTIONS_PAGE,
			$section_id
		);
	}

	public static function sanitizeSettings( $input ) {
		$input['clientId'] = sanitize_text_field( $input['clientId'] );
		$input['secret'] = sanitize_text_field( $input['secret'] );
		$input['defaultOff'] = $input['defaultOff'] ? 1 : 0;
		return $input;
	}

	public static function renderOptionsPage() {
		echo '<div class="wrap">';
		echo '<h2>Knotch Options</h2>';
		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTIONS_GROUP );
		do_settings_sections( self::OPTIONS_PAGE );
		submit_button();
		echo '</form></div>';
	}

	public static function renderApiSettings() {
		echo '<p>Enter your client ID and your API secret here. Note that you should keep your API secret secure and never share it with anyone.</p>';
		echo '<p>Need help? Contact <a href="mailto:insight@knotch.it">insight@knotch.it</a></p>';
	}

	public static function renderClientId() {
		$options = get_option( self::API_OPTIONS_NAME );
		printf(
			'<input type="text" id="clientId" name="%s" value="%s" class="regular-text" />',
			self::API_OPTIONS_NAME . '[clientId]',
			$options['clientId']
		);
	}

	public static function renderApiSecret() {
		$options = get_option( self::API_OPTIONS_NAME );
		printf(
			'<input type="text" id="secret" name="%s" value="%s" class="regular-text" />',
			self::API_OPTIONS_NAME . '[secret]',
			esc_attr( $options['secret'] )
		);
	}

	public static function renderDefaultOff() {
		$options = get_option( self::API_OPTIONS_NAME );
		$checkbox = sprintf(
			'<input type="checkbox" id="defaultOff" name="%s" %s />',
			self::API_OPTIONS_NAME . '[defaultOff]',
			checked( $options['defaultOff'], 1, false )
		);
		echo '<label for="defaultOff">' . $checkbox .
			' Don&rsquo;t add widgets to new posts by default</label>';
	}

	public static function suggestTopicHandler() {
		$data = stripslashes_deep( $_POST['data'] );

		$title = $data['title'];
		$text = strip_tags( $data['textHtml'] );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML401 );
		$tags = $data['tags'];

		$endpoint = 'https://www.knotch.it/insight/suggestTopics';

		$options = get_option( self::API_OPTIONS_NAME );

		$result = wp_remote_post( $endpoint, array(
			'timeout' => 45,
			'body' => array(
				'title' => $title,
				'text' => $text,
				'tags' => $tags,
				'clientId' => sanitize_text_field( $options['clientId'] ),
				'secret' => sanitize_text_field( $options['secret'] ),
				'v' => 'wp-0.3.3'
			)
		) );

		$json_string = $result['body'];

		// Only respond if the response JSON is valid
		if ( json_decode( $json_string, true ) !== NULL ) {
			echo $json_string;
		}

		die();
	}

	public static function savePostHandler( $post_id ) {
		if ( $_POST['post_type'] != 'post' ) {
			return;
		}

		if ( isset( $_POST['knotch_disable_widget'] ) &&
				$_POST['knotch_disable_widget'] ) {
			delete_post_meta( $post_id, '_knotch_topic_id' );
			delete_post_meta( $post_id, '_knotch_topic_name' );
			delete_post_meta( $post_id, '_knotch_prompt_type' );
			update_post_meta( $post_id, '_knotch_disable_widget', '1' );
			return;
		} else {
			delete_post_meta( $post_id, '_knotch_disable_widget' );
		}


		if ( isset( $_POST['knotch_topic_id'] ) &&
				 isset( $_POST['knotch_topic_name'] ) &&
				 $_POST['knotch_topic_name'] &&
				 $_POST['knotch_topic_name'] !== 'undefined' ) {
			$post_topic_id = $_POST['knotch_topic_id'];
			if ( $post_topic_id == 'other' || $post_topic_id == 'undefined' ) {
				delete_post_meta( $post_id, '_knotch_topic_id' );
			} else {
				update_post_meta(
					$post_id,
					'_knotch_topic_id',
					sanitize_text_field( $_POST['knotch_topic_id'] )
				);
			}

			$topic_name = $_POST['knotch_topic_name'];
			if ( strlen( $topic_name ) > 160 ) {
				$topic_name = '';
			}

			update_post_meta( $post_id, '_knotch_topic_name', $topic_name );

			update_post_meta(
				$post_id,
				'_knotch_prompt_type',
				sanitize_text_field( $_POST['knotch_prompt_type'] )
			);
		}
	}

	public static function addKnotchWidget( $content ) {
		$post = $GLOBALS['post'];
		if ( $post->post_type != 'post' ) {
			return $content;
		}

		$topic_id = get_post_meta( $post->ID, '_knotch_topic_id', true );
		$topic_name = get_post_meta( $post->ID, '_knotch_topic_name', true );
		$prompt_type = get_post_meta( $post->ID, '_knotch_prompt_type', true );

		if ( $topic_name && $topic_name !== 'undefined' ) {
			$permalink = get_permalink( $post->ID );

			$options = get_option( self::API_OPTIONS_NAME );

			$query_data = array(
				'cid' => urlencode( $options['clientId'] ),
				'canonicalURL' => urlencode( esc_url( $permalink ) ),
				'topicName' => urlencode( $topic_name )
			);

			if ( $topic_id && $topic_id !== 'undefined' ) {
				$query_data['topicID'] = urlencode( $topic_id );
			}

			if ( $prompt_type == 'interest' ) {
				$query_data['positiveLabel'] = urlencode( 'interested' );
				$query_data['negativeLabel'] = urlencode( 'uninterested' );
				$query_data['prompt'] = urlencode( 'Are you interested in %t?' );
				$query_data['hoverPrompt'] = urlencode( 'You are %s in %t' );
			}

			$iframe_src = add_query_arg( $query_data, 'https://www.knotch.it/extern/quickKnotchBox' );
			$iframe_src = esc_url( $iframe_src );

			return $content . '<iframe class="knotch-post-widget" frameborder="0" src="' .
				$iframe_src . '" style="width: 98%; height: 250px"></iframe>';
		}

		return $content;
	}
}

if ( is_admin() ) {
	add_action( 'admin_menu', array( 'Knotch', 'addOptionsMenu' ) );
	add_action( 'admin_init', array( 'Knotch', 'registerSettings' ) );

	add_action( 'add_meta_boxes_post', array( 'Knotch', 'addBoxes' ) );

	add_action( 'wp_ajax_knotch_suggest_topic', array( 'Knotch', 'suggestTopicHandler' ) );
	add_action( 'admin_enqueue_scripts', array( 'Knotch', 'loadPreviewScript' ) );

	add_action( 'save_post', array( 'Knotch', 'savePostHandler' ) );
}

add_filter( 'the_content', array( 'Knotch', 'addKnotchWidget' ) );

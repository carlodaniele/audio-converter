<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Plugin {
	const ABILITY_NAME                    = 'audio-converter-for-wp/audio-to-post';
	const ABILITY_RUN_PATH                = '/wp-abilities/v1/audio-converter-for-wp/audio-to-post/run';
	const ABILITY_RUN_PATH_ALT            = '/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run';
	const OPTION_KEY                      = 'aicb_settings';

	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_ability' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
	}

	public static function register_ability_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'content-generation',
			array(
				'label'       => 'Content Generation',
				'description' => 'AI-powered content generation and transformation abilities.',
			)
		);
	}

	public static function register_ability(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$input_schema = array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array(
				'contract_version',
				'external_run_id',
				'source',
				'audio',
			),
			'properties'           => array(
				'contract_version' => array(
					'type'    => 'string',
					'pattern' => '^1\\.[0-9]+\\.[0-9]+$',
				),
				'external_run_id'  => array(
					'type'      => 'string',
					'minLength' => 3,
					'maxLength' => 128,
				),
				'source'           => array(
					'type' => 'string',
					'enum' => array( 'telegram', 'web', 'api', 'manual' ),
				),
				'source_metadata'  => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
				'audio'            => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'media_id'   => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'signed_url' => array(
							'type'   => 'string',
							'format' => 'uri',
						),
						'base64'     => array(
							'type'      => 'string',
							'minLength' => 16,
						),
						'mime_type'  => array(
							'type' => 'string',
						),
					),
					'oneOf'                => array(
						array( 'required' => array( 'media_id' ) ),
						array( 'required' => array( 'signed_url' ) ),
						array( 'required' => array( 'base64', 'mime_type' ) ),
					),
				),
				'editorial_options' => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'language'      => array( 'type' => 'string' ),
						'tone'          => array(
							'type' => 'string',
							'enum' => array( 'neutral', 'professional', 'conversational' ),
						),
						'target_length' => array(
							'type' => 'string',
							'enum' => array( 'short', 'medium', 'long' ),
						),
						'constraints'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'proper_noun_hints' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'publish_options'   => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'status'         => array(
							'type' => 'string',
							'enum' => array( 'draft', 'publish' ),
						),
						'post_type'      => array( 'type' => 'string' ),
						'target_post_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'taxonomy_terms' => array(
							'type'                 => 'object',
							'additionalProperties' => array(
								'type'  => 'array',
								'items' => array( 'type' => array( 'string', 'integer' ) ),
							),
						),
					),
				),
				'media_options'     => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'featured_image_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'gallery_image_ids' => array(
							'type'  => 'array',
							'items' => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
						),
					),
				),
			),
		);

		$output_schema = array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array(
				'contract_version',
				'run_id',
				'status',
				'quality_flags',
				'processing_timestamps',
				'debug_reference_id',
			),
			'properties'           => array(
				'contract_version'      => array(
					'type'    => 'string',
					'pattern' => '^1\\.[0-9]+\\.[0-9]+$',
				),
				'run_id'                => array(
					'type'      => 'string',
					'minLength' => 8,
				),
				'title'                 => array(
					'type' => 'string',
				),
				'blocks'                => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'object',
					),
				),
				'updated_existing_post' => array(
					'type' => 'boolean',
				),
				'post_id'               => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'post_url'              => array(
					'type'   => 'string',
					'format' => 'uri',
				),
				'status'                => array(
					'type' => 'string',
					'enum' => array( 'pending', 'processing', 'completed', 'failed' ),
				),
				'quality_flags'         => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'processing_timestamps' => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'started_at'   => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'completed_at' => array(
							'type'   => array( 'string', 'null' ),
							'format' => 'date-time',
						),
					),
				),
				'debug_reference_id'    => array(
					'type'      => 'string',
					'minLength' => 8,
				),
				'error'                 => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => array( 'code', 'message', 'retryable' ),
					'properties'           => array(
						'code'      => array(
							'type' => 'string',
							'enum' => array(
								'invalid_input',
								'unauthorized',
								'duplicate_run',
								'ai_provider_unavailable',
								'publish_failed',
								'internal_error',
							),
						),
						'message'   => array( 'type' => 'string' ),
						'retryable' => array( 'type' => 'boolean' ),
						'details'   => array(
							'type'                 => 'object',
							'additionalProperties' => true,
						),
					),
				),
			),
			'allOf'                => array(
				array(
					'if'   => array(
						'properties' => array( 'status' => array( 'const' => 'completed' ) ),
						'required'   => array( 'status' ),
					),
					'then' => array(
						'required' => array( 'post_id', 'post_url' ),
					),
				),
				array(
					'if'   => array(
						'properties' => array( 'status' => array( 'const' => 'failed' ) ),
						'required'   => array( 'status' ),
					),
					'then' => array(
						'required' => array( 'error' ),
					),
				),
			),
		);

		wp_register_ability(
			self::ABILITY_NAME,
			array(
				'category'            => 'content-generation',
				'label'               => 'Audio to Post',
				'description'         => 'Converts audio into a structured draft post using WordPress AI connectors.',
				'input_schema'        => $input_schema,
				'output_schema'       => $output_schema,
				'execute_callback'    => array( 'Audio_Converter_REST_Controller', 'execute_ability' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	public static function register_settings(): void {
		register_setting(
			'aicb_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	public static function sanitize_settings( $value ): array {
		$defaults = self::default_settings();
		if ( ! is_array( $value ) ) {
			return $defaults;
		}

		$language = isset( $value['default_language'] ) ? sanitize_text_field( (string) $value['default_language'] ) : $defaults['default_language'];
		$tone     = isset( $value['default_tone'] ) ? sanitize_text_field( (string) $value['default_tone'] ) : $defaults['default_tone'];
		$length   = isset( $value['default_target_length'] ) ? sanitize_text_field( (string) $value['default_target_length'] ) : $defaults['default_target_length'];

		if ( ! in_array( $tone, array( 'neutral', 'professional', 'conversational' ), true ) ) {
			$tone = $defaults['default_tone'];
		}

		if ( ! in_array( $length, array( 'short', 'medium', 'long' ), true ) ) {
			$length = $defaults['default_target_length'];
		}

		return array(
			'default_language'      => $language,
			'default_tone'          => $tone,
			'default_target_length' => $length,
		);
	}

	public static function register_admin_menu(): void {
		add_options_page(
			'Audio Converter for WP',
			'Audio Converter',
			'manage_options',
			'audio-converter-for-wp',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = self::get_settings();
		?>
		<div class="wrap">
			<h1>Audio Converter for WP</h1>
			<p>Configure default editorial values used by the Gutenberg sidebar.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'aicb_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="aicb-default-language">Default language</label></th>
						<td><input id="aicb-default-language" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_language]" type="text" value="<?php echo esc_attr( $options['default_language'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="aicb-default-tone">Default tone</label></th>
						<td>
							<select id="aicb-default-tone" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_tone]">
								<option value="neutral" <?php selected( $options['default_tone'], 'neutral' ); ?>>neutral</option>
								<option value="professional" <?php selected( $options['default_tone'], 'professional' ); ?>>professional</option>
								<option value="conversational" <?php selected( $options['default_tone'], 'conversational' ); ?>>conversational</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aicb-default-target-length">Default target length</label></th>
						<td>
							<select id="aicb-default-target-length" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_target_length]">
								<option value="short" <?php selected( $options['default_target_length'], 'short' ); ?>>short</option>
								<option value="medium" <?php selected( $options['default_target_length'], 'medium' ); ?>>medium</option>
								<option value="long" <?php selected( $options['default_target_length'], 'long' ); ?>>long</option>
							</select>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save settings' ); ?>
			</form>
		</div>
		<?php
	}

	public static function enqueue_editor_assets(): void {
		$script_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/editor-sidebar.js';
		$script_url  = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/editor-sidebar.js';

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_enqueue_media();

		wp_register_script(
			'audio-converter-editor-sidebar',
			$script_url,
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-blocks', 'wp-i18n' ),
			(string) filemtime( $script_path ),
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'audio-converter-editor-sidebar', 'audio-converter-for-wp' );
		}

		$settings = self::get_settings();

		wp_localize_script(
			'audio-converter-editor-sidebar',
			'AICBData',
			array(
				'abilityRunPath'    => self::ABILITY_RUN_PATH,
				'abilityRunPathAlt' => self::ABILITY_RUN_PATH_ALT,
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'settings'          => $settings,
			)
		);

		wp_enqueue_script( 'audio-converter-editor-sidebar' );
	}

	private static function default_settings(): array {
		return array(
			'default_language'      => 'en-US',
			'default_tone'          => 'professional',
			'default_target_length' => 'medium',
		);
	}

	public static function get_settings(): array {
		$defaults = self::default_settings();
		$stored   = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			return $defaults;
		}

		return wp_parse_args( $stored, $defaults );
	}
}

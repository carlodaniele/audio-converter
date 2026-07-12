<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Plugin {
	const ABILITY_NAME                    = 'audio-converter-for-wp/audio-to-post';
	const ABILITY_RUN_PATH                = '/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run';
	const OPTION_KEY                      = 'aicb_settings';

	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_ability' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_filter( 'plugin_action_links_audio-converter-for-wp/audio-converter-for-wp.php', array( __CLASS__, 'plugin_action_links' ) );
	}

	public static function plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'options-general.php?page=audio-converter-for-wp' );
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $settings_url ),
			esc_html__( 'Settings', 'audio-converter-for-wp' )
		);

		array_unshift( $links, $settings_link );

		return $links;
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
						'temperature'   => array(
							'type'    => 'number',
							'minimum' => 0,
							'maximum' => 1,
							'default' => 0.3,
						),
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
		$hints    = isset( $value['default_proper_noun_hints'] ) ? sanitize_textarea_field( (string) $value['default_proper_noun_hints'] ) : $defaults['default_proper_noun_hints'];
		$mode     = isset( $value['default_insertion_mode'] ) ? sanitize_text_field( (string) $value['default_insertion_mode'] ) : $defaults['default_insertion_mode'];
		$auto_title = ! empty( $value['default_auto_apply_title'] ) ? 1 : 0;

		if ( ! in_array( $tone, array( 'neutral', 'professional', 'conversational' ), true ) ) {
			$tone = $defaults['default_tone'];
		}

		if ( ! in_array( $length, array( 'short', 'medium', 'long' ), true ) ) {
			$length = $defaults['default_target_length'];
		}

		if ( ! in_array( $mode, array( 'append', 'replace' ), true ) ) {
			$mode = $defaults['default_insertion_mode'];
		}

		$language_choices = self::get_language_choices();
		if ( ! isset( $language_choices[ $language ] ) ) {
			$language = $defaults['default_language'];
		}

		// Free version keeps a fixed default temperature.
		$temperature = $defaults['default_temperature'];

		return array(
			'default_language'      => $language,
			'default_tone'          => $tone,
			'default_target_length' => $length,
			'default_proper_noun_hints' => $hints,
			'default_insertion_mode' => $mode,
			'default_auto_apply_title' => $auto_title,
			'default_temperature'   => $temperature,
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

		$did_refresh = false;
		if ( isset( $_GET['aicb_refresh_languages'], $_GET['_wpnonce'] ) ) {
			$refresh_flag = sanitize_text_field( wp_unslash( (string) $_GET['aicb_refresh_languages'] ) );
			$nonce        = sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) );
			if ( '1' === $refresh_flag && wp_verify_nonce( $nonce, 'aicb_refresh_languages' ) ) {
				if ( function_exists( 'delete_site_transient' ) ) {
					delete_site_transient( 'translations_api' );
				}
				$did_refresh = true;
			}
		}

		$options                     = self::get_settings();
		$language_choices            = self::get_language_choices();
		$has_remote_language_catalog = self::has_remote_language_catalog();
		$refresh_url                = wp_nonce_url(
			add_query_arg(
				array(
					'page'                   => 'audio-converter-for-wp',
					'aicb_refresh_languages' => '1',
				),
				admin_url( 'options-general.php' )
			),
			'aicb_refresh_languages'
		);
		?>
		<div class="wrap">
			<h1>Audio Converter for WP</h1>
			<p>Configure default editorial values used by the Gutenberg sidebar.</p>
			<?php if ( $did_refresh ) : ?>
				<div class="notice notice-success is-dismissible"><p>Language catalog refreshed.</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'aicb_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="aicb-default-language">Default language</label></th>
						<td>
							<div class="aicb-language-controls">
								<select id="aicb-default-language" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_language]">
									<?php foreach ( $language_choices as $locale => $label ) : ?>
										<option value="<?php echo esc_attr( $locale ); ?>" <?php selected( $options['default_language'], $locale ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
								<a href="<?php echo esc_url( $refresh_url ); ?>" class="aicb-language-refresh-link">Refresh language catalog</a>
							</div>
							<?php if ( ! $has_remote_language_catalog ) : ?>
								<p class="description">Showing installed languages only. Remote WordPress language catalog is currently unavailable.</p>
							<?php endif; ?>
						</td>
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
					<tr>
						<th scope="row"><label for="aicb-default-proper-noun-hints">Default proper noun hints</label></th>
						<td>
							<textarea id="aicb-default-proper-noun-hints" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_proper_noun_hints]" rows="4" class="large-text"><?php echo esc_textarea( $options['default_proper_noun_hints'] ); ?></textarea>
							<p class="description">Comma or newline separated hints used as initial value in the editor sidebar. Example: "WordPress, WooCommerce, OpenAI, ChatGPT".</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aicb-default-insertion-mode">Default insertion mode</label></th>
						<td>
							<select id="aicb-default-insertion-mode" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_insertion_mode]">
								<option value="append" <?php selected( $options['default_insertion_mode'], 'append' ); ?>>append</option>
								<option value="replace" <?php selected( $options['default_insertion_mode'], 'replace' ); ?>>replace</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aicb-default-auto-apply-title">Auto-apply generated title</label></th>
						<td>
							<label for="aicb-default-auto-apply-title">
								<input id="aicb-default-auto-apply-title" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_auto_apply_title]" type="checkbox" value="1" <?php checked( ! empty( $options['default_auto_apply_title'] ) ); ?> />
								Apply generated title to the current editor title by default.
							</label>
						</td>
					</tr>
				</table>
				<p class="description">Documentation is included locally in the plugin package. See readme.txt and docs/free-user-guide-en.md.</p>

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
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'settings'          => $settings,
				'languageOptions'   => self::language_options_for_js(),
				'hasRemoteLanguageCatalog' => self::has_remote_language_catalog(),
			)
		);

		wp_enqueue_script( 'audio-converter-editor-sidebar' );
	}

	public static function enqueue_admin_assets( string $hook ): void {
		if ( 'settings_page_audio-converter-for-wp' !== $hook ) {
			return;
		}

		$style_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/admin-settings.css';
		$style_url  = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin-settings.css';

		if ( ! file_exists( $style_path ) ) {
			return;
		}

		wp_enqueue_style(
			'audio-converter-admin-settings',
			$style_url,
			array(),
			(string) filemtime( $style_path )
		);
	}

	private static function default_settings(): array {
		return array(
			'default_language'      => 'en-US',
			'default_tone'          => 'professional',
			'default_target_length' => 'medium',
			'default_proper_noun_hints' => '',
			'default_insertion_mode' => 'append',
			'default_auto_apply_title' => 1,
			'default_temperature'   => '0.3',
		);
	}

	private static function get_language_choices(): array {
		if ( ! function_exists( 'wp_get_available_translations' ) ) {
			$translation_install_file = ABSPATH . 'wp-admin/includes/translation-install.php';
			if ( file_exists( $translation_install_file ) ) {
				require_once $translation_install_file;
			}
		}

		$choices = array(
			'en-US' => 'English (United States)',
		);

		if ( function_exists( 'wp_get_available_translations' ) ) {
			$translations = wp_get_available_translations();
			if ( is_array( $translations ) ) {
				foreach ( $translations as $locale => $data ) {
					$normalized = str_replace( '_', '-', (string) $locale );
					if ( '' === $normalized ) {
						continue;
					}

					$english_name = isset( $data['english_name'] ) ? (string) $data['english_name'] : $normalized;
					$native_name  = isset( $data['native_name'] ) ? (string) $data['native_name'] : '';
					$label        = ( '' !== $native_name && $native_name !== $english_name )
						? $english_name . ' - ' . $native_name
						: $english_name;

					$choices[ $normalized ] = $label;
				}
			}
		}

		if ( function_exists( 'wp_get_installed_translations' ) ) {
			$installed_translations = wp_get_installed_translations( 'core' );
			if ( isset( $installed_translations['default'] ) && is_array( $installed_translations['default'] ) ) {
				foreach ( $installed_translations['default'] as $locale => $data ) {
					$normalized = str_replace( '_', '-', (string) $locale );
					if ( '' === $normalized || isset( $choices[ $normalized ] ) ) {
						continue;
					}

					$english_name = isset( $data['english_name'] ) ? (string) $data['english_name'] : $normalized;
					$native_name  = isset( $data['native_name'] ) ? (string) $data['native_name'] : '';
					$label        = ( '' !== $native_name && $native_name !== $english_name )
						? $english_name . ' - ' . $native_name
						: $english_name;

					$choices[ $normalized ] = $label;
				}
			}
		}

		if ( function_exists( 'get_available_languages' ) ) {
			foreach ( get_available_languages() as $locale ) {
				$normalized = str_replace( '_', '-', (string) $locale );
				if ( '' !== $normalized && ! isset( $choices[ $normalized ] ) ) {
					$choices[ $normalized ] = $normalized;
				}
			}
		}

		$site_locale = function_exists( 'get_locale' ) ? str_replace( '_', '-', (string) get_locale() ) : '';
		if ( '' !== $site_locale && ! isset( $choices[ $site_locale ] ) ) {
			$choices[ $site_locale ] = $site_locale;
		}

		asort( $choices, SORT_NATURAL | SORT_FLAG_CASE );

		return $choices;
	}

	private static function has_remote_language_catalog(): bool {
		if ( ! function_exists( 'wp_get_available_translations' ) ) {
			$translation_install_file = ABSPATH . 'wp-admin/includes/translation-install.php';
			if ( file_exists( $translation_install_file ) ) {
				require_once $translation_install_file;
			}
		}

		if ( ! function_exists( 'wp_get_available_translations' ) ) {
			return false;
		}

		$translations = wp_get_available_translations();
		return is_array( $translations ) && ! empty( $translations );
	}

	private static function language_options_for_js(): array {
		$options = array();
		foreach ( self::get_language_choices() as $value => $label ) {
			$options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		return $options;
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

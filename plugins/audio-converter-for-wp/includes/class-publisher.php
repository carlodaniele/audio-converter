<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Publisher {
	private static function build_post_content( array $blocks ): string {
		$content = '';

		foreach ( $blocks as $block ) {
			$block_name = isset( $block['name'] ) ? (string) $block['name'] : '';
			if ( '' === $block_name ) {
				continue;
			}

			$attrs      = isset( $block['attributes'] ) && is_array( $block['attributes'] ) ? $block['attributes'] : array();
			$inner_html = isset( $block['html'] ) ? (string) $block['html'] : '';

			$content .= serialize_block(
				array(
					'blockName'    => $block_name,
					'attrs'        => $attrs,
					'innerBlocks'  => array(),
					'innerHTML'    => $inner_html,
					'innerContent' => '' !== $inner_html ? array( $inner_html ) : array(),
				)
			);
		}

		return $content;
	}

	private static function update_existing_post( int $post_id, string $title, string $content ) {
		if ( $post_id <= 0 ) {
			return null;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Audio_Converter_Ability_Contract::error_response( 'invalid_input', 'target_post_id does not exist.' );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'unauthorized', 'You are not allowed to edit the target post.' );
		}

		$update_payload = array(
			'ID'           => $post_id,
			'post_content' => $content,
		);

		if ( '' !== trim( $title ) ) {
			$update_payload['post_title'] = $title;
		}

		$updated_id = wp_update_post( $update_payload, true );
		if ( is_wp_error( $updated_id ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'publish_failed', $updated_id->get_error_message() );
		}

		return array(
			'post_id'               => (int) $post_id,
			'post_url'              => (string) get_permalink( (int) $post_id ),
			'updated_existing_post' => true,
		);
	}

	public static function create_draft_from_blocks( string $title, array $blocks, array $publish_options = array() ) {
		if ( empty( $blocks ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'publish_failed', 'Cannot publish empty block payload.' );
		}

		$content = self::build_post_content( $blocks );

		$target_post_id = isset( $publish_options['target_post_id'] ) ? (int) $publish_options['target_post_id'] : 0;
		if ( $target_post_id > 0 ) {
			$updated = self::update_existing_post( $target_post_id, $title, $content );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			if ( is_array( $updated ) ) {
				return $updated;
			}
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'publish_failed', $post_id->get_error_message() );
		}

		return array(
			'post_id'               => (int) $post_id,
			'post_url'              => (string) get_permalink( (int) $post_id ),
			'updated_existing_post' => false,
		);
	}
}

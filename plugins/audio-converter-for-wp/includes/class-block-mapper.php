<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Block_Mapper {
	private static function heading_tag_for_level( int $level ): string {
		return ( 3 === $level ) ? 'h3' : 'h2';
	}

	public static function sections_to_blocks( array $sections ): array {
		$blocks = array();

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$heading = isset( $section['heading'] ) ? trim( (string) $section['heading'] ) : '';
			if ( '' === $heading ) {
				continue;
			}

			$blocks[] = array(
				'name'       => 'core/heading',
				'attributes' => array(
					'level'   => ( isset( $section['level'] ) && 3 === (int) $section['level'] ) ? 3 : 2,
				),
				'html'       => sprintf(
					'<%1$s>%2$s</%1$s>',
					self::heading_tag_for_level( ( isset( $section['level'] ) && 3 === (int) $section['level'] ) ? 3 : 2 ),
					esc_html( $heading )
				),
			);

			if ( isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ) {
				foreach ( $section['paragraphs'] as $paragraph ) {
					$clean = trim( (string) $paragraph );
					if ( '' === $clean ) {
						continue;
					}

					$blocks[] = array(
						'name'       => 'core/paragraph',
						'attributes' => array(
						),
						'html'       => '<p>' . esc_html( $clean ) . '</p>',
					);
				}
			}

			if ( isset( $section['bullet_points'] ) && is_array( $section['bullet_points'] ) ) {
				$items = '';
				foreach ( $section['bullet_points'] as $point ) {
					$clean = trim( sanitize_text_field( (string) $point ) );
					if ( '' !== $clean ) {
						$items .= '<li>' . esc_html( $clean ) . '</li>';
					}
				}

				if ( '' !== $items ) {
					$blocks[] = array(
						'name'       => 'core/list',
						'attributes' => array(
						),
						'html'       => '<ul>' . $items . '</ul>',
					);
				}
			}
		}

		return $blocks;
	}
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Normalizer {
	public static function normalize_structured_post( array $structured ): array {
		$title = isset( $structured['title'] ) ? sanitize_text_field( (string) $structured['title'] ) : '';
		$sections = array();

		if ( isset( $structured['sections'] ) && is_array( $structured['sections'] ) ) {
			foreach ( $structured['sections'] as $section ) {
				if ( ! is_array( $section ) ) {
					continue;
				}

				$heading = isset( $section['heading'] ) ? trim( sanitize_text_field( (string) $section['heading'] ) ) : '';
				if ( '' === $heading ) {
					continue;
				}

				$paragraphs = array();
				if ( isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ) {
					foreach ( $section['paragraphs'] as $paragraph ) {
						$clean = trim( sanitize_textarea_field( (string) $paragraph ) );
						if ( '' !== $clean ) {
							$paragraphs[] = $clean;
						}
					}
				}

				if ( empty( $paragraphs ) ) {
					continue;
				}

				$bullet_points = array();
				if ( isset( $section['bullet_points'] ) && is_array( $section['bullet_points'] ) ) {
					foreach ( $section['bullet_points'] as $bullet_point ) {
						$clean = trim( sanitize_text_field( (string) $bullet_point ) );
						if ( '' !== $clean ) {
							$bullet_points[] = $clean;
						}
					}
				}

				$sections[] = array(
					'heading'       => $heading,
					'level'         => ( isset( $section['level'] ) && 3 === (int) $section['level'] ) ? 3 : 2,
					'paragraphs'    => $paragraphs,
					'bullet_points' => $bullet_points,
				);
			}
		}

		return array(
			'title'    => $title,
			'sections' => $sections,
		);
	}
}

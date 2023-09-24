<?php

namespace MediaWiki\Extension\Description2;

class SimpleDescriptionProvider implements DescriptionProvider {

	/**
	 * @param string $text
	 * @return string
	 */
	public function derive( string $text ): ?string {
		$pattern = '%<table\b[^>]*+>(?:(?R)|[^<]*+(?:(?!</?table\b)<[^<]*+)*+)*+</table>%i';
		$myText = preg_replace( $pattern, '', $text );

		$paragraphs = [];
		if ( preg_match_all( '#<p>.*?</p>#is', $myText, $paragraphs ) ) {
			foreach ( $paragraphs[0] as $paragraph ) {
				$paragraph = trim( strip_tags( $paragraph ) );
				if ( !$paragraph ) {
					continue;
				}

				if ( $paragraph === '' ) {
					return null;
				}

				return $paragraph;
			}
		}

		return null;
	}
}

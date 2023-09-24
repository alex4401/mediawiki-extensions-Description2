<?php

namespace MediaWiki\Extension\Description2;

use Parser;
use ParserOutput;
use PPFrame;

/**
 * Description2 – Adds meaningful description <meta> tag to MW pages and into the parser output
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://danf.ca/mw/)
 * @author alex4401
 * @copyright Copyright 2010 – Daniel Friesen
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:Description2 Documentation
 */

class Description2 {

	/**
	 * @param ParserOutput $parserOutput The parser output.
	 * @param string $desc The description text.
	 */
	public static function setDescription( ParserOutput $parserOutput, $desc ) {
		if ( $parserOutput->getPageProperty( 'description' ) !== null ) {
			return;
		}
		$parserOutput->setPageProperty( 'description', $desc );
	}

	/**
	 * @param Parser $parser The parser.
	 * @param PPFrame $frame The frame.
	 * @param string[] $args The arguments of the parser function call.
	 * @return string
	 */
	public static function parserFunctionCallback( Parser $parser, PPFrame $frame, $args ) {
		$desc = isset( $args[0] ) ? $frame->expand( $args[0] ) : '';
		self::setDescription( $parser->getOutput(), $desc );
		return '';
	}

	/**
	 * Returns no more than a requested number of characters, preserving words
	 *
	 * Borrowed from TextExtracts.
	 *
	 * @param string $text Source text to extract from
	 * @param int $requestedLength Maximum number of characters to return
	 * @return string
	 */
	public static function getFirstChars( string $text, int $requestedLength ) {
		if ( $requestedLength <= 0 ) {
			return '';
		}

		$length = mb_strlen( $text );
		if ( $length <= $requestedLength ) {
			return $text;
		}

		// This ungreedy pattern always matches, just might return an empty string
		$pattern = '/^[\w\/]*>?/su';
		preg_match( $pattern, mb_substr( $text, $requestedLength ), $m );
		$truncatedText = mb_substr( $text, 0, $requestedLength ) . $m[0];
		if ( $truncatedText === $text ) {
			return $text;
		}

		return trim( $truncatedText );
	}
}

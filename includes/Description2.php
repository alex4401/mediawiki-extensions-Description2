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
		if ( method_exists( $parserOutput, 'getPageProperty' ) ) {
			// MW 1.38+
			if ( $parserOutput->getPageProperty( 'description' ) !== null ) {
				return;
			}
			$parserOutput->setPageProperty( 'description', $desc );
		} else {
			if ( $parserOutput->getProperty( 'description' ) !== false ) {
				return;
			}
			$parserOutput->setProperty( 'description', $desc );
		}
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
}

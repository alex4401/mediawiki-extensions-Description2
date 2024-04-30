<?php

namespace MediaWiki\Extension\Description2;

use Config;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\HtmlFormatter;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class RemexDescriptionProvider implements DescriptionProvider {
	public const CUT_ELEMENT_MARKER = "\2_d2_\3";

	/** @var string[] */
	private array $toRemove;
	/** @var bool */
	private bool $useFirstSectionOnly;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->toRemove = $config->get( 'DescriptionRemoveElements' );
		$this->useFirstSectionOnly = $config->get( 'DescriptionFirstSectionOnly' );
	}

	/**
	 * Extracts description from the HTML representation of a page.
	 *
	 * This algorithm:
	 * 1. Looks for the first <hN> heading (potentially included in the ToC) and cuts the text to avoid unnecessary
	 *    server load.
	 * 2. Invokes RemexHTML to parse and reserialise the HTML representation.
	 * 3. Comments are excluded.
	 * 4. HTML elements are filtered by tag name and the 'class' attribute. Removals are dictated through the
	 *    $wgDescriptionRemoveElements config variable.
	 * 5. HTML tags are stripped, and only text is preserved.
	 * 6. Strips white-space around the extract.
	 *
	 * This is more costly than the SimpleDescriptionProvider but is far more flexible and easier to manipulate by
	 * editors.
	 *
	 * @param string $text
	 * @return string
	 */
	public function derive( string $text ): ?string {
		// If configuration tells us to try extracting with just the first section, cut the input at the ToC placeholder
		// and see if we get any output (more than three characters).
		// Otherwise, fall back onto running on the entire text.
		if ( $this->useFirstSectionOnly ) {
			// Match everything until the ToC placeholder. Since we're using a lookahead, this won't return anything if
			// the placeholder is missing.
			if ( preg_match( "/^.*(?=(?:<meta property=\"mw:PageProp\/toc\"|<mw:tocplace))/s", $text, $matches ) ) {
				$beforeToC = $matches[0];
				$result = $this->deriveInternal( $beforeToC );
				if ( strlen( $result ) > 3 ) {
					return $result;
				}
			}
		}

		return $this->deriveInternal( $text );
	}

	/**
	 * Internal method to perform the extract derivation.
	 *
	 * @param string $text
	 * @return string
	 */
	private function deriveInternal( string $text ): ?string {
		$formatter = new class( $options = [], $this->toRemove ) extends HtmlFormatter {

			/** @var string[] */
			private array $toRemove;

			/**
			 * @param array $options
			 * @param array $toRemove
			 */
			public function __construct( $options, array $toRemove ) {
				parent::__construct( $options );
				$this->toRemove = $toRemove;
			}

			/**
			 * Skips comments.
			 *
			 * @param SerializerNode $parent
			 * @param string $text
			 * @return void
			 */
			public function comment( SerializerNode $parent, $text ) {
				return '';
			}

			/**
			 * Strips out HTML tags leaving bare text, and strips out undesirable elements per configuration.
			 *
			 * @param SerializerNode $parent
			 * @param SerializerNode $node
			 * @param string $contents
			 * @return void
			 */
			public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
				// Read CSS classes off the node into an array for later
				$nodeClasses = $node->attrs->getValues()['class'] ?? null;
				if ( $nodeClasses ) {
					$nodeClasses = explode( ' ', $nodeClasses );
				}

				// Strip away elements matching our removal list. This only supports tags and classes.
				foreach ( $this->toRemove as $selectorish ) {
					$split = explode( '.', $selectorish );
					$tagName = array_shift( $split );

					if ( $tagName !== '' && $node->name !== $tagName ) {
						continue;
					}

					if ( $split && ( !$nodeClasses || array_diff( $split, $nodeClasses ) ) ) {
						continue;
					}

					// Replace this element with a temporary marker, we'll use it to normalise white-space later
					return RemexDescriptionProvider::CUT_ELEMENT_MARKER;
				}

				return $contents;
			}

			/**
			 * Skips document starter tags.
			 *
			 * @param string $fragmentNamespace
			 * @param string $fragmentName
			 * @return void
			 */
			public function startDocument( $fragmentNamespace, $fragmentName ) {
				return '';
			}
		};

		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $text, [
			'ignoreErrors' => true,
			'skipPreprocess' => true,
			'ignoreNulls' => true,
		] );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );

		$result = $serializer->getResult();

		// Reduce white-space
		$replacements = [
			'&nbsp;' => ' ',
			' ' . self::CUT_ELEMENT_MARKER . ' ' => ' ',
			self::CUT_ELEMENT_MARKER => '',
			'  ' => ' ',
			'( ' => '(',
			' )' => ')',
			"\n\n" => "\n",
		];
		$result = str_replace( array_keys( $replacements ), array_values( $replacements ), $result );
		// Decode HTML entities
		$result = htmlspecialchars_decode( $result );

		$result = trim( $result );

		return $result;
	}
}

<?php

namespace MediaWiki\Extension\Description2;

use MediaWiki\Config\ServiceOptions;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\HtmlFormatter;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class RemexDescriptionProvider implements DescriptionProvider {

	/**
	 * @internal Use only in ServiceWiring
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'DescriptionRemoveElements',
	];

	/** @var string[] */
	private array $toRemove;

	public function __construct( ServiceOptions $options ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->toRemove = $options->get( 'DescriptionRemoveElements' );
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function derive( string $text ): ?string {
		$formatter = new class( $options = [], $this->toRemove ) extends HtmlFormatter {

			/** @var string[] */
			private array $toRemove;

			public function __construct( $options, array $toRemove ) {
				parent::__construct( $options );
				$this->toRemove = $toRemove;
			}

			public function comment( SerializerNode $parent, $text ) {
				return '';
			}

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

					return '';
				}

				return $contents;
			}

			public function startDocument( $fragmentNamespace, $fragmentName ) {
				return '';
			}
		};

		// Preserve only the first section
		if ( preg_match( '/^.*?(?=<h[1-6]\b(?! id="mw-toc-heading"))/s', $text, $matches ) ) {
			$text = $matches[0];
		}

		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $text );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );

		return trim( $serializer->getResult() );
	}
}

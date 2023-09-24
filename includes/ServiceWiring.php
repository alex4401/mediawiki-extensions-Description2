<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Description2\DescriptionProvider;
use MediaWiki\Extension\Description2\SimpleDescriptionProvider;
use MediaWiki\Extension\Description2\RemexDescriptionProvider;
use MediaWiki\MediaWikiServices;

return [
	DescriptionProvider::SERVICE_NAME => static function (
		MediaWikiServices $services
	): DescriptionProvider {
		if ( $services->getMainConfig()->get( 'UseSimpleDescriptionAlgorithm' ) ) {
			return new SimpleDescriptionProvider();
		}

		return new RemexDescriptionProvider(
			new ServiceOptions(
				RemexDescriptionProvider::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			)
		);
	},
];

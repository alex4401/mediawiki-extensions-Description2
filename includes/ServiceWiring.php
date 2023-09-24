<?php

use MediaWiki\Extension\Description2\DescriptionProvider;
use MediaWiki\Extension\Description2\SimpleDescriptionProvider;
use MediaWiki\Extension\Description2\RemexDescriptionProvider;
use MediaWiki\MediaWikiServices;

return [
    DescriptionProvider::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): DescriptionProvider {
        return new SimpleDescriptionProvider();
    },
];

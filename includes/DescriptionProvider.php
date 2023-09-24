<?php

namespace MediaWiki\Extension\Description2;

interface DescriptionProvider {

	public const SERVICE_NAME = 'Description2.DescriptionProvider';

	/**
	 * @param string $text
	 * @return ?string
	 */
	public function derive( string $text ): ?string;
}

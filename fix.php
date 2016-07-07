<?php
//error_reporting( E_ALL );
//ini_set( 'display_errors', 1 );

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;
use Wikibase\Api\WikibaseFactory;
use Mediawiki\DataModel\Revision;
use Wikibase\DataModel\ItemContent;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Entity\PropertyId;
use DataValues\StringValue;

// Load all of the things
require_once( __DIR__ . '/vendor/autoload.php' );
require_once( __DIR__ . '/config.inc.php' );

// Use the mediawiki api and Login
$api = new MediawikiApi( 'https://www.wikidata.org/w/api.php' );
$api->login( new ApiUser( $username, $password ) );

$services = new WikibaseFactory(
    $api,
    new DataValues\Deserializers\DataValueDeserializer(),
    new DataValues\Serializers\DataValueSerializer()
);

// Get specific services
$getter = $services->newRevisionGetter();
$remover = $services->newStatementRemover();

// Put list of items here
$itemList = array(
	'Q58480'
);

foreach ( $itemList as $item ) {
	$revision = $getter->getFromId( $item );
	$itemData = $revision->getContent()->getData();
	$statementList = $itemData->getStatements();
	$countryStatementList = $statementList->getByPropertyId( PropertyId::newFromNumber( 17 ) );

	// Remove existing country statements
	foreach ( $countryStatementList as $countryStatement ) {
		$remover->remove( $countryStatement );
		sleep( 1 );
	}

	// Create new statement country:novalue
	$services->newStatementCreator()->create(
			new PropertyNoValueSnak(
					PropertyId::newFromNumber( 17 )
			),
			$item
	);
	sleep( 1 );
}

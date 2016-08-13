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
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdValue;
use DataValues\StringValue;

// Load all of the things
require_once( __DIR__ . '/vendor/autoload.php' );
require_once( __DIR__ . '/config.inc.php' );

date_default_timezone_set( 'UTC' );

// Use the Wikidata API and Login
$api = new MediawikiApi( 'https://www.wikidata.org/w/api.php' );
$api->login( new ApiUser( $username, $password ) );

// Instantiate WikibaseFactory
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

	// Get all the country claims for this item (there should only be 1 per item)
	$statementList = $itemData->getStatements();
	$countryStatementList = $statementList->getByPropertyId( PropertyId::newFromNumber( 17 ) );

	// Change existing country statements to country:novalue
	foreach ( $countryStatementList as $countryStatement ) {
		$countryStatement->setMainSnak( new PropertyNoValueSnak( PropertyId::newFromNumber( 17 ) ) );
		sleep( 2 );
	}

	// Create new statement: located in the administrative territorial entity:Antarctic Treaty area
	$services->newStatementCreator()->create(
		new PropertyValueSnak(
			PropertyId::newFromNumber( 131 ),
			new EntityIdValue( new ItemId( 'Q21590062' ) )
		),
		$item
	);
	echo $item . "\n";
	sleep( 2 );
}

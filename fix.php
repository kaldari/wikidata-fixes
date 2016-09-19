<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;
use Mediawiki\DataModel\Revision;
use Mediawiki\DataModel\EditInfo;
use Wikibase\Api\WikibaseFactory;
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

// Set the bot flag for the edits
$editInfo = new EditInfo( '', false, true );

// Instantiate WikibaseFactory
$services = new WikibaseFactory(
    $api,
    new DataValues\Deserializers\DataValueDeserializer(),
    new DataValues\Serializers\DataValueSerializer()
);

// Get specific services
$getter = $services->newRevisionGetter();
$remover = $services->newStatementRemover();
$creator = $services->newStatementCreator();

// Put list of items here
$itemList = array(
	'Q1099916',
	'Q1099938',
	'Q1099945',
	'Q1099952',
	'Q1099960',
	'Q1099967',
	'Q1100051'
);

foreach ( $itemList as $item ) {
	$revision = $getter->getFromId( $item );
	$itemData = $revision->getContent()->getData();

	// Get all the country claims for this item
	$statementList = $itemData->getStatements();
	$countryStatementList = $statementList->getByPropertyId( PropertyId::newFromNumber( 17 ) );

	// Remove existing country statements
	foreach ( $countryStatementList as $countryStatement ) {
		$remover->remove(
			$countryStatement,
			$editInfo
		);
		sleep( 1 );
	}

	// Create new statement: country:novalue
	$creator->create(
		new PropertyNoValueSnak(
			PropertyId::newFromNumber( 17 )
		),
		$item,
		$editInfo
	);
	sleep( 2 );

	// Create new statement: located in the administrative territorial entity:Antarctic Treaty area
	if ( $statementList->getByPropertyId( PropertyId::newFromNumber( 131 ) )->isEmpty() ) {
		$creator->create(
			new PropertyValueSnak(
				PropertyId::newFromNumber( 131 ),
				new EntityIdValue( new ItemId( 'Q21590062' ) )
			),
			$item,
			$editInfo
		);
		echo $item . "\n";
		sleep( 2 );
	} else {
		echo "Has an existing 131 claim\n";
	}
}

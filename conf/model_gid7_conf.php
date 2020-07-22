<?php
	# Schemas of AAA
	$schemas = array();
	$schemas[] = 'aaa';
	$schemas[] = 'aaa1';
	$schemas[] = 'aaa2';
	$schemas[] = 'aaa_test';
	define('SCHEMAS', "'" . implode("';'", $schemas) . "'");

	$packages[] = 'NAS';
	$packages[] = 'AAA Basisschema';
	$packages[] = 'AAA_Nutzerprofile';
	$packages[] = 'AAA_Praesentationsobjekte 3D';
	$packages[] = 'Codelisten';
	$packages[] = 'AAA_Basisklassen';
	$packages[] = 'AAA_GemeinsameGeometrie';
	$packages[] = 'AAA_Operationen';
	$packages[] = 'AAA_Praesentationsobjekte';
	$packages[] = 'AAA_Projektsteuerung';
	$packages[] = 'AAA_Punktmengenobjekte';
	$packages[] = 'AAA_Spatial Schema';
	$packages[] = 'AAA_Unabhaengige Geometrie';
	$packages[] = 'AAA_Unabhaengige Geometrie 3D';
	$packages[] = 'AFIS-ALKIS-ATKIS Fachschema';
	$packages[] = 'Bauwerke, Einrichtungen und sonstige Angaben';
	$packages[] = 'Besondere Angaben zum Verkehr';
	$packages[] = 'Besondere Vegetationsmerkmale';
	$packages[] = 'Personen- und Bestandsdaten';
	$packages[] = 'Angaben zu Festpunkten der Landesvermessung';
	$packages[] = 'Angaben zum Netzpunkt';
	$packages[] = 'Angaben zur Historie';
	$packages[] = 'Angaben zur Lage';
	$packages[] = 'Angaben zur Reservierung';
	$packages[] = 'Angaben zum Punktort';
	$packages[] = 'Bauwerke, Einrichtungen, Anlagen 3D';
	$packages[] = 'Gestaltung 3D';
	$packages[] = 'Gesetzliche Festlegungen, Gebietseinheiten, Kataloge';
	$packages[] = 'Administrative Gebietseinheiten';
	$packages[] = 'Geographische Gebietseinheiten';
	$packages[] = 'Kataloge';
	$packages[] = 'Migration';
	$packages[] = 'Migrationsobjekte';
	$packages[] = 'Mitteilungsdaten';
	$packages[] = 'Grundbuch';
	$packages[] = 'Nutzerprofile';
	$packages[] = 'Angaben zu Nutzerprofilen';
	$packages[] = 'Relief';
	$packages[] = 'Reliefformen';
	$packages[] = 'Messdaten 3D';
	$packages[] = 'Siedlung';
	$packages[] = 'Vegetation';
	$packages[] = 'Verkehr';
	$packages[] = 'NAS-Operationen';
	$packages[] = 'AFIS-ALKIS-ATKIS-Bestandsdatenausgaben';
	$packages[] = 'NAS-AK';
	$packages[] = 'AFIS-Einzelpunktnachweise';
	$packages[] = 'AFIS-Punktlisten';
	$packages[] = 'ALKIS-Ausgaben';
	$packages[] = 'ALKIS-Auswertungen';
	$packages[] = 'Angaben im Kopf der Ausgaben';
	$packages[] = 'Externe Datentypen';
	$packages[] = 'Personen- und Bestandsangaben';
	$packages[] = 'Punktangaben';
	$packages[] = 'Reservierungen';
	$packages[] = 'NAS-GV';
	$packages[] = 'NAS-LB';
	$packages[] = 'Vegetationslos';
	$packages[] = 'Bebauung';
	$packages[] = 'Vegetation';
	$packages[] = 'Wasser';
	$packages[] = 'NAS-LN';
	$packages[] = 'Gewaesser';
	$packages[] = 'Land-, Forst- und Fischereiwirtschaft';
	$packages[] = 'Siedlung';
	$packages[] = 'Verkehr und Infrastruktur';
	# Packages of AAA Schema

	#if ($packages) define('PACKAGES', "'" . implode("';'", $packages) . "'");
?>

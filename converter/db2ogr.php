<?php
// +----------------------------------------------------------------------+
// | db2ogr.php                                                           |
// | Creating DB-Schema from UML-Model for ogr2ogr imports                |
// +----------------------------------------------------------------------+
// | Author: Peter Korduan <peter.korduan@gdi-service.de>                 |
// | Licence: GPL https://www.gnu.org/licenses/gpl-3.0.de.html            |
// +----------------------------------------------------------------------+
	include('../conf/database_conf.php');
	include(SCHEMA_CONF_FILE);
	include('../classes/logger.php');
	include('../classes/databaseobject.php');
	include('../classes/schema.php');
	include('../classes/ogrschema.php');
	include('../classes/table.php');
	include('../classes/attribute.php');
	include('../classes/data.php');
	include('../classes/datatype.php');
	include('../classes/enumtype.php');
	include('../classes/associationend.php');
	include('../classes/featuretype.php');

	$tabNameAssoc = array();
	$log_sql = '';
	$logger = new Logger(LOGLEVEL);
	$indent = 0;
	if (file_exists(FILTER_FILE)) {
		$filter = json_decode(file_get_contents(FILTER_FILE), true);
	}
	else {
		$logger->log("Filterdatei " . FILTER_FILE . " nicht gefunden.");
		$filter = array();
	}

echo '<!DOCTYPE html>
<html lang="de">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	</head>
	<body>';
	#*****************************************************************************
	# 
	#*****************************************************************************

	# Initialize the umlSchema object
	$umlSchema = new Schema(UML_SCHEMA, $logger);
	$umlSchema->openConnection(PG_HOST, PG_DBNAME, PG_USER, PG_PASSWORD);
	$umlSchema->logger->debug = true;

	# Initialize the gmlSchema object
	$ogrSchema = new OgrSchema(OGR_SCHEMA, $logger);
	$ogrSchema->umlSchema = $umlSchema;
	$sql = $ogrSchema->asSql();

	#**************
	# Enumerations
	#**************
	# Erzeuge Enummerations
	foreach($umlSchema->getEnumerations() AS $enumeration) {
		$sql .= $ogrSchema->createEnumerationTable($enumeration, $ogrSchema);
	}

	$logger->log('<br><hr><br>');

	if (WITH_CODE_LISTS) {
		#***********
		# CodeLists
		#***********
		# Lade CodeLists
		foreach($umlSchema->getCodeLists() AS $code_list) {
			$sql .= $umlSchema->createCodeListTable($code_list);
		}
		$logger->log('<br><hr><br>');
	}

  $umlSchema->iso19139SubstitutionClasses = $umlSchema->getIso19139SubstitutionClasses();

	#**************
	# FeatureTypes
	#**************
	# Lade oberste Klassen vom Typ FeatureType, die von keinen anderen abgeleitet wurden
	$topClasses = $umlSchema->getTopUmlClasses('FeatureType');
	
	# Für alle oberen Klassen
	foreach($topClasses as $topClass) {
		$ogrSchema->logger->log('<br><b>TopKlasse: ' . $topClass['name'] . '</b> (' . $topClass['xmi_id'] . ')');
		$sql .= $ogrSchema->createFeatureTypeTables('FeatureType', null, $topClass);
	}
	$logger->log('<br><hr><br>');
	$sql .= "\n\n" . $ogrSchema->create_delete_trigger();

	$logger->log('<br><hr><br>');
	$sql .= "\n\n" . $ogrSchema->create_ax_fortfuehrungsauftrag();

#	$gmlSchema->execSql($sql);

	if (WITH_CODE_LISTS) {
		# Gebe values für CodeLists aus
		$sql .= "\n\n" . file_get_contents('../sql/Codelisten/aa_anlassart.sql');
	}

?><pre><?php
	echo htmlspecialchars($sql);
?></pre>
<?php
echo '	</body>
</html>';
?>
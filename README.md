# xmi2db
Konvertiert UML Klassenmodelle aus XMI Dateien in Tabellen und Typen nach PostgreSQL.

Version vom 09.11.2016 12:35

Abstract
XMI files are XML representations of UML models. Sometimes there is a need to have theses UML model elements especially the classes its attributes, the assosiations and generalisations in a database table structure.
xmi2db read an XMI file and write the UML Things into a database structure, which is oriented on the UML Structure. There are tables fore uml_classes, uml_attributes, class_generalizations, datatypes, stereotypes etc.
In a second step the function db2classes produce a relational database schema which have separate tables for each class and attributes in accordance to the type defined in UML. The conversion from UML datatyps into types of the data base can be easy adapted. The definition of tables consider the generalization of UML classes and uses inheritance. Multiplicity are accounted by defining array types of attribute. The assosiations will be realised by autogenerated gml_id attributes of type uuid for each superclass table. The function db2ogr producee a relational database schema without complexe datatypes. These schema can be used to import GML-Files with ogr2ogr.
Our xmi2db converter focus on UML models of GML application schemas used eg. for [INSPIRE](http://inspire.ec.europa.eu/data-model/approved/r4618-ir/html/) data specifications or German application schemas of the [AAA project](http://www.adv-online.de/AAA-Modell/) or [XPlanung](http://www.iai.fzk.de/www-extern/index.php?id=679). Therefore type UNION will be converted to a geometry type and the PostGIS extension is neccesary in our database model.
We use it especially to create database models derived from the currently frequently changing XPlanung UML model to be able to store and manage XPlanung features in a PostgreSQL database.

Zusammenfassung

XMI Dateien sind XML-Repräsentationen von UML-Modellen. Manchmal braucht man die UML Modellelemente, besonders die Klassen, seine Attribute, die Assoziationen und Generalisierungen, in einer Datenbank-Tabellenstruktur.
xmi2db liest eine XMI Datei und schreibt die UML Dinge in die Datenbankstrktur, welche sich an UML Strukturen orientiert. Es gibt Tabellen für uml_classes, uml_attributes, class_generalizations, datatypes, stereotypes usw.
In einem zweiten Schritt kann mit der Funktion db2classes ein relationales Datenbankschema erzeugt werden, welches für jede einzelne Klasse eine separate Tabelle erzeugt, mit Attributen, die zur Klasse passen. Die Tabellendefinition berücksichtigt die Generalisierung von UML-Klassen und die Vererbung. Multiplizität wird durch Definition der Attribute als Arrays berücksichtigt. Die Assoziationen werden verbunden durch gml_id Attribute vom Typ uuid. Die Funktion db2ogr erzeugt ein relationales Databankmodell ohne komplexe Datentypen. Dieses Schema kann für den Import von GML-Dateien mit ogr2ogr verwendet werden.
Der xmi2db converter fokusiert sich auf GML-Anwendungsschemas wie die für INSPIRE, das AAA-Modell oder XPlanung-Schema. Der Type UNION wird in geometry umgesetzt und die PostGIS Erweiterung für die Datenbank ist erforderlich.

db2classes wird für einen Shape-to-XPlanGML-Konverter für XPlanung konforme Raumordnungspläne verwendet wird.

db2ogr wird für das PostNAS Projekt verwendet um alle Modellelemente der NAS-Schnittstelle mit ogr2ogr in Postgres einlesen zu können.

Installation

* Clone das Projekt in das eigene Verzeichnis.
```
git clone https://github.com/pkorduan/xmi2db.git
```
* Erzeuge und editiere die Datei database_config.php
```
cp conf/database_conf_sample.php conf/database_conf.php
```
* Passe Datenbankzugang an: PG_HOST, PG_USER, PG_PASSWORD, PG_DBNAME

* Erzeuge eine Datenbank, die $PG_USER gehört und installiere die Erweiterung PostGIS

* Lege die zu importierende XMI-Datei im Unterordner xmis ab.

* Öffne den Link um auf die Konvertierungsoberfläche zu kommen. http://yourserver.de/xmi2db/

xmi2db

Führe als erstes die Funktion xmi2db "Fülle DB mit XMI Inhalten" aus um die UML-Elemente nach Postgres einzulesen.
Die Funktion erzeugt das Datenbankschema zur Speicherung der UML-Elemente und liest alle Klassen, Attribute, Beziehungen, Generalisierungen und Assoziationen aus der XMI-Datei aus und trägt sie in Tabellen ein.
* Wähle die XMI Datei aus dem Ordner xmis aus,
* Wähle ein Schemaname, z.b. aaa_uml
* Wähle den Namen des Basis Paketes des UML models aus.
* Klick auf "Fülle DB mit XMI Inhalten"
Die Schemanamen können auch in conf/database_config.php vordefiniert werden.

db2classes

Diese Funktion erzeugt den SQL-Code eines Datenbankschemas, welches für jede UML-Klasse eine Tabelle hat, für jeden Datentyp einen Postgrestyp und für jede Aufzählung einen Enumerationstyp und je eine Schlüsseltabelle für eine Enumeration und eine Codeliste.
* Wähle den Namen des Schemas aus in das die XMI eingelesen wurde.
* Wähle den Namen des Ausgabeschemas aus.

db2ogr

Diese Funktion erzeugt den SQL-Code eines flachen Datenbankschemas, welches für jede UML-Klasse eine Tabelle hat. Die Attribute sind jedoch nicht mit komplexen Datentypen versehen, sondern die Attribute der Datentypen sind als Attribute der Tabelle übernommen. Um doppelte Namen zu vermeiden werden einige Attribute umbenannt. Eine Liste der Umbenennungen kann mit der URL http://yourserver.de/xmi2db/listings/umbenennungsliste.php erzeugt werden.
* Wähle den Namen des Schemas aus in das die XMI eingelesen wurde.
* Wähle den Namen des Ausgabeschemas aus.

Um einen tieferen Einblick zu erhalten was alles abgefragt wird um die Schmata zu erzeugen kann der Parameter loglevel=1 mit angegeben werden. z.b.

```
http://meinserver.de/xmi2db/converter/db2classes.php?umlSchema=aaa_uml&gmlSchema=aaa_gml&loglevel=1
```

Umbenennungsskript

Um NAS-Dateien in das neue flache Schema, welches bei db2ogr herauskommt einlesen zu können, müssen einige XML-Elemente umbenannt werden. Dazu wurde das Ruby-Program rename_nas.rb geschrieben, welches sich im Verzeichnis converter befindet. Die Ausführung unter Debian erfordert die Installation von ruby_libxml.

```
apt-get updated && apt-get install libxml-ruby
```

In der Datei rename_nas.rb steht der Pfad auf die Umbenennungsdatei. Der Pfad ist relativ zum Verzeichnis converter angegeben in dem das Skript rename_nas.rb liegt. Wenn die Umbenennungsdatei da bleibt, wo sie standardmäßig liegt, passt der angegebenen Pfad.

Die Umbenennung von Elementen in einer NAS-Datei "eingabedatei.xml" wird wie folgt aufgerufen:
```
ruby rename_nas.rb eingabedatei.xml [ausgabedatei.xml]
```

Filter

Das Schema, welches mit db2ogr erzeugt wird, kann durch einen Filter beschränkt werden. Dazu dient eine Filterdatei im JSON Format, dessen Name in conf/database_conf.php im Parameter FILTER_FILE eingestellt werden kann. Die Beispieldatei conf/filter_sample.json enthält folgende Filter.
```
{
	"AA_Modellart": {
		"attribute": {
			"sonstigesModell": 0
		}
	},
	"AA_Objekt": {
		"beziehungen": {
			"istTeilVon": 0
		}
	},
	"AX_Netzknoten": 0,
	"AX_Bauwerksfunktion_Leitung": 0
}
```
Im Element AA_Modellart wird das Attribut sonstigesModell ausgeschlossen. Im Element AA_Objekt wird die Beziehung istTeilVon ausgeschlossen. Zusätzlich wird das Elemente AX_Netzknoten und die Aufzählungsklasse AX_Bauwerksfunktion_Leitung vollständig weggelassen. Es können mehrere Attribute und Beziehungen getrennt durch Komma angegeben werden. Die Zahl hinter dem : hat noch nichts zu sagen und sollte mit 0 angegeben werden.

Schritt für Schritt Anleitung zum Einlesen von NAS-Dateien in PostgreSQL

1. Vorbereitungen

Zum Einlesen von NAS-Dateien in Postgres benötigt man in der Datenbank ein aufbereitet Schema im folgenden "aaa_ogr" genannt und eine aufbereitete NAS-Datei im folgenden "renamed_nas.xml" genannt.

1.1 Erstellung des Schemas "aaa_ogr"

Ein vollständiges Schema kann unter
http://gdi-service.de/xmi2db/converter/db2ogr
heruntergeladen werden, z.B. in der Datei aaa_ogr_schema.sql ablegen.

Länderspezifische Schemata lassen sich mit dem Zusatz filter= mv,rp oder sl erzeugen. z.B.
http://gdi-service.de/xmi2db/converter/db2ogr?filter=mv. Siehe Punkt "Filter" oben, um zu erfahren was gefiltert wird und wie er funktioniert.

Den SQL-Text in aaa_ogr_schema.sql in einer Datenbank in einem SQL-Client ausführen z.B. pgAdmin3 oder psql ausführen. Die Befehle zum Anlegen der Datenbank lauten wie folgt:

```
CREATE DATABASE "mypgdatabase";
CREATE EXTENSION postgis;
```

Befehl zum Ausführen der SQL-Datei aaa_ogr_schema.sql im Console-Client psql:

```
psql -U mydbuser -f aaa_ogr_schema.sql mydbname -
```

siehe psql --help für mehr Informationen.

1.2 NAS-Datei aufbereiten

Jede NAS-Datei, die in das Schema aaa_ogr eingelesen werden soll, muss voher mit dem Script "rename_nas.rb" aufbereitet werden. Zur Installation von ruby und der Ausführung des Skripts sieht oben Punkt "Umbenennungsskript".

2. Einlesen

2.1 Eine einzelne NAS-Datei einlesen
Eine einzelne aufbereitete NAS-Datei "renamed_nas.xml" wird wie folgt mit ogr2ogr in das Schema "aaa_ogr" eingelesen.

```
ogr2ogr -f "PostgreSQL" --config PG_USE_COPY NO -nlt CONVERT_TO_LINEAR -append PG:"dbname=mydbname active_schema=aaa_ogr user=mydbuser host=myhost port=5432" -a_srs EPSG:25833 renamed_nas.xml
```
Im Osten Deutschlands wie Mecklenburg-Vorpommern nutze 25833 sonst 25832
Siehe http://gdal.org/ogr für mehr Informationen zur Benutzung von ogr2ogr

3. Automatisierung des Einlesens von Massendaten

NAS-Dateien, die im nutzer- oder stichtagsbezogenem Abgabeverfahren (NBA) von AAA-Softwaresystemen erzeugt werden, liegen in der Regel in Form von gepackten und komprimierten Archiven vor, z.B. NBA_Grundausstattung_2015-02-11.zip Unter Linux lassen sich solche Archive wie folgt entpacken:

```
unzip NBA_Grundausstattung_2015-02-11.zip
```
Es entstehen viele Dateien z.B.
```
NBA_Grundausstattung_001_081_2015-02-11.xml.gz
NBA_Grundausstattung_002_081_2015-02-11.xml.gz
...
NBA_Grundausstattung_081_081_2015-02-11.xml.gz
```
Diese Dateien wiederum lassen sich wie folgt entpacken und in einer Schleife verarbeiten.

```
gunzip *.xml.gz
for NAS_FILE in *.xml
do
  ruby rename_nas.rb $NAS_FILE renamed_nas.xml
  ogr2ogr -f "PostgreSQL" --config PG_USE_COPY NO -nlt CONVERT_TO_LINEAR -append PG:"dbname=mydbname active_schema=aaa_ogr user=mydbuser host=myhost port=5432" -a_srs EPSG:25833 renamed_nas.xml
done
```

In der Schleife der Abarbeitung ist jedoch noch zu berücksichtigen, dass die erste Datei Metadaten enthält und ignoriert werden kann und Fehler abgefangen werden müssen.

Ein Vorschlag für ein Bash-Skript für Linux, welches die Metadaten und Fehlerbehandlung berücksichtigt in Log-Dateien protokolliert und abgearbeitete Dateien in einen Archivordner schreibt, findet sich in der Datei tools/import_nas.sh Die Datei muss im Modus "Ausführbar" sein

```
chmod a+x import_nas.sh
```
Passen Sie vor dem Ausführen der Datei mit

```
./import_nas.sh
```
die folgenden Parameter an.
```
DATA_PATH="/pfad/zu/den/nas/dateien"
OGR_PATH="/pfad/zu/ogr2ogr/bin/verzeichnis"
ERROR_LOG=/pfad/fuer/logfiles/mylogfile.log
```

Diese Anwendung wurde 2016 entwickelt von Peter Korduan und Christian Seip.

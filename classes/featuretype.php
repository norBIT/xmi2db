<?php
class FeatureType {

  function __construct($name, $parent, $logger, $umlSchema, $enumerations = NULL) {
    $this->alias = $name;
    $this->name = $this->getName($name);
    $this->comments = array();
    if ($this->name != $this->alias)
      $this->comments[] = 'FeatureType: "' . $this->alias . '"';
    $this->attributes = array();
    $this->attributes_until_leafs = array();
    $this->associationEnds = array();
    $this->primaryKey = '';
    $this->primaryKeyType = 'text';
    $this->parent = $parent;
    $this->withOids = PG_WITH_OIDS;
    $this->values = new Data();
    $this->umlSchema = $umlSchema;
    $this->logger = $logger;
    $this->stereotype = 'featuretype';
    $this->attribute_filter = array();
    $this->enumerations = $enumerations;
  }

  public static function getName($name) {
    return strtolower(substr($name, 0, PG_MAX_NAME_LENGTH));
  }

  function setId($id) {
    $this->id = $id;
  }

  function addAttribute($attribute) {
    $this->attributes[] = $attribute;
  }

  function createUserInfoColumns() {
    $this->logger->log('<br><b>Erzeuge userinfo Spalten</b>');
    $this->addAttribute(new Attribute('user_id', 'integer'));
    $this->addAttribute(new Attribute('created_at', 'timestamp without time zone','',array(), true, 'current_timestamp'));
    $this->addAttribute(new Attribute('updated_at', 'timestamp without time zone','',array(), true, 'current_timestamp'));
    $this->addAttribute(new Attribute('konvertierung_id', 'integer'));
  }

  function setAssociationEnds($class) {
    # lade navigierbare Assoziationsenden von 1:n Assoziationen
    foreach($this->umlSchema->getAssociationEnds($class) AS $end) {
      if (!$this->is_filtered('beziehungen', $class['name'], $end['b_name'])) {
        $associationEnd = new AssociationEnd(
          $end['b_name'],
          $end['a_class_name'],
          $end['b_class_name'],
          $this->logger
        );
        $associationEnd->stereotype = 'FeatureType';
        $associationEnd->setMultiplicity($end['b_multiplicity_range_lower'], $end['b_multiplicity_range_upper']);
        $this->addAssociationEnd($associationEnd);
      }
    }
    $this->logger->log($this->associationsAsTable());
  }

  function is_filtered($attribute_type, $class_name, $attribute_name) {
    $is_filtered = (
      array_key_exists($class_name, $GLOBALS['filter']) and (
        $GLOBALS['filter'][$class_name] == 0 or (
          array_key_exists($attribute_type, $GLOBALS['filter'][$class_name]) and
          array_key_exists($attribute_name, $GLOBALS['filter'][$class_name][$attribute_type])
        )
      )
    );

    if ($is_filtered) {
      $this->logger->log("<br>Ignoriere Klasse: {$class_name} Attribute: {$attribute_name}");
    }
    return $is_filtered;
  }

  function getAttributesUntilLeafs($type, $stereotype, $parts) {
    $return_attributes = array();
    if (in_array(substr($type, 0, 3), array('DQ_', 'LI_', 'CI_'))) {
      /* Damit die DQ_, LI_ und CI_ Elemente gefunden werden, mussen Sie in classes existieren.
      * Zum Anlegen kann das SQL-Script sql/external_uml_classes.sql verwendet werden.
      */
      $attributes = $this->umlSchema->getExternalClassAttributes($type, $stereotype, $parts);
    }
    else {
      $attributes = $this->umlSchema->getClassAttributes($type);
    }
    foreach ($attributes AS $attribute) {
      $this->logger->log("<br><b>Class:</b> {$attribute['class_name']} <b>Attribut:</b> {$attribute['attribute_name']} <b>datatype:</b> {$attribute['attribute_datatype']} <b>stereotype:</b> {$attribute['attribute_stereotype']}");
      if (!$this->is_filtered('attribute', $type, $attribute['attribute_name'])) {
        if (!empty($attribute['attribute_name'])) {
          if (empty($parts)) {
            $parent = $this;
          }
          else {
            #if ($attribute['class_name'] == 'AA_Modellart') echo '<br>  class: ' . $attribute['class_name'] . ' class stereotype: ' . $stereotype . ' attribute name: ' . $attribute['attribute_name'] . ' datatype: ' . $attribute['attribute_datatype'] . ' stereotyp: ' . $attribute['attribute_stereotype'];

            $parent = new Datatype($attribute['class_name'], $stereotype, $this->logger, $this->enumerations);
            $parent->ogrSchema = $this->ogrSchema;
          }

          $attributeObj = new Attribute(
            ($attribute['attribute_name'] == 'position' and GEOMETRY_COLUMN_NAME != '') ? GEOMETRY_COLUMN_NAME : $attribute['attribute_name'],
            $attribute['attribute_datatype'],
            $parent,
            $parts
          );
          $attributeObj->setStereoType($attribute['attribute_stereotype']);
          # Union Typen können nicht not null sein, weil immer eines der Attribute
          # des Union Types verwendet wird. Also andere weggelassen werden.
          # Sonderfall, wenn ein Union Typ nur ein Attribut hätte, aber das wäre
          # für einen Union Typ sinnlos, weil er ja dafür da ist, dass man ein
          # Attribut aus verschiedenen auswählt.
          #if ($type == 'AX_Lagebezeichnung') echo '<br>typ: ' . $type . ' attribute: ' . $attributeObj->name . ' stereotype: ' . $stereotype;
          
          $attributeObj->setMultiplicity(
            (($attribute['attribute_name'] == 'position' or $stereotype == 'union') ? 0 : $attribute['multiplicity_range_lower']),
            $attribute['multiplicity_range_upper']
          );

          $msg = $attributeObj->overwriteIso19139Type($this->umlSchema->iso19139SubstitutionClasses, $parts);
          if ($msg != '') {
            $this->logger->log($msg);
          }

          $new_path = $parts;
          array_push($new_path, $attributeObj);
          if (in_array(strtolower($attribute['attribute_stereotype']), array('datatype', 'union'))) {
            foreach ($this->getAttributesUntilLeafs($attribute['attribute_datatype'], $attribute['attribute_stereotype'], $new_path) AS $child_attribute) {
              $return_attributes[] = $child_attribute;
            }
          }
          else {
            $return_attributes[] = $new_path;
          }
        }
      }
    }
    $this->attributes_until_leafs = $return_attributes;
    return $return_attributes;
  }

  function flattenAttributes() {
    if ($this->parent != null AND !empty($this->parent->attributes)) {
      foreach($this->parent->attributes AS $parent_attribute) {
        $parent_attribute->parts[0]->parent->alias = $this->alias;
        $parent_attribute->setNameFromParts();
        $this->attributes[] = $parent_attribute;
      }
    }
    foreach($this->attributes_until_leafs AS $attribute_parts) {
      $attribute = end($attribute_parts);
      $attribute->parts = $attribute_parts;
      $attribute->setNameFromParts();
      $this->attributes[] = $attribute;
    }
  }

  function getParentsAttributes() {
    if ($this->parent == null)
      return array();
    else
      return array_merge(
        $this->parent->attributes,
        $this->parent->getParentsAttributes()
      );
  }

  function getParentsAssociationEnds() {
    if ($this->parent == null)
      return array();
    else
      return array_merge(
        $this->parent->associationEnds,
        $this->parent->getParentsAssociationEnds()
      );
  }

  function unifyShortNames($level) {
    $this->logger->log('<br><b>unifyShortNames:</b>');
    $multiple_occured = false;
    foreach($this->attributes AS $a) {
      $frequency = 0;
      foreach($this->attributes AS $b) {
        if ($a->short_name == $b->short_name) {
          $frequency++;
        }
      }
      $a->frequency = $frequency;
      if ($frequency > 1) {
        $multiple_occured = true;
      }
    }
    if ($multiple_occured) {
      $this->logger->log('<br>gleichlautende Namen gefunden in Runde ' . $level . ' der Umbenennung!');
      foreach($this->attributes AS $a) {
        $n = count($a->parts) - $level - 1; # Stufe der Klasse im Pfad
        if ($a->frequency > 1 AND $n < 0) {
          $this->logger->log('<br>' . $a->path_name . ' (nicht umbenannt)');
        }
        if ($a->frequency > 1 AND $n > -1) {
          $this->logger->log('<br>' . $a->path_name);
          $this->logger->log('<br>' . $a->short_name);
          $a->short_name = $a->parts[$n]->name . '_' . $a->short_name;
          $this->logger->log(' => ' . $a->short_name . ' (kam ' . $a->frequency . ' mal vor)');
        }
      }
      if ($level > 10) {
        $this->logger->log('<br>Abbruch bei level: ' . $level . ' weil Umbenennung nicht möglich.');
      }
      else {
        $level += 1;
        $this->unifyShortNames($level);
      }
    }
    else {
      $this->logger->log('<br>keine gleichlautenden Namen gefunden!');
    }
  }

  function unifyShortNamesWithFirst($level) {
    $multiple_occured = false;
    foreach($this->attributes AS $a) {
      $frequency = 0;
      foreach($this->attributes AS $b) {
        if ($a->short_name == $b->short_name) {
          $frequency++;
        }
      }
      $a->frequency = $frequency;
      if ($frequency > 1) {
        $multiple_occured = true;
      }
    }
    if ($multiple_occured AND $level < 10) {
      foreach($this->attributes AS $a) {
        $n = count($a->parts) - $level - 1;
        if ($a->frequency > 1 AND $n > -1) {
          $this->logger->log('<br>Attribut: ' . $a->short_name);
          $this->logger->log('<br>level: ' . $level . ' path' . $a->path_name);
          if ($level == 1) {
            $a->short_name = $a->parts[0]->name . '_' . $a->short_name;
          }
          else {
            $a->short_name = $a->parts[$n]->name . '_' . $a->short_name;
          }
          $this->logger->log(' umbenannt nach: ' . $a->short_name);
        }
      }
      
      $this->unifyShortNames($level++);
    }
  }

  function getFlattenedName() {
    $n = count($this->attribute_names);
    $return_name = $this->attribute_names[0]->name;
    if ($n > 2) # füge den vorletzen hinzu wenn es mehr als zwei Namesteile sind
      $return_name .= '_' . $this->attribute_names[$n-2]->name;
    if ($n > 1) # füge den letzten hinzu wenn es mehr als einer ist
      $return_name .= '_' . $this->attribute_names[$n-1]->name;
    return $return_name;
  }

  function getKeys() {
    return array_map(
      function($attribute) {
        return $attribute['name'];
      },
      $this->attributes
    );
  }

  function attributesAsTable() {
    if (empty($this->attributes)) {
      $html = '<br>Keine Attribute gefunden.';
    }
    else {
      $html = '<table border="1"><tr><th>Attribut</th><th>Attributtyp</th><th>Stereotyp</th><th>Datentyp</th><th>Multiplizität</th><th>Default</th><th>Sequence</th></tr>';
      # für jedes Attribut erzeuge Attributzeilen
      foreach($this->attributes AS $i => $attribute) {
        $html .= '<tr><td>' . $attribute->name . '</td><td>' .
                $attribute->attribute_type . '</td><td>' .
                $attribute->stereotype . '</td><td>' .
                $attribute->datatype . '</td><td>' .
                $attribute->multiplicity . '</td><td>' .
                $attribute->default . '</td><td>' .
                $attribute->sequence_number . '</td></tr>';
        $sql .= '
    ';
      }
      $html .= '</table><p>';
    }
    return $html;
  }

  function associationsAsTable() {
    if (empty($this->associationEnds)) {
      $html = '<br>Keine Assoziationen gefunden.';
    }
    else {
      $html = '<table border="1"><tr><th>Attribut</th><th>Stereotyp</th><th>Datentyp</th><th>Multiplizität</th></tr>';
      # für jedes Attribut erzeuge Attributzeilen
      foreach($this->associationEnds AS $i => $associationEnd) {
        $html .= '<tr><td>' . $associationEnd->name . '</td><td>' .
                $associationEnd->stereotype . '</td><td>' .
                $associationEnd->zeigt_auf_alias . '</td><td>' .
                $associationEnd->multiplicity . '</td>' .
        $sql .= '</tr>
    ';
      }
      $html .= '</table><p>';
    }
    return $html;
  }

  function addAssociationEnd($associationEnd) {
    $this->associationEnds[] = $associationEnd;
  }

  function addComment($comment) {
    $this->comments[] = $comment;
  }

  function outputFlattendedAttributTable() {
    $html = '';
    if (empty($this->attributes))
      $html .= '<br>keine Attribute';
    else {
      $html .= '<table>
        <th>Pfad</th><th>Name</th><th>Kurzname</th><th>Stereotype</th><th>UML-Datatype</th><th>Databasetype</th><th>Multipliziät</th>';
        $num_attributes = 0;
        foreach ($this->attributes AS $attribute) {
          $html .= '<tr>';
          $html .=  '<td>' . $attribute->path_name . '</td>';
          $html .=  '<td>';
          $html .=  $attribute->attributes_name;
          if (strlen($attribute->attributes_name) > 58)
            $html .=  '(*)';
          $html .=  '</td>';
          $html .=  '<td>';
          $html .=  $attribute->short_name;
          $html .=  '</td>';
          $html .=  '<td>';
          $html .=  $attribute->stereotype;
          $html .=  '</td>';
          $html .=  '<td>';
          $html .=  $attribute->datatype;
          $html .=  '</td>';
          $html .=  '<td>';
          $html .=  $attribute->get_database_type(false, false);
          $html .=  '</td>';
          $html .=  '<td>';
          $html .=  $attribute->multiplicity;
          $html .=  '</td>';
          $html .=  '</tr>';
        }
      $html .= '</table>';
    }
    return $html;
  }

  function outputFlattenedAttributes() {
    $this->logger->log('<br><b>Attribute mit Pfad:</b>');
    $output = array();
    if (!empty($this->attributes)) {
      foreach ($this->attributes AS $attribute) {
        $this->logger->log('<br>Attribut Pfad: ' . $attribute->path_name . ' (' . $attribute->short_name . ')');
        if (RENAME_ZEIGT_AUF_EXTERNES) {
          $zeigt_auf_externes_pos = strpos($attribute->path_name, 'zeigtAufExternes');
          if ($zeigt_auf_externes_pos !== false) {
            $zeigt_auf_externes_path_name = substr($attribute->path_name, 0, $zeigt_auf_externes_pos + 16);
            if (!array_key_exists($zeigt_auf_externes_path_name, $output)) {
              $output[$zeigt_auf_externes_path_name] = 'zeigtaufexternes_';
            }
          }
        }
        if ($attribute->short_name != end($attribute->parts)->name) {
          if ($attribute->overwrite['type'] != '') {
            $key = $attribute->overwrite['path_name'];
          }
          else {
            $key = $attribute->path_name;
          }
          $output[$key] = $attribute->short_name;
        }
      }
    }
    return $output;
  }

  function hasGeometryColumn() {
    $hasGeometryColumn = false;
    foreach($this->attributes AS $attribute) {
      if ($attribute->name == GEOMETRY_COLUMN_NAME) {
        $hasGeometryColumn = true;
      }
    }
    return $hasGeometryColumn;
  }
	
  function getGeometryType() {
		$geometry_type = 100;
    foreach($this->attributes AS $attribute) {
      if($attribute->name == GEOMETRY_COLUMN_NAME){
        $geometry_type = $attribute->get_gfs_type($attribute->get_database_type(false, false), NULL);
      }
    }
    return $geometry_type;
  }	

  function asSql() {
    $attribute_parts = array();
    $sql = "
" . PG_CREATE_TABLE . " " . $this->name . " (
";

    # Ausgabe id
    if ($this->parent == null) {
      $part .= "\t" . $this->primaryKey;
      if (WITH_UUID_OSSP) {
        $part .= " uuid NOT NULL DEFAULT uuid_generate_v1mc()";
      }
      else {
        $part .= " " . $this->primaryKeyType;
      }
      $attribute_parts[] = $part;
    }

    # Ausgabe Attribute
    $attribute_parts = array_merge(
      $attribute_parts,
      array_map(
        function($attribute) {
          return $attribute->asSql('table');
        },
        $this->attributes
      )
    );

    # Ausgabe Assoziationsenden
    $attribute_parts = array_merge(
      $attribute_parts,
      array_map(
        function($associationsEnd) {
          return $associationsEnd->asSql('table');
        },
        $this->associationEnds
      )
    );

    # Ausgabe Primary Key
    if ($this->primaryKey != '')
      $attribute_parts[] = "CONSTRAINT " . $this->name . '_pkey PRIMARY KEY (' . $this->primaryKey . ')';

    # Zusammenfügen der Attributteile
    $sql .= implode(",\n", $attribute_parts);

    $sql .= '
)';

    # Ausgabe Vererbung
    if ($this->parent != null)
      $sql .= ' INHERITS (' . $this->parent->name . ')';

    # Ausgabe WITH OIDS
    if ($this->withOids)
      $sql .= ' WITH OIDS';

    $sql .= ';
';  # Tabellenende

    # Set epsg code
    if (!empty(GEOMETRY_EPSG_CODE) and $this->hasGeometryColumn()) {
      $sql .= "
SELECT UpdateGeometrySRID('" . $this->name . "', '" . GEOMETRY_COLUMN_NAME . "', " . GEOMETRY_EPSG_CODE . ");
      ";
    }

    # Ausgabe Tabellenkommentare
    if (!empty($this->comments)) {
      $sql .= "\nCOMMENT ON TABLE " . $this->name . " IS '" .
        implode(', ', $this->comments) . "';";
    }

    # Ausgabe Attributkommentare
    foreach($this->attributes AS $attribute) {
      $sql .= $attribute->getComment($this->name);
    }

    # Ausgabe Assoziationskommentare
    foreach($this->associationEnds AS $associationEnd) {
      $sql .= $associationEnd->getComment($this->name);
    }

    # Ausgabe Tabellen Values
    if (!empty($this->values->rows)) {
      $sql .= "\nINSERT INTO " . $this->name . ' (' .
        implode(
          ',',
          array_map(
            function($attribute) {
              return $attribute->name;
            },
            $this->attributes
          )
        ) .
      ") VALUES \n";
      $sql .= $this->values->asSql();
      $sql .= ';';
    }

    return $sql;
  }

  function asFlattenedSql() {
    $attribute_parts = array();
    $sql = "
" . PG_CREATE_TABLE . " " . $this->name . " (
";
    # ogc_fid Spalte
    $attribute_parts[] .= "  ogc_fid serial NOT NULL";

    # identifier Spalte
    if(PG_WITH_IDENTIFIER) {
      $attribute_parts[] .= "  identifier " . PG_CHARACTER_VARYING;
    }

    # gml_id Spalte
    $attribute_parts[] .= "  gml_id character varying(16)";

    # Ausgabe Attribute
    $attribute_parts = array_merge(
      $attribute_parts,
      array_map(
        function($attribute) {
          return $attribute->asFlattenedSql();
        },
        $this->attributes
      )
    );

    if ($this->parent != null) {
      # Ausgabe vererbter Assoziationsenden
      $attribute_parts = array_merge(
        $attribute_parts,
        array_map(
          function($associationsEnd) {
            return $associationsEnd->asSql('table');
          },
          $this->getParentsAssociationEnds()
        )
      );
    }

    # Ausgabe Assoziationsenden
    $attribute_parts = array_merge(
      $attribute_parts,
      array_map(
        function($associationsEnd) {
          return $associationsEnd->asSql('table');
        },
        $this->associationEnds
      )
    );

    # Ausgabe Primary Key
    if ($this->primaryKey != '')
      $attribute_parts[] = "CONSTRAINT " . $this->name . '_pkey PRIMARY KEY (' . $this->primaryKey . ')';

    # Zusammenfügen der Attributteile
    $sql .= implode(",\n", $attribute_parts);

    $sql .= '
)';

    # Ausgabe WITH OIDS
    if ($this->withOids)
      $sql .= ' WITH OIDS';

    $sql .= ';
';  # Tabellenende

    # Set epsg code
    if (!empty(GEOMETRY_EPSG_CODE) and $this->hasGeometryColumn()) {
      $sql .= "
SELECT UpdateGeometrySRID('" . $this->name . "', '" . GEOMETRY_COLUMN_NAME . "', " . GEOMETRY_EPSG_CODE . ");
      ";
    }

    # Ausgabe Tabellenkommentare
    if (!empty($this->comments)) {
      $sql .= "\nCOMMENT ON TABLE " . $this->name . " IS '" .
        implode(', ', $this->comments) . "';";
    }

    # Ausgabe Attributkommentare
    foreach($this->attributes AS $attribute) {
      $sql .= $attribute->getFlattenedComment($this->name);
    }

    # Ausgabe Assoziationskommentare
    foreach($this->associationEnds AS $associationEnd) {
      $sql .= $associationEnd->getComment($this->name);
    }

    # Ausgabe Tabellen Values
    if (!empty($this->values->rows)) {
      $sql .= "\nINSERT INTO " . $this->name . ' (' .
        implode(
          ',',
          array_map(
            function($attribute) {
              return $attribute->name;
            },
            $this->attributes
          )
        ) .
      ") VALUES \n";
      $sql .= $this->values->asSql();
      $sql .= ';';
    }

    return $sql;
  }

  function asGfs() {
    $attribute_parts = array();
    $gfs = "
  <GMLFeatureClass>
    <Name>".$this->alias."</Name>
    <ElementPath>".$this->alias."</ElementPath>
    <GeometryType>".$this->getGeometryType()."</GeometryType>";

    # identifier Spalte
    if(PG_WITH_IDENTIFIER) {
      $attribute_parts[] .= "
    <PropertyDefn>
      <Name>identifier</Name>
      <ElementPath>identifier</ElementPath>
      <Type>String</Type>
      <Width>28</Width>
    </PropertyDefn>";

    # gml_id Spalte
    $attribute_parts[] .= "
    <PropertyDefn>
      <Name>gml_id</Name>
      <ElementPath>gml_id</ElementPath>
      <Type>String</Type>
      <Width>16</Width>
    </PropertyDefn>";

    # Ausgabe Attribute
    $attribute_parts = array_merge(
      $attribute_parts,
      array_map(
        function($attribute) {
          return $attribute->asGfs();
        },
        $this->attributes
      )
    );

    if ($this->parent != null) {
      # Ausgabe vererbter Assoziationsenden
      $attribute_parts = array_merge(
        $attribute_parts,
        array_map(
          function($associationsEnd) {
            return $associationsEnd->asGfs();
          },
          $this->getParentsAssociationEnds()
        )
      );
    }

    # Ausgabe Assoziationsenden
    $attribute_parts = array_merge(
      $attribute_parts,
      array_map(
        function($associationsEnd) {
          return $associationsEnd->asGfs();
        },
        $this->associationEnds
      )
    );

    # Zusammenfügen der Attributteile
    $gfs .= implode('', $attribute_parts);

    $gfs .= "
  </GMLFeatureClass>";
    
    return $gfs;
  }

}
?>
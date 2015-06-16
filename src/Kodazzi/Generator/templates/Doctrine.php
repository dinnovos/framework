[?php
<?php
$unique = array();
$primary = array();
$manytomany = array();
?>
$schema = new \Doctrine\DBAL\Schema\Schema();
<?php foreach( $data as $model => $data ) { ?>
$<?php echo $model ?> = $schema->createTable("<?php echo $options['_prefix']?><?php echo $data['options']['table'] ?>");
<?php foreach( $data['fields'] as $field => $config ) {?>
<?php if( isset($config['unique']) && $config['unique'] ){?>
<?php $unique[] = $field; ?>
<?php } ?>
<?php if( $config['type'] == 'primary' ){?>
<?php $primary[] = $field; ?>
<?php } ?>
<?php if($config['type'] == 'primary' ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "integer", array("unsigned" => true, "autoincrement" => true));
<?php } elseif( in_array($config['type'] , array('title', 'string', 'name', 'email', 'login', 'password', 'url', 'file', 'image') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "string", array("length" => <?php echo $config['length'] ?>, 'notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( $config['type'] == 'integer' ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "integer", array("length" => <?php echo $config['length'] ?>, 'notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( in_array($config['type'] , array('editor', 'note') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "text", array('notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( in_array($config['type'] , array('decimal') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "decimal", array('notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>, 'precision' => <?php echo $config['precision'] ?>, 'scale' => <?php echo $config['scale'] ?>));
<?php } elseif( in_array($config['type'] , array('date') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "date", array('notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( in_array($config['type'] , array('datetime') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "datetime", array('notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( in_array($config['type'] , array('time') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "time", array('notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( in_array($config['type'] , array('options') ) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $field ?>", "string", array("length" => 100, 'notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
<?php } elseif( in_array($config['type'] , array('foreign') ) ){ ?>
<?php if( in_array($config['relation'], array('many-to-one', 'one-to-one')) ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $config['join']['name'] ?>", "integer", array('unsigned' => true, 'notnull' => <?php echo ( isset($config['notnull']) && !$config['notnull']) ? 'false' : 'true'; ?>));
$<?php echo $model ?>->addForeignKeyConstraint($<?php echo $config['model'] ?>, array("<?php echo $config['join']['name'] ?>"), array("<?php echo $config['join']['foreignField'] ?>"));
<?php } else if( $config['relation'] == 'many-to-one-self-referencing' ){ ?>
$<?php echo $model ?>->addColumn("<?php echo $config['join']['name'] ?>", "integer", array('unsigned' => true, 'notnull' => false));
$<?php echo $model ?>->addForeignKeyConstraint($<?php echo $config['model'] ?>, array("<?php echo $config['join']['name'] ?>"), array("<?php echo $config['join']['foreignField'] ?>"));
<?php } ?>
<?php } elseif( in_array($config['type'], array('table')) && $config['relation'] == 'many-to-many'){ ?>
<?php $manytomany[$model] = $config; ?>
<?php } ?>
<?php if( isset($unique) && count( $unique ) ){ ?>
$<?php echo $model ?>->addUniqueIndex(<?php echo \Kodazzi\Tools\Util::parsetArrayToString($unique); ?>);
<?php } ?>
<?php if( $primary ) {?>
$<?php echo $model ?>->setPrimaryKey(<?php echo \Kodazzi\Tools\Util::parsetArrayToString($primary); ?>);
<?php } ?>
<?php
$unique = array();
$primary = array();
?>
<?php } ?>
<?php if(isset($data['options']['timestampable']) && $data['options']['timestampable'] ){?>
$<?php echo $model ?>->addColumn("created", "datetime", array('notnull' => true));
$<?php echo $model ?>->addColumn("updated", "datetime", array('notnull' => true));
<?php } ?>
<?php if(isset($data['options']['sluggable'])): ?>
$<?php echo $model ?>->addColumn("slug", "string", array("length" => 255, 'notnull' => true));
<?php endif; ?>
<?php } ?>

<?php if( count( $manytomany ) ){ ?>
<?php foreach($manytomany as $model => $many){ ?>
$<?php echo $many['joinTable']['name'] ?> = $schema->createTable("<?php echo $many['joinTable']['name'] ?>");
$<?php echo $many['joinTable']['name'] ?>->addColumn("<?php echo $many['joinTable']['join']['name'] ?>", "integer", array('unsigned' => true));
$<?php echo $many['joinTable']['name'] ?>->addColumn("<?php echo $many['joinTable']['inverseJoin']['name'] ?>", "integer", array('unsigned' => true));
$<?php echo $many['joinTable']['name'] ?>->addForeignKeyConstraint($<?php echo $model ?>, array("<?php echo $many['joinTable']['join']['name'] ?>"), array("<?php echo $many['joinTable']['join']['foreignField'] ?>"));
$<?php echo $many['joinTable']['name'] ?>->addForeignKeyConstraint($<?php echo $many['model'] ?>, array("<?php echo $many['joinTable']['inverseJoin']['name'] ?>"), array("<?php echo $many['joinTable']['inverseJoin']['foreignField'] ?>"));
<?php } ?>
<?php } ?>
return $schema;
[?php
namespace ##NAMESPACE##;
<?php 
$_foreigns = array();
$has_title = false;
foreach ( $data['fields'] as $field => $_options )
{
	if($_options['type'] == 'foreign')
	{
		$_foreigns[$field] = $_options;
	}

	if ( $_options['type'] == 'primary' )
	{
		$field_primary = $field;
	}

	// Si no tiene title y hay un campo tipo title o string lo utiliza.
	if( !$has_title && ($_options['type'] == 'title' || $_options['type'] == 'string' || $_options['type'] == 'name' || $_options['type'] == 'integer') )
	{
		$field_title = $field;
		$has_title = true;
	}
}
?>
/** 
* @Table("<?php echo $data['options']['table'] ?>")
*/
Class ##CLASS##
{
	const table = '<?php echo $options['_prefix']?><?php echo strtolower($data['options']['table']) ?>';
<?php if( isset($field_title) ): ?>
	const title = '<?php echo $field_title; ?>';
<?php endif; ?>
	const primary = '<?php echo $field_primary; ?>';
<?php if(isset($data['options']['timestampable']) && $data['options']['timestampable']): ?>
	const hasTimestampable = true;
<?php endif; ?>
<?php if(isset($data['options']['sluggable']) && count($data['options']['sluggable'])): ?>
	public function getFieldsSluggable()
	{
		return array("<?php echo implode('", "', $data['options']['sluggable'])?>");
	}
<?php endif; ?>

<?php if(count($_foreigns)): ?>
	public function getDefinitionRelations()
	{
		return array(
<?php foreach($_foreigns as $_field => $_opt): ?>
<?php if($_opt['relation'] == 'one-to-one'): ?>
			'##NAMESPACE##\<?php echo $_opt['model']; ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldLocal' => '<?php echo $_opt['join']['name'] ?>' ),
<?php elseif($_opt['relation'] == 'many-to-one'): ?>
			'##NAMESPACE##\<?php echo $_opt['model']; ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldLocal' => '<?php echo $_opt['join']['name'] ?>' ),
<?php elseif($_opt['relation'] == 'one-to-many'): ?>
			'##NAMESPACE##\<?php echo $_opt['model']; ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldForeign' => '<?php echo $_opt['join']['foreignField'] ?>' ),
<?php elseif($_opt['relation'] == 'many-to-one-self-referencing'): ?>
			'##NAMESPACE##\<?php echo $_opt['model']; ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldLocal' => '<?php echo $_opt['join']['name'] ?>' ),
<?php endif; ?>
<?php endforeach; ?>
		);
	}
<?php endif; ?>
}
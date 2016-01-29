[?php
/**
* This file is part of the Kodazzi Framework.
*
* (c) Jorge Gaitan <info@kodazzi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ##NAMESPACE##;

use Kodazzi\Orm\ActiveRecord;

<?php 
$_foreigns = array();
$has_title = false;
foreach ( $data['fields'] as $field => $_options )
{
	if($_options['type'] == 'foreign')
	{
        // Si "model" no tiene el formato Company\Bundle:Model lo coloca con el formato
        if(! strpos($_options['model'], ':'))
        {
            $_options['model'] = "{$options['namespace_bundle']}:{$_options['model']}";
        }

		$_foreigns[$field] = $_options;
	}

	if ($_options['type'] == 'primary')
	{
		$field_primary = $field;
	}

    // Si no tiene title y hay un campo tipo title o string lo utiliza para el modelo
    if( !$has_title && ($_options['type'] == 'title' || $_options['type'] == 'string' || $_options['type'] == 'name' || $_options['type'] == 'integer') )
	{
		$field_title = $field;
		$has_title = true;
	}
}

if(array_key_exists('translatable', $data['options']))
{
    // Si "model" no tiene el formato Company\Bundle:Model lo coloca con el formato
    if(! strpos($data['options']['translatable'], ':'))
    {
        $data['options']['translatable'] = "{$options['namespace_bundle']}:{$data['options']['translatable']}";
    }
}
?>
/** 
* @Table("<?php echo $data['options']['table'] ?>")
*/
Class ##CLASS## extends ActiveRecord
{
	const table = '<?php echo strtolower($data['options']['table']) ?>';
<?php if( isset($field_title) ): ?>
	const title = '<?php echo $field_title; ?>';
<?php endif; ?>
	const primary = '<?php echo $field_primary; ?>';
<?php if(isset($data['options']['timestampable']) && $data['options']['timestampable']): ?>
	const hasTimestampable = true;
<?php endif; ?>
<?php if(array_key_exists('translatable', $data['options']) && $data['options']['translatable']): ?>
    const modelLanguage = '<?php echo "{$data['options']['translatable']}"; ?>';
<?php endif; ?>
<?php if(array_key_exists('translatable', $data['options']) && $data['options']['translatable']): ?>
    const modelTranslation = '<?php echo "{$options['namespace_bundle']}:{$options['model']}"; ?>Translation';
<?php endif; ?>
<?php if(isset($data['options']['sluggable']) && count($data['options']['sluggable'])): ?>

	public function getFieldsSluggable()
	{
		return array("<?php echo $data['options']['sluggable']?>");
	}
<?php endif; ?>
<?php if(count($_foreigns)){ ?>

	public function getDefinitionRelations()
	{
		return array(
<?php foreach($_foreigns as $_field => $_opt){ ?>
<?php if($_opt['relation'] == 'one-to-one'){ ?>
			'<?php echo str_replace('/', '\\',$_opt['model']); ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldLocal' => '<?php echo $_opt['join']['name'] ?>' ),
<?php }elseif($_opt['relation'] == 'many-to-one'){ ?>
			'<?php echo str_replace('/', '\\',$_opt['model']); ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldLocal' => '<?php echo $_opt['join']['name'] ?>' ),
<?php }elseif($_opt['relation'] == 'one-to-many'){ ?>
			'<?php echo str_replace('/', '\\',$_opt['model']); ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldForeign' => '<?php echo $_opt['join']['foreignField'] ?>' ),
<?php }elseif($_opt['relation'] == 'many-to-one-self-referencing'){ ?>
			'<?php echo str_replace('/', '\\',$_opt['model']); ?>' => array('field' => '<?php echo $_field; ?>' , 'fieldLocal' => '<?php echo $_opt['join']['name'] ?>' ),
<?php } ?>
<?php } ?>
		);
	}
<?php } ?>
}
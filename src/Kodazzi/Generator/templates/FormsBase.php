[?php

/**
* This file is part of the Kodazzi Framework.
*
* (c) Jorge Gaitan <info@kodazzi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
* ##CLASS##
*
* @author Jorge Gaitan
*/

namespace ##NAMESPACE##;

use Kodazzi\Form\FormBuilder;

Class ##CLASS## extends FormBuilder
{
	protected function config()
	{
		$this->setNameModel('<?php echo str_replace('/', '\\', $options['model']); ?>');

<?php foreach($data['fields'] as $field => $_options){ ?>
<?php
    if(isset($_options['model']))
    {
        $model = $_options['model'];

        if(strpos($_options['model'], ':') == 0)
        {
            $model = "{$options['namespace_bundle']}:{$model}";
        }
    }
?>
<?php if(strtoupper($_options['type']) != 'PRIMARY'){ ?>
<?php if(strtoupper($_options['type']) == 'FILE' || strtoupper($_options['type']) == 'IMAGE') {$is_multipart = true;} ?>
<?php if( isset($_options['type']) && $_options['type'] == 'foreign' ){ ?>
<?php if( in_array($_options['relation'], array('many-to-one', 'one-to-one')) ){ ?>
		$this->setWidget('<?php echo $_options['join']['name']; ?>', new \Kodazzi\Form\Fields\<?php echo ucfirst($_options['type']) ?>())<?php echo (isset($_options['notnull']) && $_options['notnull'] == false) ? '->setRequired(false)' : '' ?><?php echo (isset($_options['relation'])) ? "->setTypeRelation('".str_replace('/', '\\', $_options['relation'])."')" : ""; ?><?php echo (isset($_options['model'])) ? "->definitionRelation('{$model}', array('name' => '".$_options['join']['name']."', 'foreignField' => '".$_options['join']['foreignField']."') )" : ""; ?><?php echo (isset($_options['label'])) ? "->setValueLabel('".htmlentities($_options['label'], ENT_QUOTES, 'UTF-8')."')": '' ?><?php echo (isset($_options['nullable']) && $_options['nullable']) ? '->setRequired(false)' : '' ?><?php echo (isset($_options['css'])) ? "->setClassCss('{$_options['css']}')" : "" ?>;
<?php }else if( $_options['relation'] == 'many-to-one-self-referencing' ){ ?>
		$this->setWidget('<?php echo $_options['join']['name']; ?>', new \Kodazzi\Form\Fields\<?php echo ucfirst($_options['type']) ?>())<?php echo (isset($_options['relation'])) ? "->setTypeRelation('".$_options['relation']."')" : ""; ?><?php echo (isset($_options['model'])) ? "->definitionRelation('{$model}', array('name' => '".$_options['join']['name']."', 'foreignField' => '".$_options['join']['foreignField']."') )" : ""; ?><?php echo (isset($_options['label'])) ? "->setValueLabel('".htmlentities($_options['label'], ENT_QUOTES, 'UTF-8')."')": '' ?><?php echo '->setRequired(false)'; ?><?php echo (isset($_options['css'])) ? "->setClassCss('{$_options['css']}')" : "" ?>;
<?php } ?>
<?php }else if(isset($_options['type']) && $_options['type'] == 'table' && in_array($_options['relation'], array('many-to-many', 'many-to-many-self-referencing')) ){?>
		$this->setWidget('<?php echo $field; ?>', new \Kodazzi\Form\Fields\<?php echo ucfirst($_options['type']) ?>())<?php echo (isset($_options['relation'])) ? "->setTypeRelation('{$_options['relation']}')" : ""; ?><?php echo (isset($_options['model'])) ? "->definitionRelation('{$model}', array('tableManyToMany' => '{$_options['joinTable']['name']}', 'localField' => '".$_options['joinTable']['join']['name']."', 'foreignField' => '".$_options['joinTable']['inverseJoin']['name']."') )" : ""; ?><?php echo (isset($_options['label'])) ? "->setValueLabel('".htmlentities($_options['label'], ENT_QUOTES, 'UTF-8')."')": '' ?><?php echo (isset($_options['nullable']) && $_options['nullable']) ? '->setRequired(false)' : '' ?><?php echo (isset($_options['css'])) ? "->setClassCss('{$_options['css']}')" : "" ?>;
<?php }else if(isset($_options['type']) && $_options['type'] == 'check'){?>
        $this->setWidget('<?php echo $field; ?>', new \Kodazzi\Form\Fields\<?php echo ucfirst($_options['type']) ?>())<?php echo (isset($_options['value']) && $_options['value'] != '') ? '->setAllowedValue("'.$_options['value'].'")' : '' ?><?php echo (isset($_options['notnull']) && $_options['notnull'] == false) ? '->setRequired(false)' : '' ?>;
<?php }else{ ?>
		$this->setWidget('<?php echo $field; ?>', new \Kodazzi\Form\Fields\<?php echo ucfirst($_options['type']) ?>())<?php echo (isset($_options['notnull']) && $_options['notnull'] == false) ? '->setRequired(false)' : '' ?><?php echo (isset($_options['label'])) ? "->setValueLabel('".htmlentities($_options['label'], ENT_QUOTES, 'UTF-8')."')": '' ?><?php echo (isset($_options['unique']) && $_options['unique']) ? '->setUnique(true)' : '' ?><?php echo (isset($_options['css'])) ? "->setClassCss('{$_options['css']}')" : "" ?><?php echo (isset($_options['help'])) ? "->setHelp('{$_options['help']}')" : "" ?><?php echo (isset($_options['disabled']) && $_options['disabled']) ? "->setDisabled(true)" : "" ?><?php echo (isset($_options['readonly']) && $_options['readonly']) ? "->setReadonly(true)" : "" ?><?php echo (isset($_options['hidden']) && $_options['hidden']) ? "->setHidden(true)" : "" ?><?php echo (isset($_options['display']) && $_options['display']) ? "->setDisplay(false)" : "" ?><?php echo (isset($_options['scale']) && $_options['scale']) ? "->setScale({$_options['scale']})" : "" ?><?php echo (isset($_options['precision']) && $_options['precision']) ? "->setPrecision({$_options['precision']})" : "" ?><?php echo (isset($_options['size']) && $_options['size']) ? "->setFileSize({$_options['size']})" : "" ?><?php echo (isset($_options['ext'])) ? "->setFileExt(array('".  implode("', '", $_options['ext'])."'))" : "" ?><?php echo (isset($_options['max_dimensions'])) ? "->setMaxDimensions(".  implode(", ", $_options['max_dimensions']).")" : "" ?><?php echo (isset($_options['min_dimensions'])) ? "->setMinDimensions(".  implode(", ", $_options['min_dimensions']).")" : "" ?><?php echo (isset($_options['values'])) ? "->setOptions(".\Kodazzi\Tools\Util::parsetArrayToString($_options['values']).")" : ""; ?><?php echo (isset($_options['default'])) ? "->setDefault('".$_options['default']."')" : ""; ?><?php echo (isset($_options['tag'])) ? "->setTypeFieldTag('".$_options['tag']."')" : ""; ?>;
<?php } ?>
<?php if(isset($_options['copys']) && is_array($_options['copys'])){ ?>
		$this->getWidget('<?php echo $field; ?>')->setCopys(array(
<?php foreach($_options['copys'] as $copy){ ?>
			array('width'=> <?php echo $copy['width']; ?>,'height'=> <?php echo $copy['height']; ?>,'method'=> '<?php echo $copy['method']; ?>','dir'=> '<?php echo $copy['dir']; ?>'),
<?php } ?>
		));
<?php } ?>
<?php } ?>
<?php } ?>
<?php if(isset($is_multipart) && $is_multipart){ ?>
		$this->setMultipart(true);
<?php } ?>
	}
<?php if(array_key_exists('translatable', $data['options'])){?>

    public function getTranslationForm()
    {
        return new \<?php echo str_replace('/', '\\', $options['form_translation']); ?>TranslationForm();
    }
<?php } ?>
}
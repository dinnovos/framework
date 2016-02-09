[?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
'<?php echo $options['form'] ?>' => [
<?php foreach($data['fields'] as $field => $_options){ ?>
<?php if( isset($_options['type']) && $_options['type'] == 'foreign' ){ ?>
<?php if( in_array($_options['relation'], array('many-to-one', 'one-to-one', 'many-to-one-self-referencing')) ){ ?>
    '<?php echo $_options['join']['name']; ?>' => '<?php echo ucfirst(str_replace('_', ' ', $_options['join']['name'])); ?>',
<?php } ?>
<?php }else{ ?>
    '<?php echo $field; ?>' => '<?php echo ucfirst(str_replace('_', ' ', $field)); ?>',
<?php } ?>
<?php } ?>
    ]
];
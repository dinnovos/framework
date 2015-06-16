<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Ys_I18n
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Translator;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Finder\Finder;
use Kodazzi\Container\Service;

Class TranslatorBuilder
{
	private $data = array();

    private $Translator = null;

    public function loader($locale = 'es_ES')
    {
        $Translator = new Translator($locale);
        $Translator->addLoader('array', new ArrayLoader());
        $data = array();
        $part_locale = explode('_', $locale);

        $bundles = Service::getNamespacesBundles();

        foreach($bundles as $bundle)
        {
            $path_i18n = str_replace('\\', '/', YS_BUNDLES.$bundle.'i18n/'.$part_locale[0]);

            if(is_dir($path_i18n))
            {
                $finder = new Finder();
                $finder->files()->name('*.i18n.php')->in($path_i18n);

                // Une todos los esquemas en un solo array
                foreach($finder as $file)
                {
                    $_a = require $file->getRealpath();

                    $data = array_merge($data, $_a);
                }
            }
        }

        if(is_dir($path_i18n))
        {
            $path_i18n = str_replace('\\', '/', YS_APP.'i18n/'.$part_locale[0]);

            $finder = new Finder();
            $finder->files()->name('*.i18n.php')->in($path_i18n);

            // Une todos los esquemas en un solo array
            foreach($finder as $file)
            {
                $_a = require $file->getRealpath();

                $data = array_merge($data, $_a);
            }
        }

        $Translator->addResource('array', $data, $locale);

        $this->Translator = $Translator;
    }

	public function get($key)
	{
        return $this->Translator->trans($key);
	}
}
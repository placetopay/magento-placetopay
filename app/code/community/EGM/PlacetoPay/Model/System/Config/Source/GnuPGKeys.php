<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2011 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: GnuPGKeys.php,v 1.0.8 2011-06-01 20:05:00-05 egarcia Exp $
 */

/**
 * Lista de llaves disponibls en el KeyRing
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      martes, 17 de noviembre de 2009
 */
class EGM_PlacetoPay_Model_System_Config_Source_GnuPGKeys
{
    public function toOptionArray()
    {
        // obtiene la configuracion de PlacetoPay
        $fields = Mage::getStoreConfig('placetopay/gpg');

        // incializa las opciones a retornar
        $options = array(
            array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- Please Select --'))
        );

        // verifica que se hayan establecido las rutas del GnuPG y del anillo de llaves
        if (!empty($fields['gpgpath']) && !empty($fields['gpghomedir'])) {
            // carga la clase del GnuPG para obtener la lista de llaves
            require_once(dirname(__FILE__) . '/../../../Classes/egmGnuPG.class.php');

            // usa la clase para obtener las llaves, si obtiene entonces genera la lista
            $gpg = new egmGnuPG($fields['gpgpath'], $fields['gpghomedir']);
            $keys = $gpg->ListKeys();
            if (!empty($keys)) {
                foreach ($keys as $key)
                    $options[] = array('value' => $key['KeyID'], 'label' => $key['UserID']);
            } else
                $options[] = array('value' => 'GPG', 'label' => $gpg->error);
        } else
            $options[] = array('value' => 'error', 'label' => Mage::helper('placetopay')->__('Missing GPG Path or Keyring'));
        return $options;
    }
}

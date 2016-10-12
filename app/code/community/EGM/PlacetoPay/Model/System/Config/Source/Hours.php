<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2010 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    $Id: Logos.php,v 1.0.1 2011-06-02 06:39:00-05 egarcia Exp $
 */

/**
 * Horas de disponibilidad del medio de pago en promoción
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @author     Enrique Garcia M. <ingenieria@egm.co>
 * @since      jueves, 2 de junio de 2011
 */
class EGM_PlacetoPay_Model_System_Config_Source_Hours
{
    public function toOptionArray()
    {
        // establece las horas disponibles para las promociones
        $options = array(
            array('value' => '00', 'label' => '00:00 - 00:59'),
            array('value' => '01', 'label' => '01:00 - 01:59'),
            array('value' => '02', 'label' => '02:00 - 02:59'),
            array('value' => '03', 'label' => '03:00 - 03:59'),
            array('value' => '04', 'label' => '04:00 - 04:59'),
            array('value' => '05', 'label' => '05:00 - 05:59'),
            array('value' => '06', 'label' => '06:00 - 06:59'),
            array('value' => '07', 'label' => '07:00 - 07:59'),
            array('value' => '08', 'label' => '08:00 - 08:59'),
            array('value' => '09', 'label' => '09:00 - 09:59'),
            array('value' => '10', 'label' => '10:00 - 10:59'),
            array('value' => '11', 'label' => '11:00 - 11:59'),
            array('value' => '12', 'label' => '12:00 - 12:59'),
            array('value' => '13', 'label' => '13:00 - 13:59'),
            array('value' => '14', 'label' => '14:00 - 14:59'),
            array('value' => '15', 'label' => '15:00 - 15:59'),
            array('value' => '16', 'label' => '16:00 - 16:59'),
            array('value' => '17', 'label' => '17:00 - 17:59'),
            array('value' => '18', 'label' => '18:00 - 18:59'),
            array('value' => '19', 'label' => '19:00 - 19:59'),
            array('value' => '20', 'label' => '20:00 - 20:59'),
            array('value' => '21', 'label' => '21:00 - 21:59'),
            array('value' => '22', 'label' => '22:00 - 22:59'),
            array('value' => '23', 'label' => '23:00 - 23:59'),
        );
        return $options;
    }
}

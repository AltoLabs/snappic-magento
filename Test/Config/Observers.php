<?php
/**
 * Ensure that all observers are defined and in the correct areas
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Test_Config_Observers extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Ensure that all observers are defined correctly
     * @dataProvider dataProvider
     */
    public function testSnappicObserversDefined($area, $event, $model, $method)
    {
        $this->assertEventObserverDefined($area, $event, $model, $method);
    }
}

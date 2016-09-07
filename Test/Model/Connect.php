<?php
/**
 * Helper to return appropriate payload structures for various input types
 *
 * @coversDefaultClass AltoLabs_Snappic_Model_Connect
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Test_Model_Connect extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Simple example unit test to ensure that the seal() method works correctly
     * @covers ::seal
     */
    public function testSeal()
    {
        $connect = Mage::getModel('altolabs_snappic/connect');
        $this->assertSame('{"data":"hello world"}', $connect->seal('hello world'));
    }
}

<?php
/**
 * Class AltoLabs_Snappic_Model_Resource_Session
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Model_Resource_Session extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor method
     */
    public function _construct()
    {
        $this->_init('altolabs_snappic/session', 'id');
    }

    public function getQuoteIdByToken($token)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from(
                ['al' => $this->getTable('altolabs_snappic/session')],
                [
                    'quote_id'   => 'al.quote_id'
                ]
            )
            ->where('soap_session_id = ?', $token)
            ->limit(1);

        $result = $this->_getReadAdapter()->fetchAll($select);
        if (count($result) > 0) {
            return $result[0]['quote_id'];
        }

        return [];
    }
}

<?php

class CRM_Testsettings_MandaatSynchronisator extends CRM_Sepamandaat_OdooSync_Synchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    $return = parent::isThisItemSyncable($sync_entity);
    if ($return) {
      $data = $this->getSepaMandaat($sync_entity->getEntityId());
      try {
        //has contact group 'Test: voor incasso contributie SP lidmaatschap'
        $group_id = civicrm_api3('Group', 'getvalue', array(
          'return' => 'id',
          'title' => 'Test: voor incasso contributie SP lidmaatschap'
        ));
        $groups = civicrm_api3('GroupContact', 'get', array(
          'contact_id' => $data['contact_id']
        ));
        foreach($groups['values'] as $group) {
          if ($group['group_id'] == $group_id) {
            return true;
          }
        }
      } catch (Exception $e) {
        return FALSE;
      }
    }
    return false;
  }
  
}


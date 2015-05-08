<?php

class CRM_Testsettings_MandaatSynchronisator extends CRM_Sepamandaat_OdooSync_Synchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    $return = parent::isThisItemSyncable($sync_entity);
    if ($return) {
      $data = $this->getSepaMandaat($sync_entity->getEntityId());
      if ($data['status'] == 'RCUR' || $data['status'] == 'FRST') {
        return true; //only rcur and first status
      }
    }
    return false;
  }
  
}


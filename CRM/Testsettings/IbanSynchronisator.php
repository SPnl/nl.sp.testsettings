<?php

class CRM_Testsettings_IbanSynchronisator extends CRM_Ibanodoosync_Synchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    return false;
  }
  
}

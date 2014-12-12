<?php

class CRM_Testsettings_MandaatSynchronisator extends CRM_Sepamandaat_OdooSync_Synchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    return false;
  }
  
}


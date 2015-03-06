<?php

/**
 * This class prevents contribution to sync to Odoo
 */
class CRM_Testsettings_ContributionSynchronisator extends CRM_OdooContributionSync_ContributionSynchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {

    //to test we return false so no contributions are synced to Odoo
    return false;

    $return = parent::isThisItemSyncable($sync_entity);
    //do not sync contributions with a date before 13 december 2014
    if ($return) {
      $contribution = $this->getContribution($sync_entity->getEntityId());
      $date = new DateTime($contribution['receive_date']);
      if ($date->format('Ymd') < 20141215) {
        $return = false;
      }
    }
    
    
    //if the contact test is succesfull we enable normal sync
    return $return;
  }
  
}


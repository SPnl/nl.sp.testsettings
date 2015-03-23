<?php

/**
 * This class prevents contribution to sync to Odoo
 */
class CRM_Testsettings_ContributionSynchronisator extends CRM_OdooContributionSync_ContributionSynchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {

    //to test we return false so no contributions are synced to Odoo
    //return false;

    $return = parent::isThisItemSyncable($sync_entity);
    //do not sync contributions with a date before 13 december 2014
    if ($return) {
      $contribution = $this->getContribution($sync_entity->getEntityId());
      try {
        //has contact group 'Test: voor incasso contributie SP lidmaatschap'
        $group_id = civicrm_api3('Group', 'getvalue', array('return' => 'id', 'title' => 'Test: voor incasso contributie SP lidmaatschap'));
        $groups = civicrm_api3('GroupContact', 'get', array(
          'contact_id' => $contribution['contact_id']
        ));
        foreach($groups['values'] as $group) {
          if ($group['group_id'] == $group_id) {
            return true;
          }
        }
      } catch (Exception $e) {
        return false;
      }
    }
    
    return false;
  }
  
}


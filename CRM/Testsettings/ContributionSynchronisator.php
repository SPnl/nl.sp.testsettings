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
        $group_id = civicrm_api3('Group', 'getvalue', array('return' => 'id', 'title' => 'Test: totaal uitwisseling Civi Odoo'));
        $groups = civicrm_api3('GroupContact', 'get', array(
          'contact_id' => $contribution['contact_id']
        ));
        foreach($groups['values'] as $group) {
          if ($group['group_id'] == $group_id) {
            //check if this is a membership payment for Rood, SP, or SP en Rood
            $count = CRM_Core_DAO::singleValueQuery("
                  SELECT COUNT(*)
                  FROM `civicrm_membership_payment` mp
                  INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
                  INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
                  where (
                    mt.name = 'Lid SP'
                    OR mt.name = 'Lid ROOD'
                    OR mt.name = 'Lid SP en ROOD'
                  ) and mp.contribution_id = %1",
                array(
                  1 => array($contribution['id'], 'Integer')
                ));
            if ($count > 0) {
              return true;
            }
          }
        }
      } catch (Exception $e) {
        return false;
      }
    }

    return false;
  }
  
}


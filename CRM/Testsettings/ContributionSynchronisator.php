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
        //check if this is a membership payment for SP and in q2 and mandaat is RCUR
        $count = CRM_Core_DAO::singleValueQuery("
              SELECT COUNT(*)
              FROM civicrm_contribution c
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              INNER JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              INNER JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid SP'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND mandaat.status = 'RCUR'
              and c.id = %1",
            array(
              1 => array($contribution['id'], 'Integer')
            ));
        if ($count > 0) {
          return true;
        }
      } catch (Exception $e) {
        return false;
      }
    }

    return false;
  }
  
}


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
      $receive_date = new DateTime($contribution['receive_date']);
      if ($receive_date->format('Y') >= 2015) {
        if ($this->checkNotEventContribution($contribution)) {
          return TRUE;
        }
      }
    }
    if ($sync_entity->getOdooId()) {
      $this->performDelete($sync_entity->getOdooId(), $sync_entity);
    }
    return false;
  }

  protected function checkNotEventContribution($contribution) {
    $sql = "SELECT COUNT(*) as total FROM `civicrm_participant_payment` WHERE contribution_id = %1";
    $params[1] = array($contribution['id'], 'Integer');
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      if ($dao->total > 0) {
        return false;
      }
    }
    return true;
  }
  
}


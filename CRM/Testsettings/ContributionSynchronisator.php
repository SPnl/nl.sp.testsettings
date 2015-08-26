<?php

/**
 * This class prevents contribution to sync to Odoo
 */
class CRM_Testsettings_ContributionSynchronisator extends CRM_Spodoosync_Synchronisator_ContributionSynchronisator {
  
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {

    //to test we return false so no contributions are synced to Odoo
    //return false;

    $return = parent::isThisItemSyncable($sync_entity);
    //do not sync contributions with a date before 13 december 2014
    if ($return) {
      $contribution = $this->getContribution($sync_entity->getEntityId());
      if ($this->checkNotEventContribution($contribution)) {
        return TRUE;
      }
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


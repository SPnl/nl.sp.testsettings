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
      if ($this->checkSpanningOrTribune($contribution)) {
        return TRUE;
      } elseif ($this->checkAcceptgiroAndPeriodiek($contribution)) {
        return true;
      } elseif ($this->checkQ2($contribution)) {
        return true;
      } elseif ($this->checkQ3($contribution)) {
        return true;
      }
    }

    return false;
  }

  protected function checkSpanningOrTribune($contribution) {
    $config = CRM_Testsettings_Config::singleton();
    $financial_type_id = $contribution['financial_type_id'];
    if (isset($config->financial_types[$financial_type_id]['name'])) {
      $financial_type = $config->financial_types[$financial_type_id]['name'];
      switch ($financial_type) {
        case 'Tribune':
        case 'Spanning':
          return true;
      }
    }
    return false;
  }

  protected function checkAcceptgiroAndPeriodiek($contribution) {
    $config = CRM_Testsettings_Config::singleton();
    $payment_instrument_id = $contribution['instrument_id'];
    $financial_type_id = $contribution['financial_type_id'];
    if (isset($config->payment_instruments[$payment_instrument_id]['name'])) {
      $payment_instrument = $config->payment_instruments[$payment_instrument_id]['name'];
      switch ($payment_instrument) {
        case 'sp_acceptgiro':
        case 'Periodieke overboeking':
          $financial_type = $config->financial_types[$financial_type_id]['name'];
          switch ($financial_type) {
            case 'Contributie ROOD':
            case 'Contributie SP+ROOD':
            case 'Contributie SP':
              return TRUE;
              break;
          }
          break;
      }
    }
    return false;
  }

  protected function checkQ3($contribution) {
    try {
      //check if this is a membership payment for SP and in q2 and mandaat is RCUR
      $count = CRM_Core_DAO::singleValueQuery("
              SELECT COUNT(*)
              FROM civicrm_contribution c
              INNER JOIN civicrm_financial_type ft on c.financial_type_id = ft.id
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              LEFT JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              LEFT JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid SP'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 7 AND 9
              AND (mandaat.status IS NULL OR mandaat.status = 'RCUR')
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
    return false;
  }

  protected function checkQ2($contribution) {
    try {
      //check if this is a membership payment for SP and in q2 and mandaat is RCUR
      $count = CRM_Core_DAO::singleValueQuery("
              SELECT COUNT(*)
              FROM civicrm_contribution c
              INNER JOIN civicrm_financial_type ft on c.financial_type_id = ft.id
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              LEFT JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              LEFT JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid SP'
                OR
                mt.name = 'Lid ROOD'
                OR
                mt.name = 'Lid SP en ROOD'
                OR
                ft.name = 'Tribune'
                or
                ft.name = 'Spanning'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND (mandaat.status IS NULL OR mandaat.status = 'RCUR' OR mandaat.status = 'FRST')
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
    return false;
  }
  
}


<?php

/**
 * Collection of upgrade steps
 */
class CRM_Testsettings_Upgrader extends CRM_Testsettings_Upgrader_Base {

  public function upgrade_1015() {
    /**
     * Crediteer geannuleerde bijdragen die tocg in Odoo terecht zijn gekomen
     * Deze bijdragen worden gecrediteerd in Odoo en op die manier kunnen ze terug gestort worden
     */
    $cancel_id = CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name');
    $sql = "SELECT e.odoo_id, e.id
            FROM civicrm_odoo_entity e
            LEFT JOIN civicrm_contribution c ON e.entity = 'civicrm_contribution' AND e.entity_id = c.id
            WHERE e.odoo_id IS NOT NULL and e.status = 'SYNCED'
            AND c.contribution_status_id = '".$cancel_id."'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $title = ts('Correct invoice with Odoo id: %1', array(
        1 => $dao->odoo_id,
      ));
      $this->addTask($title, 'creditCancelledInvoice', $dao->odoo_id, $dao->id);
    }

    return true;
  }

  public static function creditCancelledInvoice($odoo_invoice_id, $sync_entity_id) {
    $connector = CRM_Odoosync_Connector::singleton();
    $is_deletable = false;
    $invoice = $connector->read('account.invoice', $odoo_invoice_id);
    if (isset($invoice['state']) && $invoice['state']->scalarval() == 'draft') {
      $is_deletable = true;
    }

    if ($is_deletable) {
      $connector->unlink('account.invoice', $odoo_invoice_id);
    } else {
      $now = new DateTime();
      $dao = CRM_Core_DAO::singleValueQuery("SELECT * FROM civicrm_odoo_entity where id = %1", array(1=>array($sync_entity_id, 'Integer')));
      $sync_entity = new CRM_Odoosync_Model_OdooEntity($dao);
      if ($sync_entity->getOdooField() != 'refunded') {
        $credit = new CRM_OdooContributionSync_CreditInvoice();
        $result = $credit->credit($odoo_invoice_id, $now);
        if ($result) {
          $sync_entity->setOdooField('refunded');
        }

        $sql = "UPDATE `civicrm_odoo_entity` SET `action` = NULL, `odoo_field` = %1, `sync_date` = NOW(), `last_error` = NULL, `last_error_date` = NULL WHERE `id` = %2";
        CRM_Core_DAO::executeQuery($sql, array(
          1 => array($sync_entity->getOdooField(), 'String'),
          2 => array($sync_entity_id, 'Positive'),
        ));

      }
    }

    return true;
  }

  public function upgrade_1014() {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN (
        SELECT c.id FROM `civicrm_contribution` c
        INNER JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
        INNER JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
        WHERE
        MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
        AND mandaat.status = 'FRST'
      );
    ");

    return true;
  }

  public function upgrade_1013() {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_sepa_mandaat'
      AND o.`entity_id` IN (
        SELECT s.id FROM `civicrm_value_sepa_mandaat` s WHERE s.status = 'FRST'
      );
    ");
    return true;
  }

  public function upgrade_1012() {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN (
        SELECT c.id FROM `civicrm_contribution` c
        INNER JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
        INNER JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
        WHERE
        MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
        AND mandaat.status = 'RCUR'
      );
    ");

    return true;
  }

  public function upgrade_1010() {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_sepa_mandaat'
      AND o.`entity_id` IN (
        SELECT s.id FROM `civicrm_value_sepa_mandaat` s WHERE s.status = 'RCUR'
      );
    ");
    return true;
  }

  /**
   * Update civicrm_odoo_entity so we start syncing mandates again and contributions again
   *
   * @return bool
   */
  public function upgrade_1008() {

    $contactIds = array();
    $group_id = civicrm_api3('Group', 'getvalue', array('return' => 'id', 'title' => 'Test: totaal uitwisseling Civi Odoo'));
    $contacts = civicrm_api3('GroupContact', 'get', array('group_id' => $group_id, 'options' => array('limit' => 999)));
    foreach($contacts['values'] as $contact) {
      $contactIds[] = $contact['contact_id'];
    }

    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_sepa_mandaat'
      AND o.`entity_id` IN (
        SELECT s.id FROM `civicrm_value_sepa_mandaat` s WHERE s.entity_id IN (".implode(",", $contactIds).")
      );
    ");

    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN (
        SELECT c.id FROM `civicrm_contribution` c WHERE c.contact_id IN (".implode(",", $contactIds).")
      );
    ");

    return TRUE;
  }

  /**
   * Update civicrm_odoo_entity so we start syncing mandates again and contributions again
   *
   * @return bool
   */
  public function upgrade_1002() {

    $contactIds = array();
    $group_id = civicrm_api3('Group', 'getvalue', array('return' => 'id', 'title' => 'Test: voor incasso contributie SP lidmaatschap'));
    $contacts = civicrm_api3('GroupContact', 'get', array('group_id' => $group_id));
    foreach($contacts['values'] as $contact) {
      $contactIds[] = $contact['contact_id'];
    }

    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_sepa_mandaat'
      AND o.`entity_id` IN (
        SELECT s.id FROM `civicrm_value_sepa_mandaat` s WHERE s.entity_id IN (".implode(",", $contactIds).")
      );
    ");
    return true;
  }
}

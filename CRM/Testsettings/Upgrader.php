<?php

/**
 * Collection of upgrade steps
 */
class CRM_Testsettings_Upgrader extends CRM_Testsettings_Upgrader_Base {

  /**
   * Update civicrm_odoo_entity so we start syncing mandates again and contributions again
   *
   * @return bool
   */
  public function upgrade_1001() {

    $contactIds = array();
    $group_id = civicrm_api3('Group', 'getvalue', array('return' => 'id', 'title' => 'Test: voor incasso contributie SP lidmaatschap'));
    $contacts = civicrm_api3('GroupContact', 'get', array('group_id' => $group_id));
    foreach($contacts['values'] as $contact) {
      $contactIds[] = $contact['contact_id'];
    }

    $this->executeSql("
      UPDATE civicrm_odoo_sync_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_sepa_mandaat'
      AND o.`entity_id` IN (
        SELECT s.id FROM `civicrm_value_sepa_mandaat` s WHERE s.entity_id IN (".implode(",", $contactIds).")
      );
    ");

    $this->executeSql("
      UPDATE civicrm_odoo_sync_entity o
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
}

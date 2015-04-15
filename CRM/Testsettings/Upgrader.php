<?php

/**
 * Collection of upgrade steps
 */
class CRM_Testsettings_Upgrader extends CRM_Testsettings_Upgrader_Base {

  public function upgrade_1011() {
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
        SELECT s.id FROM `civicrm_value_sepa_mandaat` s WHERE s.status = 'RCUR')
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

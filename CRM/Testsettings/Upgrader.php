<?php

/**
 * Collection of upgrade steps
 */
class CRM_Testsettings_Upgrader extends CRM_Testsettings_Upgrader_Base {

  public function upgrade_1025() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
    ";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_sepa_mandaat '
    ";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_value_payment_arrangement'
    ";
    CRM_Core_DAO::executeQuery($sql);

    return true;
  }

  public function upgrade_1024() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
              INNER JOIN `civicrm_option_value` ov on c.payment_instrument_id = ov.value
              INNER JOIN `civicrm_option_group` og ON og.id = ov.option_group_id and og.name = 'payment_instrument'
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              where
              (
                ov.name = 'sp_acceptgiro'
                OR
                ov.name = 'Periodieke overboeking'
              )
              and
              (
                mt.name = 'Lid ROOD'
                OR
                mt.name = 'Lid SP en ROOD'
                or
                mt.name = 'Lid SP'
              )
        )
    ";
    CRM_Core_DAO::executeQuery($sql);

    return true;
  }

  public function upgrade_1023() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
              INNER JOIN `civicrm_financial_type` ft ON c.financial_type_id = ft.id
              where
              (
                ft.name = 'Tribune'
                OR
                ft.name = 'Spanning'
              )
        )
    ";
    CRM_Core_DAO::executeQuery($sql);

    return true;
  }

  public function upgrade_1022() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
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
              AND (mandaat.status IS NULL or mandaat.status = 'RCUR')
        )
    ";
    CRM_Core_DAO::executeQuery($sql);

    return true;
  }

  public function upgrade_1021() {
    $dao = CRM_Core_DAO::executeQuery("SELECT c.* FROM civicrm_contribution c left join civicrm_membership_payment mt on c.id = mt.contribution_id  where c.source = 'handmatig' and mt.id is null");
    while($dao->fetch()) {
      //find active membership voor this contact
      $mid = CRM_Core_DAO::singleValueQuery("
        SELECT m.id
        FROM civicrm_membership m
        inner join civicrm_membership_type mt on m.membership_type_id = mt.id
        where
        (
          mt.name = 'Lid ROOD'
          OR
          mt.name = 'Lid SP en ROOD'
          or
          mt.name = 'Lid SP'
        )
        and m.status_id = 2 and m.contact_id = %1
        ", array(1=>array($dao->contact_id, 'Integer')));
      CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_membership_payment` (`membership_id`, `contribution_id`) VALUES (%1, %2)", array(
        1 => array($mid, 'Integer'),
        2 => array($dao->id, 'Integer')
      ));
    }

    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              LEFT JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              LEFT JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid ROOD'
                OR
                mt.name = 'Lid SP en ROOD'
                or
                mt.name = 'Lid SP'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND (mandaat.status IS NULL or mandaat.status = 'RCUR' OR mandaat.status = 'FRST')
        )
    ";
    CRM_Core_DAO::executeQuery($sql);

    return true;
  }

  public function upgrade_1020() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
              INNER JOIN civicrm_financial_type ft on c.financial_type_id = ft.id
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              LEFT JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              LEFT JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                ft.name = 'Tribune'
                or
                ft.name = 'Spanning'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND (mandaat.status IS NULL or mandaat.status = 'RCUR' OR mandaat.status = 'FRST')
        )
    ";
    CRM_Core_DAO::executeQuery($sql);
    return true;
  }

  public function upgrade_1019() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              LEFT JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              LEFT JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid ROOD'
                OR
                mt.name = 'Lid SP en ROOD'
                or
                mt.name = 'Lid SP'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND (mandaat.status IS NULL or mandaat.status = 'RCUR' OR mandaat.status = 'FRST')
        )
    ";
    CRM_Core_DAO::executeQuery($sql);
    return true;
  }

  public function upgrade_1018() {
    $sql = "
      UPDATE civicrm_odoo_entity o
      SET o.`status` = 'OUT OF SYNC',
      o.`sync_date` = null,
      o.`action` = 'INSERT'
      WHERE o.`status` = 'NOT SYNCABLE'
      AND o.`entity` = 'civicrm_contribution'
      AND o.`entity_id` IN
              (SELECT c.id
              FROM civicrm_contribution c
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              INNER JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              INNER JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid ROOD'
                OR
                mt.name = 'Lid SP en ROOD'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND (mandaat.status = 'RCUR' OR mandaat.status = 'FRST')
        )
    ";
    CRM_Core_DAO::executeQuery($sql);
    return true;
  }

  public function upgrade_1017() {
    $sql = "
      UPDATE civicrm_odoo_entity o
        SET o.`status` = 'OUT OF SYNC',
            o.`sync_date` = null,
            o.`action` = 'INSERT'
        WHERE
          o.`status` = 'NOT SYNCABLE'
          AND o.`entity` = 'civicrm_value_sepa_mandaat'
          AND o.`entity_id` IN
              (SELECT mandaat.id
              FROM civicrm_contribution c
              INNER JOIN `civicrm_membership_payment` mp ON c.id = mp.contribution_id
              INNER JOIN `civicrm_membership` m on mp.membership_id = m.id
              INNER JOIN `civicrm_membership_type` mt on m.membership_type_id = mt.id
              INNER JOIN civicrm_contribution_mandaat cm on c.id = cm.entity_id
              INNER JOIN civicrm_value_sepa_mandaat mandaat on cm.mandaat_id = mandaat.mandaat_nr
              where
              (
                mt.name = 'Lid ROOD'
                OR
                mt.name = 'Lid SP en ROOD'
              )
              AND MONTH(DATE(c.receive_date)) BETWEEN 4 AND 6
              AND (mandaat.status = 'RCUR' OR mandaat.status = 'FRST')
        )
    ";
    CRM_Core_DAO::executeQuery($sql);
    return true;
  }

  public function upgrade_1016() {
    /**
     * Crediteer geannuleerde bijdragen die tocg in Odoo terecht zijn gekomen
     * Deze bijdragen worden gecrediteerd in Odoo en op die manier kunnen ze terug gestort worden
     */
    $cancel_id = CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name');
    $sql = "SELECT e.odoo_id, e.id
            FROM civicrm_odoo_entity e
            LEFT JOIN civicrm_contribution c ON e.entity = 'civicrm_contribution' AND e.entity_id = c.id
            WHERE e.odoo_id IS NOT NULL and e.status = 'SYNCED' and odoo_field != 'refunded'
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

    $message = 'Odoo id: '.$odoo_invoice_id;

    if ($is_deletable) {
      $message .= ' deleted';
      $connector->unlink('account.invoice', $odoo_invoice_id);
    } else {
      $now = new DateTime();
      $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_odoo_entity where id = %1", array(1=>array($sync_entity_id, 'Integer')));
      if (!$dao->fetch()) {
        Throw new Exception('Could not fetch odoo entity from civicrm_odoo_entity');
      }
      $sync_entity = new CRM_Odoosync_Model_OdooEntity($dao);
      if ($sync_entity->getOdooField() != 'refunded') {
        $credit = new CRM_OdooContributionSync_CreditInvoice();
        $credit->setReference("Geannuleerde contributies");
        $result = $credit->credit($odoo_invoice_id, $now);
        if ($result) {
          $sync_entity->setOdooField('refunded');
        }

        $message .= ' refund id: '.$result;

        $sql = "UPDATE `civicrm_odoo_entity` SET `action` = NULL, `odoo_field` = %1, `sync_date` = NOW(), `last_error` = NULL, `last_error_date` = NULL WHERE `id` = %2";
        CRM_Core_DAO::executeQuery($sql, array(
          1 => array($sync_entity->getOdooField(), 'String'),
          2 => array($sync_entity_id, 'Positive'),
        ));

      }
    }

    $session = CRM_Core_Session::singleton();
    //$session->setStatus($message);

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

<?php

class CRM_Testsettings_Config {

  private static $singleton;

  public $financial_types = array();

  public $payment_instruments = array();

  private function __construct() {
    $params = array();
    $financialType = new CRM_Financial_DAO_FinancialType( );
    $financialType->copyValues( $params );
    $financialType->find(true);
    while($financialType->fetch()) {
      $financial_type = array();
      CRM_Core_DAO::storeValues($financialType, $financial_type);
      $this->financial_types[$financialType->id] = $financial_type;
    }

    $pi_gid = civicrm_api3('OptionGroup', 'getvalue', array('return' => 'id', 'name' => 'payment_instrument'));
    $pis = civicrm_api3('OptionValue', 'get', array('option_group_id' => $pi_gid));
    foreach($pis['values'] as $pi) {
      //we need id of option value as we get an array with the id to the option value table and not the value.
      $this->payment_instruments[$pi['value']] = $pi;
    }
  }

  /**
   * @return CRM_Testsettings_Config
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new CRM_Testsettings_Config();
    }
    return self::$singleton;
  }

}
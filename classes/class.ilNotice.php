<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

$pluginDirectory = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard')
	->includeClass('class.ilNoticeboardConfig.php');


/**
 *
 * Model of a notice object
 *
 * @author Jens Conze <jc@databay.de>
 * @version $Id$
 */
class ilNotice
{
	/**
	 * ALL categories..
	 * @const integer
	 */
	const NOTICE_CATEGORY_ALL = 0;

	/**
	 * Type of the notice: Fixed price
	 * @const integer
	 */
	const PRICE_TYPE_FIXED_PRICE = 1;
	/**
	 * Type of price: On nearest offer
	 * @const integer
	 */
	const PRICE_TYPE_ONO = 2;
	/**
	 * Type of the notice: For free
	 * @const integer
	 */
	const PRICE_TYPE_FOR_FREE = 3;

	/**
	 * Id of the notice
	 * @var integer
	 */
	protected $id = 0;
	/**
	 * Id of the notice's owner user
	 * @var integer
	 */
	protected $usr_id = 0;
	/**
	 * Title
	 * @var string
	 */
	protected $title = '';
	/**
	 * Description
	 * @var string
	 */
	protected $description = '';
	/**
	 * Image
	 * @var string
	 */
	protected $image = '';
	/**
	 * Location: Street
	 * @var string
	 */
	protected $location_street = '';
	/**
	 * Location: Zip
	 * @var string
	 */
	protected $location_zip = '';
	/**
	 * Location: City
	 * @var string
	 */
	protected $location_city = '';
	/**
	 * User: Name
	 * @var string
	 */
	protected $user_name = '';
	/**
	 * User: Phone
	 * @var string
	 */
	protected $user_phone = '';
	/**
	 * User: E-Mail
	 * @var string
	 */
	protected $user_email = '';
	/**
	 * Price
	 * @var float
	 */
	protected $price = 0;
	/**
	 * Price type
	 * @var float
	 */
	protected $price_type = 0;
	/**
	 * Create date
	 * @var integer
	 */
	protected $create_date = 0;
	/**
	 * Date of last modification
	 * @var integer
	 */
	protected $mod_date = 0;
	/**
	 * Deleted: 0 = not deleted / 1 = deleted
	 * @var integer
	 */
	protected $deleted = 0;
	/**
	 * Hidden: 0 = not hidden / 1 = hidden
	 * @var integer
	 */
	protected $hidden = 0;
	/**
	 * @var integer
	 */
	protected $category_id = 0;
	/**
	 * @var ilNoticeCategory
	 */
	protected $category;

	/**
	 * @var int $validity
	 */
	public $validity = 0;
	
	public $until_date = 0; 
	

	public $obj_id = 0;
			
	/**
	 * Constructor
	 *
	 * @param <type> $data (optional) If given, set initially the data of the notice object
	 * @access public
	 */
	public function  __construct($data = NULL) 
	{
		if (is_array($data)) 
		{
			$this->setData($data);
		}
	}

	/**
	 * Sets data of the notice object
	 *
	 * @param array $data Data to be set
	 * @access public
	 */
	public function setData(array $data) 
	{
		
		$this->setId((int)$data['nt_id']);
		$this->setUserId((int)$data['nt_usr_id']);
		$this->setTitle($data['nt_title']);
		$this->setDescription($data['nt_description']);
//		$this->setImage($data['nt_image']);
		$this->setLocationStreet($data['nt_location_street']);
		$this->setLocationZip($data['nt_location_zip']);
		$this->setLocationCity($data['nt_location_city']);
		$this->setUserName($data['user_name']);
		$this->setUserPhone($data['nt_user_phone']);
		$this->setUserEmail($data['nt_user_email']);
		$this->setPrice((float)$data['nt_price']);
		$this->setPriceType((int)$data['nt_price_type']);
		$this->setCreateDate((int)$data['nt_create_date']);
		$this->setModDate((int)$data['nt_mod_date']);
		$this->setDeleted((int)$data['nt_deleted']);
		$this->setHidden((int)$data['nt_hidden']);
		$this->setCategoryId((int)$data['nt_category_id']);#
//		$this->setValidity((int)$data['nt_validity']);
		$this->setUntilDate((int)$data['nt_until_date']);
	}

	/**
	 * Returns data of the notice object
	 *
	 * @return array
	 * @access public
	 */
	public function getData()
	{
		$data = array(
			'nt_id'				=> $this->getId(),
			'usr_id'			=> $this->getUserId(),
			'nt_title'			=> $this->getTitle(),
			'nt_description'	=> $this->getDescription(),
//			'nt_image'			=> $this->getImage(),
			'nt_location_street'=> $this->getLocationStreet(),
			'nt_location_zip'	=> $this->getLocationZip(),
			'nt_location_city'	=> $this->getLocationCity(),
			'user_name'			=> $this->getuserName(),
			'nt_user_phone'		=> $this->getUserPhone(),
			'nt_user_email'		=> $this->getUserEmail(),
			'nt_price'			=> $this->getPrice(),
			'nt_price_type'		=> $this->getPriceType(),
			'nt_create_date'	=> $this->getCreateDate(),
			'nt_mod_date'		=> $this->getModDate(),
			'nt_deleted'		=> $this->getDeleted(),
			'nt_hidden'			=> $this->getHidden(),
			'nt_category_id'	=> $this->getCategoryId(),
//			'nt_validity'		=> $this->getValidity(),
			'nt_until_date'		=> $this->getUntilDate()
			
		);
		
		return $data;
	}

	public function setId($a_id) {
		$this->id = (int)$a_id;
	}

	public function getId() {
		return (int)$this->id;
	}

	public function setUserId($a_usr_id) {
		$this->usr_id = (int)$a_usr_id;
	}

	public function getUserId() {
		return (int)$this->usr_id;
	}

	public function setTitle($a_title) {
		$this->title = $a_title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setDescription($a_description) {
		$this->description = $a_description;
	}

	public function getDescription() {
		return $this->description;
	}

	public function setImage($a_image) {
		$this->image = $a_image;
	}

	public function getImage() {
		return $this->image;
	}

	public function setLocationStreet($a_street) {
		$this->location_street = $a_street;
	}

	public function getLocationStreet() {
		return $this->location_street;
	}

	public function setLocationZip($a_zip) {
		$this->location_zip = $a_zip;
	}

	public function getLocationZip() {
		return $this->location_zip;
	}

	public function setLocationCity($a_city) {
		$this->location_city = $a_city;
	}

	public function getLocationCity() {
		return $this->location_city;
	}

	public function getLocation() {
		$location = $this->location_city;
		if ($this->location_zip != '') {
			$location = $this->location_zip.' '.$location;
		}
		if ($this->location_street != '') {
			$location = $this->location_street.', '.$location;
		}
		return $location;
	}

	public function setUserName($a_name) {
		$this->user_name = $a_name;
	}

	public function getUserName() {
		return $this->user_name;
	}

	public function setUserPhone($a_phone) {
		$this->user_phone = $a_phone;
	}

	public function getUserPhone() {
		return $this->user_phone;
	}

	public function setUserEmail($a_email) {
		$this->user_email = $a_email;
	}

	public function getUserEmail() {
		return $this->user_email;
	}

	public function setPrice($a_price) {
		$this->price = (float)$a_price;
	}

	public function getPrice() {
		return (float)$this->price;
	}

	public function setPriceType($a_price_type) {
		$this->price_type = (int)$a_price_type;
	}

	public function getPriceType() {
		return (int)$this->price_type;
	}

	public function setCreateDate($a_create_date) {
		$this->create_date = (int)$a_create_date;
	}

	public function getCreateDate() {
		return (int)$this->create_date;
	}

	public function setModDate($a_mod_date) {
		$this->mod_date = (int)$a_mod_date;
	}

	public function getModDate() {
		return (int)$this->mod_date;
	}

	public function setDeleted($a_status) {
		$this->deleted = (int)$a_status;
	}

	public function getDeleted() {
		return (int)$this->deleted;
	}

	public function isDeleted() {
		return ($this->deleted === 1 ? TRUE : FALSE);
	}

	public function setHidden($a_status) {
		$this->hidden = (int)$a_status;
	}

	public function getHidden() {
		return (int)$this->hidden;
	}

	public function isHidden() {
		return ($this->hidden === 1 ? TRUE : FALSE);
	}

	public function setCategoryId($a_type) {
		$this->category_id = (int)$a_type;
	}

	public function getCategoryId() {
		return (int)$this->category_id;
	}

	public function getCategory()
	{
		if (! isset($this->category))
		{
			$this->category = ilNoticeCategory($this->getCategoryId());
		}

		return $this->category;
	}

	/**
	 * @param integer $a_validity
	 */
	public function setValidity($a_validity)
	{
		$this->validity = (int)$a_validity;
	}
	
	public function getValidity()
	{
		return $this->validity;	
	}

	public function setUntilDate($until_date)
	{
		$this->until_date = $until_date;
	}

	public function getUntilDate()
	{
		return $this->until_date;
	}
	
	/**
	 * Returns an array of all available price types
	 *
	 * @access	public
	 * @return	array	An array of all available price types
	 * @static
	 */
	public static function getPriceTypes() {
		return array(
			self::PRICE_TYPE_FIXED_PRICE => 'fixed_price',
			self::PRICE_TYPE_ONO => 'ono',
			self::PRICE_TYPE_FOR_FREE => 'for_free'
		);
	}

	/**
	 * @param integer $obj_id
	 */
	public static function performValidityChecks($obj_id)
	{
		/** 
		 * @var $ilDB ilDB 
		 */
		global $ilDB;

		$now = time();

		$ilDB->manipulateF('UPDATE xnob_notices
		SET nt_hidden = %s 
		WHERE nt_until_date <= %s',
		array('integer', 'integer'),
		array(1, $now));
	}

	/**
	 * @param integer $notice_id
	 */
	public static function lookupUserId($a_notice_id)
	{
		global $ilDB;

		$res = $ilDB->queryF('SELECT nt_usr_id FROM xnob_notices WHERE nt_id = %s',
		array('integer'), array($a_notice_id));
		
		$row = $ilDB->fetchAssoc($res);
		
		return $row['nt_usr_id'];
	}

	/**
	 * @param integer $obj_id
	 * @param integer $delete_days
	 */
	public static function deleteHiddenPosts($obj_id, $delete_days)
	{
		global $ilDB;
		
		$timestamp = strtotime('- '.(int)$delete_days.'days');
		
		$ilDB->manipulateF('
			UPDATE xnob_notices 
			SET nt_deleted = %s
			WHERE nt_obj_id = %s 
			AND nt_mod_date < %s
			AND nt_hidden = %s',
		array('integer','integer', 'integer','integer'), array(1,$obj_id, $timestamp, 1));
	}

	/**
	 * @param integer $notice_id
	 */
	public static function isHiddenDeleted($notice_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryf('SELECT nt_hidden, nt_deleted FROM xnob_notices WHERE nt_id = %s',
		array('integer'), array($notice_id));
		
		$row = $ilDB->fetchAssoc($res);
		if($row['nt_deleted'] == 1 || $row['nt_hidden'] == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once('./Services/Repository/classes/class.ilObjectPlugin.php');

/**
 * Application class for notice board repository object.
 * @author  Jens Conze <jc@databay.de>
 */
class ilObjNoticeboard extends ilObjectPlugin
{
	/**
	 * Type of the notice board: All Postings
	 * @const integer
	 */
	const NOTICE_BOARD_TYPE_ALL = -1;
	/**
	 * Type of the notice board: General Postings
	 * @const integer
	 */
	const NOTICE_BOARD_TYPE_DEFAULT = 0;
	/**
	 * Type of the notice board: For sale
	 * @const integer
	 */
	const NOTICE_BOARD_TYPE_FOR_SALE = 1;
	/**
	 * Type of the notice board: Wanted
	 * @const integer
	 */
	const NOTICE_BOARD_TYPE_WANTED = 2;
	
	/**
	 * Path to image folder
	 * @var string
	 */
	protected $imagePath = '';
	
	/**
	 * Path to image cache folder
	 * @var string
	 */
	protected $imageCachePath = '';
	
	/**
	 * Currency
	 * @var string
	 */
	protected $currency = 'EUR';
	
	/**
	 * Validity of a notice in days
	 * @var integer
	 */
	protected $validity = 28;
	
	/**
	 * @var ilPlugin
	 */
	public $pluginObj = null;
	
	/**
	 * ilObjNoticeboard constructor.
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
		
		if(!is_dir(ilUtil::getWebspaceDir() . '/xnob') || !is_dir(ilUtil::getWebspaceDir() . '/xnob/cache'))
		{
			ilUtil::makeDirParents(ilUtil::getWebspaceDir() . '/xnob/cache');
		}
		$this->imagePath      = ilUtil::getWebspaceDir() . '/xnob';
		$this->imageCachePath = ilUtil::getWebspaceDir() . '/xnob/cache';
		
		$this->pluginObj = ilPlugin::getPluginObject('Services', 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilNoticeboardObjPermission.php');
		$this->pluginObj->includeClass('class.ilNoticeboardConfig.php');
		
		$this->validity = ilNoticeboardConfig::getSetting('validity');
	}
	
	final public function initType()
	{
		$this->setType('xnob');
	}
	
	public function doCreate()
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$query = 'INSERT INTO xnob_properties
			(pt_obj_id, pt_currency, pt_validity)
			VALUES (%s, %s, %s)';
		$ilDB->manipulateF($query, array('integer', 'text', 'integer'), array($this->getId(), $this->getCurrency(), $this->getValidity()));
		
		ilUtil::makeDirParents($this->imagePath . '/' . $this->getId());
	}
	
	public function doRead()
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$query = 'SELECT *
			FROM xnob_properties
			WHERE pt_obj_id = %s';
		$res   = $ilDB->queryF($query, array('integer'), array($this->getId()));
		$row   = $ilDB->fetchAssoc($res);
		
		$this->setCurrency($row['pt_currency']);
	}
	
	public function doUpdate()
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$query = 'UPDATE xnob_properties
			SET pt_currency = %s,
			pt_validity = %s
			WHERE pt_obj_id = %s';
		$ilDB->manipulateF($query, array('text', 'integer', 'integer'), array($this->getCurrency(), $this->getId(), $this->getValidity()));
	}
	
	public function doDelete()
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		// delete categories
		$ilDB->manipulateF('DELETE FROM xnob_categories WHERE obj_id = %s',
			array('integer'), array($this->getId()));
		
		// delete permissions
		$ilDB->manipulateF('DELETE FROM xnob_cat_permissions WHERE obj_id = %s',
			array('integer'), array($this->getId()));
		
		// delete notices
		$ilDB->manipulateF('DELETE FROM xnob_notices WHERE nt_obj_id = %s',
			array('integer'), array($this->getId()));
		
		// delete properties
		$ilDB->manipulateF('DELETE FROM xnob_properties WHERE pt_obj_id = %s',
			array('integer'), array($this->getId()));
		
		// delete title images
		ilUtil::delDir($this->imagePath . '/' . $this->getId());
		
		// delete additional multiple images
		$this->pluginObj->includeClass('class.ilObjNoticeImage.php');
		ilObjNoticeImage::deleteFilesByObjId($this->getId());
	}
	
	/**
	 * @param $a_target_id
	 * @param $a_copy_id
	 * @param $new_obj ilObjNoticeboard
	 */
	public function doClone($a_target_id, $a_copy_id, $new_obj)
	{
		$new_obj->setCurrency($this->getCurrency());
		$new_obj->setValidity($this->getValidity());
		$new_obj->update();
	}
	
	/**
	 * Set currency
	 * @param string $currency
	 */
	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}
	
	/**
	 * Returns currency
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->currency;
	}
	
	/**
	 * Set validity of a notice in days
	 * @param integer $validity
	 */
	public function setValidity($validity)
	{
		$this->validity = (int)$validity;
	}
	
	/**
	 * Returns validity of a notice in days
	 * @return integer
	 */
	public function getValidity()
	{
		return $this->validity;
	}
	
	/**
	 * @param int $a_days
	 * @return int
	 */
	public function getTimeForValidityCheck($a_days = 0)
	{
		if($a_days > 0)
		{
			return (time() - (60 * 60 * 24 * $a_days));
		}
		else
		{
			return (time() - (60 * 60 * 24 * $this->getValidity()));
		}
	}
	
	/**
	 * @param        $a_date
	 * @param string $a_notice_validity
	 * @return bool
	 */
	public function isNoticeExpired($a_date, $a_notice_validity = '')
	{
		if($a_notice_validity == '')
		{
			return ($a_date <= (time() - (60 * 60 * 24 * $this->getValidity())));
		}
		else
		{
			return ($a_date <= (time() - (60 * 60 * 24 * $a_notice_validity)));
		}
	}
	
	/**
	 * Returns path to image folder
	 * @return string
	 */
	public function getImagePath()
	{
		return $this->imagePath . '/' . $this->getId();
	}
	
	/**
	 * Returns path to image cachefolder
	 * @return string
	 */
	public function getImageCachePath()
	{
		return $this->imageCachePath;
	}
}

<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once('./Services/Repository/classes/class.ilObjectPlugin.php');

/**
 * Application class for notice board repository object.
 *
 * @author Jens Conze <jc@databay.de>
 * @version $Id$
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
	 *
	 * @var string
	 */
	protected $imagePath = '';

	/**
	 * Path to image cache folder
	 *
	 * @var string
	 */
	protected $imageCachePath = '';

	/**
	 * Currency
	 *
	 * @var string
	 */
	protected $currency = 'EUR';

	/**
	 * Validity of a notice in days
	 *
	 * @var integer
	 */
	protected $validity = 28;

	public $pluginObj = null;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
		
		if(! is_dir(ilUtil::getWebspaceDir().'/xnob') || ! is_dir(ilUtil::getWebspaceDir().'/xnob/cache'))
		{
			ilUtil::makeDirParents(ilUtil::getWebspaceDir().'/xnob/cache');
		}
		$this->imagePath = ilUtil::getWebspaceDir().'/xnob';
		$this->imageCachePath = ilUtil::getWebspaceDir().'/xnob/cache';

		$this->pluginObj = ilPlugin::getPluginObject('Services', 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilObjPermission.php');
		$this->pluginObj->includeClass('class.ilNoticeboardConfig.php');
		
		$this->validity = ilNoticeboardConfig::getSetting('validity');
		
	}

	/**
	 * Get type.
	 * The initType() method must set the same ID as the plugin ID.
	 *
	 * @access	public
	 */
	final public function initType()
	{
		$this->setType('xnob');
	}
	
	/**
	 * Create object
	 * This method is called, when a new repository object is created.
	 * The Object-ID of the new object can be obtained by $this->getId().
	 * You can store any properties of your object that you need.
	 * It is also possible to use multiple tables.
	 * Standard properites like title and description are handled by the parent classes.
	 *
	 * @access	public
	 */
	public function doCreate()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$query = 'INSERT INTO xnob_properties
			(pt_obj_id, pt_currency, pt_validity)
			VALUES (%s, %s, %s)';
		$ilDB->manipulateF($query, array('integer', 'text', 'integer'), array($this->getId(), $this->getCurrency(), $this->getValidity()));

		ilUtil::makeDirParents($this->imagePath.'/'.$this->getId());
	}
	
	/**
	 * Read data from db
	 * This method is called when an instance of a repository object is created and an existing Reference-ID is provided to the constructor.
	 * All you need to do is to read the properties of your object from the database and to call the corresponding set-methods.
	 *
	 * @access	public
	 */
	public function doRead()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$query = 'SELECT *
			FROM xnob_properties
			WHERE pt_obj_id = %s';
		$res = $ilDB->queryF($query, array('integer'), array($this->getId()));
		$row = $ilDB->fetchAssoc($res);

		$this->setCurrency($row['pt_currency']);
	}
	
	/**
	 * Update data
	 * This method is called, when an existing object is updated.
	 *
	 * @access	public
	 */
	public function doUpdate()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$query = 'UPDATE xnob_properties
			SET pt_currency = %s,
			pt_validity = %s
			WHERE pt_obj_id = %s';
		$ilDB->manipulateF($query, array('text', 'integer', 'integer'), array($this->getCurrency(), $this->getId(), $this->getValidity()));
	}
	
	/**
	 * Delete data from db
	 * This method is called, when a repository object is finally deleted from the system.
	 * It is not called if an object is moved to the trash.
	 *
	 * @access	public
	 */
	public function doDelete()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		
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
		ilUtil::delDir($this->imagePath.'/'.$this->getId());
		
		// delete additional multiple images
		$this->pluginObj->includeClass('class.ilObjNoticeImage.php');
		ilObjNoticeImage::deleteFilesByObjId($this->getId());
	}
	
	/**
	 * Do Cloning
	 * This method is called, when a repository object is copied.
	 *
	 * @access	public
	 */
	public function doClone($a_target_id,$a_copy_id,$new_obj)
	{
		$new_obj->setCurrency($this->getCurrency());
		$new_obj->setValidity($this->getValidity());
		$new_obj->update();
	}

	/**
	 * Set currency
	 *
	 * @param string $currency
	 * @access	public
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
	}

	/**
	 * Returns currency
	 * 
	 * @return string
	 * @access	public
	 */
	public function getCurrency() {
		return $this->currency;
	}

	/**
	 * Set validity of a notice in days
	 *
	 * @param integer $validity
	 * @access	public
	 */
	public function setValidity($validity) {
		$this->validity = (int)$validity;
	}

	/**
	 * Returns validity of a notice in days
	 *
	 * @return integer
	 * @access	public
	 */
	public function getValidity() {
		return $this->validity;
	}

	/**
	 * Returns time for validity check
	 *
	 * @return integer
	 * @access	public
	 */
	public function getTimeForValidityCheck($a_days = 0) 
	{
		if($a_days > 0)
		{
			return (time() - (60*60*24*$a_days));
		}
		else
		{	
			return (time() - (60*60*24*$this->getValidity()));
		}
	}

	/**
	 * Returns whether a notice is expired or not
	 *
	 * @param integer $a_date Modification date of the notice (unix-timestamp)
	 * @return bool
	 * @access	public
	 */
	public function isNoticeExpired($a_date, $a_notice_validity = '') 
	{
		if($a_notice_validity ==  '')
		{
			return ($a_date <= (time() - (60*60*24*$this->getValidity())));	
		}
		else
		{
			return ($a_date <= (time() - (60*60*24* $a_notice_validity)));
		}
	}

	/**
	 * Returns path to image folder
	 *
	 * @return string
	 * @access	public
	 */
	public function getImagePath() {
		return $this->imagePath.'/'.$this->getId();
	}

	/**
	 * Returns path to image cachefolder
	 *
	 * @return string
	 * @access	public
	 */
	public function getImageCachePath() {
		return $this->imageCachePath;
	}
}
?>

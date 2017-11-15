<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

/**
 * Class ilNoticeCategory
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */
class ilNoticeCategory
{
	/**
	 * ALL categories..
	 * @const integer
	 */
	const NOTICE_CATEGORY_ALL = 0;
	
	/**
	 * @var int
	 */
	public $category_id = 0;
	/**
	 * @var int
	 */
	public $price_enabled = 0;
	/**
	 * @var string
	 */
	public $category_title = '';
	/**
	 * @var string
	 */
	public $category_description = '';
	/**
	 * @var int
	 */
	public $obj_id = 0;
	
	/**
	 * @param $category_description
	 */
	public function setCategoryDescription($category_description)
	{
		$this->category_description = $category_description;
	}
	
	/**
	 * @return string
	 */
	public function getCategoryDescription()
	{
		return $this->category_description;
	}
	
	/**
	 * @param $category_id
	 */
	public function setCategoryId($category_id)
	{
		$this->category_id = $category_id;
	}
	
	/**
	 * @return int
	 */
	public function getCategoryId()
	{
		return $this->category_id;
	}
	
	/**
	 * @param $category_title
	 */
	public function setCategoryTitle($category_title)
	{
		$this->category_title = $category_title;
	}
	
	/**
	 * @return string
	 */
	public function getCategoryTitle()
	{
		return $this->category_title;
	}
	
	/**
	 * @param $obj_id
	 */
	public function setObjId($obj_id)
	{
		$this->obj_id = $obj_id;
	}
	
	/**
	 * @return int
	 */
	public function getObjId()
	{
		return $this->obj_id;
	}
	
	/**
	 * @param $price_enabled
	 */
	public function setPriceEnabled($price_enabled)
	{
		$this->price_enabled = $price_enabled;
	}
	
	/**
	 * @return int
	 */
	public function getPriceEnabled()
	{
		return $this->price_enabled;
	}
	
	/**
	 * ilNoticeCategory constructor.
	 * @param int $category_id
	 */
	public function __construct($category_id = 0)
	{
		if((int)$category_id > 0)
		{
			$this->readCategory($category_id);
		}
	}
	
	/**
	 * @param int $category_id
	 */
	public function readCategory($category_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryF('SELECT * FROM xnob_categories WHERE category_id = %s',
			array('integer'), array($category_id));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$this->setObjId($row['obj_id']);
			$this->setCategoryId($row['category_id']);
			$this->setPriceEnabled($row['price_enabled']);
			$this->setCategoryTitle($row['category_title']);
			$this->setCategoryDescription($row['category_description']);
		}
	}
	
	/**
	 * @return int
	 */
	public function insertCategory()
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$next_id = $ilDB->nextId('xnob_categories');
		
		$ilDB->insert('xnob_categories',
			array(
				'category_id'          => array('integer', $next_id),
				'price_enabled'        => array('integer', $this->getPriceEnabled()),
				'category_title'       => array('text', $this->getCategoryTitle()),
				'category_description' => array('text', $this->getCategoryDescription()),
				'obj_id'               => array('integer', $this->getObjId())
			));
		
		return $next_id;
	}
	
	public function updateCategory()
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$ilDB->update('xnob_categories',
			array(
				'price_enabled'        => array('integer', $this->getPriceEnabled()),
				'category_title'       => array('text', $this->getCategoryTitle()),
				'category_description' => array('text', $this->getCategoryDescription()),
				'obj_id'               => array('integer', $this->getObjId())),
			array(
				'category_id' => array('integer', $this->getCategoryId())
			));
	}
	
	/**
	 * @param array $category_ids
	 */
	public function deleteCategories($category_ids)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		//delete images first!
		$pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$pluginObj->includeClass('class.ilObjNoticeImage.php');
		ilObjNoticeImage::deleteFilesByCatId($category_ids);
		
		// delete notices
		$ilDB->manipulate('DELETE FROM xnob_notices WHERE ' . $ilDB->in('nt_category_id', $category_ids, false, 'integer'));
		
		// delete permissions
		$ilDB->manipulate('DELETE FROM xnob_cat_permissions WHERE ' . $ilDB->in('category_id', $category_ids, false, 'integer'));
		
		// delete categories
		$ilDB->manipulate('DELETE FROM xnob_categories WHERE ' . $ilDB->in('category_id', $category_ids, false, 'integer'));
	}
	
	/**
	 * @param int $category_id
	 * @return bool
	 */
	public static function isPriceEnabled($category_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryF('SELECT price_enabled FROM xnob_categories WHERE category_id = %s',
			array('integer'), array($category_id));
		
		$row = $ilDB->fetchAssoc($res);
		return (bool)$row['price_enabled'];
	}
	
	/**
	 * @param int $obj_id
	 * @param int $cat_id
	 * @return bool
	 */
	public static function anyCategoryWithPrice($obj_id, $cat_id = 0)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		if($cat_id > 0)
		{
			$res = $ilDB->queryF('
				SELECT COUNT(nt_price) cnt 
				FROM xnob_categories xc
				INNER JOIN xnob_notices xn
				WHERE (
					price_enabled = %s
					AND xn.nt_price > %s
					AND nt_obj_id = %s
					AND nt_category_id = %s)
				AND nt_category_id = category_id',
				array('integer', 'integer', 'integer', 'integer'), array(1, 0, $obj_id, $cat_id));
		}
		else
		{ // check complete noticeboard
			$res = $ilDB->queryF('
				SELECT COUNT(nt_price) cnt 
				FROM xnob_categories xc
				INNER JOIN xnob_notices xn
				WHERE (
					price_enabled = %s
					AND xn.nt_price > %s
					AND nt_obj_id = %s
					)
				AND nt_category_id = category_id',
				array('integer', 'integer', 'integer'), array(1, 0, $obj_id));
		}
		$row = $ilDB->fetchAssoc($res);
		
		if($row['cnt'] > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @return array
	 */
	public function convertToArray()
	{
		$data['category_id']          = $this->getCategoryId();
		$data['category_title']       = $this->getCategoryTitle();
		$data['category_description'] = $this->getCategoryDescription();
		$data['obj_id']               = $this->getObjId();
		$data['price_enabled']        = $this->getPriceEnabled();
		
		return $data;
	}
	
	/**
	 * @param int $category_id
	 * @return string
	 */
	public static function lookupTitle($category_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryF('SELECT category_title FROM xnob_categories WHERE category_id = %s',
			array('integer'), array((int)$category_id));
		
		$row = $ilDB->fetchAssoc($res);
		
		return $row['category_title'] ? $row['category_title'] : '';
	}
	
	/**
	 * @param int $obj_id
	 * @return int
	 */
	public static function countCategoriesByObjId($obj_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryf('SELECT COUNT(*) cnt FROM xnob_categories WHERE obj_id = %s',
			array('integer'), array((int)$obj_id));
		
		$row = $ilDB->fetchAssoc($res);
		return $row['cnt'];
	}
	
	/**
	 * @param int     $boardId
	 * @param bool $assoc
	 * @return array
	 */
	static public function getList($boardId, $assoc = false)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$list = array();
		$res  = $ilDB->queryF('
			SELECT * FROM xnob_categories
			WHERE obj_id = %s',
			array('integer'), array($boardId));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			if(!$assoc)
			{
				$category                       = new self;
				$category->category_id          = $row['category_id'];
				$category->obj_id               = $row['obj_id'];
				$category->category_title       = $row['category_title'];
				$category->category_description = $row['category_description'];
				$category->price_enabled        = $row['price_enabled'];
			}
			else
			{
				$category = $row;
			}	
				
			
			$list[] = $category;
		}
		
		return $list;
	}
	
	/**
	 * @param integer $obj_id
	 * @return array
	 */
	static public function getPairs($obj_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$pairs = array();
		$res   = $ilDB->queryF('
			SELECT category_id, category_title
			FROM xnob_categories
			WHERE obj_id = %s', array('integer'), array($obj_id));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$pairs[$row['category_id']] = $row['category_title'];
		}
		
		return $pairs;
	}
	
	/**
	 * @param int $obj_id
	 * @return array
	 */
	public static function getCatIdsByObjId($obj_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryF('SELECT category_id FROM xnob_categories WHERE obj_id = %s',
			array('integer'), array($obj_id));
		
		$ids = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$ids[] = $row['category_id'];
		}
		
		return $ids;
	}
}
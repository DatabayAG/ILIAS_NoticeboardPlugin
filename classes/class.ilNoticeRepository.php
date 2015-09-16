<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

/**
 * Repository for notice objects
 *
 * @author Jens Conze <jc@databay.de>
 * @version $Id$
 */
class ilNoticeRepository {

	/**
	 * Notice board object
	 * @var ilObjNoticeboard
	 */
	protected $object = NULL;

	public $pluginObj = null;
	
	
	/**
	 * Constructor
	 *
	 * @param ilObjNoticeboard $a_object	Notice board object
	 * @access public
	 */
	public function __construct(ilObjNoticeboard $a_object) 
	{
		$this->object = $a_object;

		$this->pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard'); 
		$this->pluginObj->includeClass('class.ilNoticeRepositoryImage.php');
		$this->pluginObj->includeClass('class.ilNoticeboardConfig.php');
	}

	/*
	 * Find all current notices.
	 * Returns list of notice objects.
	 * 
	 * @param bool   $use_admin_filter
	 * @param string $hidden_status
	 * @return array
	 */
	public function findCurrent($use_admin_filter = false, $hidden_status='both') 
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		if($use_admin_filter == true)
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
				FROM xnob_notices
				INNER JOIN usr_data ON usr_id = nt_usr_id
				WHERE nt_obj_id = %s
				AND nt_deleted = 0';
			$types = array('integer');
			$data = array($this->object->getId());

			if($hidden_status !== 'both')
			{
				$query .= ' AND nt_hidden = %s ';
				$types[] = 'integer';
				$data[] =  (int)$hidden_status;
			}
			
			$res = $ilDB->queryF($query, $types, $data);
		}
		else
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
				FROM xnob_notices
				INNER JOIN usr_data ON usr_id = nt_usr_id
				WHERE nt_obj_id = %s
				AND nt_deleted = 0
				AND nt_hidden = 0
				AND nt_until_date > %s';
			$res = $ilDB->queryF($query, array('integer', 'integer'), array($this->object->getId(), time()));
		}
		
		$rows = array();
		while($row = $ilDB->fetchAssoc($res)) 
		{
			$rows[] = new ilNotice($row);
		}

		return $rows;
	}


	/**
	 * Find all current notices of a notice board by type.
	 * Returns list of notice objects.
	 * 
	 * @param        $a_cat_id
	 * @param bool   $use_admin_filter
	 * @param string $hidden_status
	 * @return array
	 */
	public function findCurrentByCategory($a_cat_id, $use_admin_filter = false, $hidden_status = 'both')
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		
		if($use_admin_filter == true)
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_category_id = %s
			AND nt_deleted = 0';

			$types = array('integer', 'integer');
			$data = array($this->object->getId(), $a_cat_id);
			
			if($hidden_status !== 'both')
			{
				$query .= ' AND nt_hidden = %s ';
				$types[] = 'integer';
				$data[] =  (int)$hidden_status;				
			}

			$res = $ilDB->queryF($query, $types, $data);
		}
		else
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_category_id = %s
			AND nt_deleted = 0
			AND nt_hidden = %s
			AND nt_until_date > %s';
			$res = $ilDB->queryF($query,
				array('integer', 'integer', 'integer', 'integer'),
				array($this->object->getId(), $a_cat_id, 0, time()));
		}

		$rows = array();
		while($row = $ilDB->fetchAssoc($res)) 
		{
			$rows[] = new ilNotice($row);
		}

		return $rows;
	}

	/*
	 * Find a current notice by its id.
	 * Returns notice object.
	 *
	 * @param      $a_id
	 * @param bool $is_admin
	 * @return bool|ilNotice
	 */
	public function findCurrentById($a_id, $is_admin = false)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		if($is_admin == 1)
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND  nt_id = %s
			AND nt_until_date > %s';
			$res = $ilDB->queryF($query, array('integer', 'integer', 'integer'), array($this->object->getId(), $a_id, time()));
		}
		else
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND  nt_id = %s
			AND nt_deleted = 0
			AND nt_hidden = 0
			AND nt_until_date > %s';
			$res = $ilDB->queryF($query, array('integer', 'integer', 'integer'), array($this->object->getId(), $a_id, time()));
		}
		
		$row = $ilDB->fetchAssoc($res);
	
			if(count($row) == 0)
		{
			return false;
		}
		else
		{
			return new ilNotice($row);	
		}
	}

	/*
	 * Find all notices of a user by type of notice.
	 * Returns list of notice objects.
	 *
	 * @param        $a_usr_id
	 * @param int    $a_cat_id
	 * @param string $hidden_status
	 * @return array
	 */
	public function findByUserAndCategory($a_usr_id, $a_cat_id = 0, $hidden_status = 'both')
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		if($a_cat_id == 0)
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_usr_id = %s
			AND nt_deleted = %s ';
			
			$types = array('integer', 'integer', 'integer');
			$data = array($this->object->getId(), $a_usr_id, 0);
		}
		else
		{
			$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_usr_id = %s
			AND nt_category_id = %s
			AND nt_deleted = %s';
			$types = array('integer', 'integer', 'integer','integer'); 
			$data = array($this->object->getId(), $a_usr_id, $a_cat_id, 0);
			
		}

		if($hidden_status !== 'both')
		{
			$query .= ' AND nt_hidden = %s ';
			$types[] = 'integer';
			$data[] =  (int)$hidden_status;
		}

		$res = $ilDB->queryF($query, $types, $data);

		$rows = array();
		while($row = $ilDB->fetchAssoc($res)) 
		{
			$rows[] = new ilNotice($row);
		}

		return $rows;
	}

	/*
	 * Find a notice by its id.
	 * Returns a notice object.
	 *
	 * @param integer $a_id	Id of the notice
	 * @return object ilNotice
	 * @access public
	 */
	public function findById($a_id) 
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$query = 'SELECT xnob_notices.*, CONCAT(usr_data.firstname, " ", usr_data.lastname) user_name
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_id = %s
			AND nt_deleted = 0';
		$res = $ilDB->queryF($query, array('integer', 'integer'), array($this->object->getId(), $a_id));
		$row = $ilDB->fetchAssoc($res);

		return new ilNotice($row);
	}

	/*
	 * Update notice.
	 *
	 * @param object $a_notice	ilNotice
	 * @access public
	 */
	public function update($a_notice) 
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		
		$ilDB->update('xnob_notices',
		array(
			'nt_obj_id'          => array('integer', $this->object->getId()),
			'nt_usr_id'          => array('integer', $a_notice->getUserId()),
			'nt_category_id'     => array('integer', $a_notice->getCategoryId()),
			'nt_title'           => array('text', $a_notice->getTitle()),
			'nt_description'     => array('text', $a_notice->getDescription()),
//			'nt_image'           => array('text', $a_notice->getImage()),
			'nt_price'           => array('float', $a_notice->getPrice()),
			'nt_price_type'      => array('integer', $a_notice->getPriceType()),
			'nt_location_street' => array('text', $a_notice->getLocationStreet()),
			'nt_location_zip'    => array('text', $a_notice->getLocationZip()),
			'nt_location_city'   => array('text', $a_notice->getLocationCity()),
			'nt_user_phone '     => array('text', $a_notice->getUserPhone()),
			'nt_user_email'      => array('text', $a_notice->getUserEmail()),
			'nt_create_date'     => array('integer', $a_notice->getCreateDate()),
			'nt_mod_date'        => array('integer', $a_notice->getModDate()),
			'nt_deleted'         => array('integer', $a_notice->getDeleted()),
			'nt_hidden'          => array('integer', $a_notice->getHidden()),
			'nt_until_date'		 => array('integer', $a_notice->getUntilDate()) 
		),
		array('nt_id' => array('integer',$a_notice->getId())));
	}

	/*
	 * Update notice.
	 *
	 * @param object $a_notice	ilNotice
	 * @access public
	 */

	public function add($a_notice) 
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$a_notice->setId($ilDB->nextId('xnob_notices'));
		$ilDB->insert('xnob_notices',
		array(
			'nt_id'              => array('integer', $a_notice->getId()),
			'nt_obj_id'          => array('integer', $this->object->getId()),
			'nt_usr_id'          => array('integer', $a_notice->getUserId()),
			'nt_category_id'     => array('integer', $a_notice->getCategoryId()),
			'nt_title'           => array('text', $a_notice->getTitle()),
			'nt_description'     => array('text', $a_notice->getDescription()),
//			'nt_image'           => array('text', $a_notice->getImage()),
			'nt_price'           => array('text', $a_notice->getPrice()),
			'nt_price_type'      => array('float', $a_notice->getPriceType()),
			'nt_location_street' => array('text', $a_notice->getLocationStreet()),
			'nt_location_zip'    => array('text', $a_notice->getLocationZip()),
			'nt_location_city'   => array('text', $a_notice->getLocationCity()),
			'nt_user_phone'      => array('text', $a_notice->getUserPhone()),
			'nt_user_email'      => array('text', $a_notice->getUserEmail()),
			'nt_create_date'     => array('integer', $a_notice->getCreateDate()),
			'nt_mod_date'        => array('integer', $a_notice->getModDate()),
			'nt_deleted'         => array('integer', $a_notice->getDeleted()),
			'nt_hidden'          => array('integer', $a_notice->getHidden()),
			'nt_until_date'	 	 => array('integer', $a_notice->getUntilDate())
		));
	}

	/*
	 * Remove notice.
	 *
	 * @param object $a_notice	ilNotice
	 * @access public
	 */
	public function remove($a_notice) 
	{
		$this->removeById($a_notice->getId());
	}

	/*
	 * Remove notice by its id.
	 *
	 * @param integer $a_id	Id of the notice
	 * @access public
	 */
	public function removeById($a_id) 
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$query = 'UPDATE xnob_notices
			SET nt_deleted = 1
			WHERE nt_id = %s';
		$ilDB->manipulateF($query, array('integer'), array($a_id));
	}


	/*
	 * Count all current notices of a notice board by type.
	 * Returns a number.
	 *
	 * @param integer $a_type	Type of the notice board: ilObjNoticeboard::NOTICE_BOARD_TYPE_...
	 * @return integer
	 * @access public
	 */
	public function countCurrentByCategory($a_cat_id)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$query = 'SELECT COUNT(xnob_notices.nt_id) cnt
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_category_id = %s
			AND nt_deleted = 0
			AND nt_hidden = 0
			AND nt_until_date > %s';
		$res = $ilDB->queryF($query, array('integer', 'integer', 'integer'), array($this->object->getId(), $a_cat_id, time()));
		$row = $ilDB->fetchAssoc($res);
		return $row['cnt'];
	}

	/*
	 * Count all current notices.
	 * Returns a number.
	 *
	 * @return integer
	 * @access public
	 */
	public function countCurrent() 
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$query = 'SELECT COUNT(xnob_notices.nt_id) cnt
			FROM xnob_notices
			INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE nt_obj_id = %s
			AND nt_deleted = 0
			AND nt_hidden = 0
			AND nt_until_date > %s';
		$res = $ilDB->queryF($query, array('integer', 'integer'), array($this->object->getId(), time()));
		$row = $ilDB->fetchAssoc($res);
		return $row['cnt'];
	}

	/*
	 * Count all notices of a user by type of notice board.
	 * Returns a number.
	 *
	 * @param integer $a_type	Type of the notice: ilObjNoticeboard::NOTICE_BOARD_TYPE_...
	 * @return integer
	 * @access public
	 */
	public function countByUserAndCategory($a_usr_id, $a_cat_id = 0, $count_own_entries = false)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$userSQL = ""; 
		$catSQL = ""; 

		if($count_own_entries == true)
		{
			$userSQL = " AND nt_usr_id = " . $ilDB->quote($a_usr_id, 'integer');
		}
			
		if($a_cat_id > 0)
		{
			$catSQL = " AND nt_category_id = %s ";
		}

		$query = "
			SELECT
				COUNT(xnob_notices.nt_id) cnt
			FROM  
				xnob_notices
				INNER JOIN usr_data ON usr_id = nt_usr_id
			WHERE
				nt_obj_id = %s ".
				$userSQL ." ".
				$catSQL."
				AND nt_deleted = 0
				AND nt_hidden = 0
				AND nt_until_date > %s";
				
			$res = $ilDB->queryF($query, 	
			array('integer', 'integer', 'integer'),
			array($this->object->getId(), $a_cat_id, time())
		);
		$row = $ilDB->fetchAssoc($res);
		
		return $row['cnt'];
	}
	
	public function setHiddenById($a_nt_id)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		
		$ilDB->update('xnob_notices',
		array('nt_hidden' 	=> array('integer', 1)),
		array('nt_id'		=> array('integer', (int)$a_nt_id)));
	}

	/**
	 * @param     $a_filename
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @return bool|string
	 */
	public static function createPreviewImage($a_filename, $maxWidth = 0 , $maxHeight = 0)
	{
		$pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$pluginObj->includeClass('class.ilNoticeRepositoryImage.php');
		$pluginObj->includeClass('class.ilNoticeboardConfig.php');
		
		if($maxWidth <= 0 || $maxHeight <= 0)
		{
			$maxWidth = ilNoticeboardConfig::getSetting('img_preview_width');
			$maxHeight = ilNoticeboardConfig::getSetting('img_preview_height');
		}

		$image = '';
		$ok = false;

		if(file_exists($a_filename))
		{
			if(!is_dir(ilUtil::getWebspaceDir().'/xnob/img_preview'))
			{

				if(mkdir(ilUtil::getWebspaceDir().'/xnob/img_preview'))
				{
					if(chmod(ilUtil::getWebspaceDir().'/xnob/img_preview',0755))
					{
						$ok = true;
					}
				}
			}
			else
			{
				$ok = true;
			}
			
			if(!$ok)
			{
				// something went wrong ... could not create directories ..
				return false;
			}

			$dir          = dirname($a_filename);
			$file         = basename($a_filename);
			$new_filename = $dir . '/img_preview/' . $file;

			if(!file_exists($new_filename))
			{
				$resize = new ilNoticeRepositoryImage();
				$resize->load($a_filename);

				if( ($maxWidth > 0  && $maxHeight > 0)
					&& ($maxWidth < ilNoticeboardConfig::getSetting('img_preview_width') && $maxHeight < ilNoticeboardConfig::getSetting('img_preview_height')))
				{
					$resize->resize($maxWidth, $maxHeight);
				}
				else
				{
					if($resize->getWidth() >= $resize->getHeight())
					{
						$resize->resizeToWidth($maxWidth);
					}
					else
					{
						$resize->resizeToHeight($maxHeight);
					}
				}
				$resize->save($new_filename );
				chmod($new_filename, 0775);
			}

			if(file_exists($new_filename))
			{
				$image = $new_filename;
			}
		}
		return $image;
	}

	/**
	 * @param     $a_filename
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @return bool|string
	 */
	public static function createThumbnail($a_filename, $maxWidth = 0 , $maxHeight = 0)
	{
		$pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$pluginObj->includeClass('class.ilNoticeRepositoryImage.php');
		$pluginObj->includeClass('class.ilNoticeboardConfig.php');

		if($maxWidth <= 0 || $maxHeight <= 0)
		{
			$maxWidth = ilNoticeboardConfig::getSetting('img_thumbnail_width');
			$maxHeight = ilNoticeboardConfig::getSetting('img_thumbnail_height');
		}
		
		$image = '';
		$ok = false;

		if(file_exists($a_filename))
		{
			if(!is_dir(ilUtil::getWebspaceDir().'/xnob/img_thumbnail'))
			{
				if(mkdir(ilUtil::getWebspaceDir().'/xnob/img_thumbnail'))
				{
					if(chmod(ilUtil::getWebspaceDir().'/xnob/img_thumbnail',0755))
					{
						$ok = true;
					}
				}
			}
			else
			{
				$ok = true;
			}

			if(!$ok)
			{
				// something went wrong ... could not create directories ..
				return false;
			}

			$dir          = dirname($a_filename);
			$file         = basename($a_filename);
			$new_filename = $dir . '/img_thumbnail/' . $file;

			if(!file_exists($new_filename))
			{
				$resize = new ilNoticeRepositoryImage();
				$resize->load($a_filename);

				if( ($maxWidth > 0  && $maxHeight > 0)
					&& ($maxWidth < ilNoticeboardConfig::getSetting('img_preview_width') && $maxHeight < ilNoticeboardConfig::getSetting('img_preview_height')))
				{
					$resize->resize($maxWidth, $maxHeight);
				}
				else
				{
					if($resize->getWidth() >= $resize->getHeight())
					{
						$resize->resizeToWidth($maxWidth);
					}
					else
					{
						$resize->resizeToHeight($maxHeight);
					}
				}
				$resize->save($new_filename);
				chmod($new_filename, 0775);
			}

			if(file_exists($new_filename))
			{
				$image = $new_filename;
			}
		}
		return $image;
	}

	public static function existsPreviewImage($a_filename)
	{
		if(file_exists($a_filename))
		{
			$new_filename = self::getPreviewImage($a_filename);

			if(!file_exists($new_filename))
			{
				return false;
			}
		}
		else
		{
			return true;
		}
		return false;
	}
	
	public static function getPreviewImage($a_filename)
	{
		if(file_exists($a_filename))
		{
			$dir          = dirname($a_filename);
			$file         = explode('.', basename($a_filename));
			$preview_filename = $dir . '/img_preview/' .$file;
			
			return $preview_filename;
		}
		else 
		{
			return '';
		}
	}
}
?>

<?php

/**
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */
class ilObjNoticeImage
{
	/**
	 *
	 */
	const IMAGE = 'img';
	/**
	 *
	 */
	const DOCUMENT = 'doc';
	
	/**
	 * @var int
	 */
	private $image_id = 0;
	/**
	 * @var int
	 */
	private $obj_id = 0;
	/**
	 * @var int
	 */
	private $category_id = 0;
	/**
	 * @var int
	 */
	private $notice_id = 0;
	/**
	 * @var null
	 */
	private $filemame = NULL;
	/**
	 * @var int
	 */
	private $is_selected = 0;
	/**
	 * @var string
	 */
	private $file_type = 'img';
	
	/**
	 * @var array
	 */
	public $img_files = array();
	/**
	 * @var array
	 */
	public $doc_files = array();
	/**
	 * @var null
	 */
	public $pluginObj = NULL;
	
	/**
	 * @var ilDB
	 */
	public $db;
	
	/**
	 * ilObjNoticeImage constructor.
	 * @param int $notice_id
	 */
	public function __construct($notice_id = 0)
	{
		global $DIC;
		$this->db = $DIC->database();
		
		$this->pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilFileDataNoticeboard.php');
		
		if($notice_id > 0)
		{
			$this->setNoticeId($notice_id);
			$this->getFilesOfNotice();
		}
	}
	
	/**
	 * @param int $category_id
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
	 * @param $filemame
	 */
	public function setFilemame($filemame)
	{
		$this->filemame = $filemame;
	}
	
	/**
	 * @return null
	 */
	public function getFilemame()
	{
		return $this->filemame;
	}
	
	/**
	 * @param $image_id
	 */
	public function setImageId($image_id)
	{
		$this->image_id = $image_id;
	}
	
	/**
	 * @return int
	 */
	public function getImageId()
	{
		return $this->image_id;
	}
	
	/**
	 * @param $is_selected
	 */
	public function setIsSelected($is_selected)
	{
		$this->is_selected = $is_selected;
	}
	
	/**
	 * @return int
	 */
	public function getIsSelected()
	{
		return $this->is_selected;
	}
	
	/**
	 * @param $notice_id
	 */
	public function setNoticeId($notice_id)
	{
		$this->notice_id = $notice_id;
	}
	
	/**
	 * @return int
	 */
	public function getNoticeId()
	{
		return $this->notice_id;
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
	 * @param $file_type
	 */
	public function setFileType($file_type)
	{
		$this->file_type = $file_type;
	}
	
	/**
	 * @return string
	 */
	public function getFileType()
	{
		return $this->file_type;
	}
	
	/**
	 * @return array
	 */
	public function getImgFiles()
	{
		return $this->img_files;
	}
	
	/**
	 * @return array
	 */
	public function getDocFiles()
	{
		return $this->doc_files;
	}
	
	/**
	 * @return array
	 */
	public function getAllFiles()
	{
		$all_files = array_merge($this->getImgFiles(), $this->getDocFiles());
		return $all_files;
	}
	
	/**
	 *
	 */
	public function getFilesOfNotice()
	{
		if($this->getNoticeId() > 0)
		{
			$res = $this->db->queryF('SELECT * FROM xnob_images WHERE notice_id = %s',
				array('integer'), array($this->getNoticeId()));
			
			while($row = $this->db->fetchAssoc($res))
			{
				if($row['file_type'] == 'img')
				{
					$this->img_files[] = $row;
				}
				else
				{
					$this->doc_files[] = $row;
				}
			}
		}
	}
	
	/**
	 *
	 */
	public function insertFile()
	{
		if($this->getIsSelected() == 1)
		{
			$this->db->update('xnob_images',
				array('is_selected' => array('integer', 0)),
				array(
					'obj_id'      => array('integer', $this->getObjId()),
					'notice_id'   => array('integer', $this->getNoticeId()),
					'file_type'   => array('text', 'img'),
					'is_selected' => array('integer', 1)
				)
			);
		}
		
		$next_id = $this->db->nextId('xnob_images');
		$this->db->insert('xnob_images',
			array(
				'image_id'    => array('integer', $next_id),
				'obj_id'      => array('integer', $this->getObjId()),
				'category_id' => array('integer', $this->getCategoryId()),
				'notice_id'   => array('integer', $this->getNoticeId()),
				'filename'    => array('text', $this->getFilemame()),
				'is_selected' => array('integer', $this->getIsSelected()),
				'file_type'   => array('text', $this->getFileType())
			));
	}
	
	/**
	 * @param array $image_ids
	 */
	public function deleteFiles($image_ids = array())
	{
		
		if(count($image_ids) > 0)
		{
			$res = $this->db->query('SELECT filename FROM xnob_images WHERE ' .
				$this->db->in('image_id', $image_ids, false, 'integer'));
			
			while($row = $this->db->fetchAssoc($res))
			{
				$filenames[] = $row['filename'];
			}
			
			$filesys = new ilFileDataNoticeboard();
			$filesys->unlinkFiles($filenames);
			
			$this->db->manipulate('DELETE FROM xnob_images WHERE ' .
				$this->db->in('image_id', $image_ids, false, 'integer'));
		}
	}
	
	/**
	 * @param $notice_id
	 */
	public function deleteSelectedImage($notice_id)
	{
		$res = $this->db->queryF('SELECT filename, image_id FROM xnob_images 
		WHERE notice_id = %s AND is_selected = %s',
			array('integer', 'integer'), array($notice_id, 1));
		
		while($row = $this->db->fetchAssoc($res))
		{
			$filenames[] = $row['filename'];
			$image_ids[] = $row['image_id'];
		}
		
		$filesys = new ilFileDataNoticeboard();
		$filesys->unlinkFiles($filenames);
		
		$this->db->manipulate('DELETE FROM xnob_images WHERE ' .
			$this->db->in('image_id', $image_ids, false, 'integer'));
	}
	
	/**
	 * @param $obj_id
	 */
	public static function deleteFilesByObjId($obj_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE obj_id = %s',
			array('integer'), array($obj_id));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$filenames[] = $row['filename'];
		}
		
		$plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$plugin->includeClass('class.ilFileDataNoticeboard.php');
		
		$filesys = new ilFileDataNoticeboard();
		$filesys->unlinkFiles($filenames);
		
		$ilDB->manipulateF('DELETE FROM xnob_images WHERE obj_id = %s',
			array('integer'), array($obj_id));
	}
	
	/**
	 * @param $category_ids
	 */
	public static function deleteFilesByCatId($category_ids)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->query('SELECT filename FROM xnob_images WHERE ' .
			$ilDB->in('category_id', $category_ids, false, 'integer'));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$filenames[] = $row['filename'];
		}
		
		$plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$plugin->includeClass('class.ilFileDataNoticeboard.php');
		
		$filesys = new ilFileDataNoticeboard();
		$filesys->unlinkFiles($filenames);
		
		$ilDB->manipulate('DELETE FROM xnob_images WHERE ' .
			$ilDB->in('category_id', $category_ids, false, 'integer'));
	}
	
	/**
	 * @param      $image_id
	 * @param bool $is_selected
	 * @return mixed
	 */
	public static function lookupFilename($image_id, $is_selected = false)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		if($is_selected == false)
		{
			$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE image_id = %s',
				array('integer'), array($image_id));
		}
		else
		{
			$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE image_id = %s AND is_selected',
				array('integer', 'integer'), array($image_id, 1));
		}
		$row = $ilDB->fetchAssoc($res);
		
		return $row['filename'];
	}
	
	/**
	 * @param $notice_id
	 * @return mixed
	 */
	public static function lookupSelectedFilename($notice_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE notice_id = %s AND is_selected',
			array('integer', 'integer'), array($notice_id, 1));
		
		$row = $ilDB->fetchAssoc($res);
		
		return $row['filename'];
	}
}
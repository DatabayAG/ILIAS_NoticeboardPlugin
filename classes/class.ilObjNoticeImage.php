<?php

/**
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */

class ilObjNoticeImage
{
	const IMAGE = 'img';
	const DOCUMENT = 'doc';
	
	
	private $image_id = 0;
	private $obj_id = 0;
	private $category_id = 0;
	private $notice_id = 0;
	private $filemame = NULL;
	private $is_selected = 0;
	private $file_type = 'img';
	
	public $img_files = array();
	public $doc_files = array();
	public $pluginObj = NULL; 

	public function __construct($notice_id = 0)
	{
		$this->pluginObj =  ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilFileDataNoticeboard.php');
		
		if($notice_id > 0)
		{
			$this->setNoticeId($notice_id);
			$this->getFilesOfNotice();
		}
	}

	public function setCategoryId($category_id)
	{
		$this->category_id = $category_id;
	}

	public function getCategoryId()
	{
		return $this->category_id;
	}

	public function setFilemame($filemame)
	{
		$this->filemame = $filemame;
	}

	public function getFilemame()
	{
		return $this->filemame;
	}

	public function setImageId($image_id)
	{
		$this->image_id = $image_id;
	}

	public function getImageId()
	{
		return $this->image_id;
	}

	public function setIsSelected($is_selected)
	{
		$this->is_selected = $is_selected;
	}

	public function getIsSelected()
	{
		return $this->is_selected;
	}

	public function setNoticeId($notice_id)
	{
		$this->notice_id = $notice_id;
	}

	public function getNoticeId()
	{
		return $this->notice_id;
	}

	public function setObjId($obj_id)
	{
		$this->obj_id = $obj_id;
	}

	public function getObjId()
	{
		return $this->obj_id;
	}

	public function setFileType($file_type)
	{
		$this->file_type = $file_type;
	}

	public function getFileType()
	{
		return $this->file_type;
	}
	
	public function getImgFiles()
	{
		return $this->img_files;
	}

	public function getDocFiles()
	{
		return $this->doc_files;
	}
	
	public function getAllFiles()
	{
		$all_files = array_merge($this->getImgFiles(), $this->getDocFiles());
		return $all_files;
	}
	
	public function getFilesOfNotice()
	{
		global $ilDB;
		
		if($this->getNoticeId() > 0)
		{
			$res = $ilDB->queryF('SELECT * FROM xnob_images WHERE notice_id = %s',
			array('integer'), array($this->getNoticeId()));
			
			while($row = $ilDB->fetchAssoc($res))
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
	
	public function insertFile()
	{
		global $ilDB;

		if($this->getIsSelected() == 1)
		{
			$ilDB->update('xnob_images',
				array('is_selected' => array('integer', 0)),
				array(
					'obj_id'      => array('integer', $this->getObjId()),
					'notice_id'   => array('integer', $this->getNoticeId()),
					'file_type'   => array('text', 'img'),
					'is_selected' => array('integer', 1)
				)
			);
		}

		$next_id = $ilDB->nextId('xnob_images');
		$ilDB->insert('xnob_images',
			array(
				'image_id'    => array('integer', $next_id),
				'obj_id'      => array('integer', $this->getObjId()),
				'category_id' => array('integer', $this->getCategoryId()),
				'notice_id'   => array('integer', $this->getNoticeId()),
				'filename'    => array('text', $this->getFilemame()),
				'is_selected' => array('integer', $this->getIsSelected()),
				'file_type'	=> array('text', $this->getFileType())
			));
	}
	
	public function deleteFiles($image_ids = array())
	{
		global $ilDB;
			
		if(count($image_ids) > 0)
		{
			$res = $ilDB->query('SELECT filename FROM xnob_images WHERE '.
			$ilDB->in('image_id', $image_ids, false, 'integer'));

			while($row = $ilDB->fetchAssoc($res))
			{
				$filenames[] = $row['filename'];
			}
			
			$filesys = new ilFileDataNoticeboard();
			$filesys->unlinkFiles($filenames);
			
			$ilDB->manipulate('DELETE FROM xnob_images WHERE '.
			$ilDB->in('image_id', $image_ids, false, 'integer'));
		}
	}
	
	public function deleteSelectedImage($notice_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryF('SELECT filename, image_id FROM xnob_images 
		WHERE notice_id = %s AND is_selected = %s', 
			array('integer','integer'),array($notice_id, 1));

		while($row = $ilDB->fetchAssoc($res))
		{
			$filenames[] = $row['filename'];
			$image_ids[] = $row['image_id'];
		}

		$filesys = new ilFileDataNoticeboard();
		$filesys->unlinkFiles($filenames);

		$ilDB->manipulate('DELETE FROM xnob_images WHERE '.
			$ilDB->in('image_id', $image_ids, false, 'integer'));
	}
	
	public static function deleteFilesByObjId($obj_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE obj_id = %s',
		array('integer'), array($obj_id));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$filenames[] = $row['filename'];
		}

		$plugin =  ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$plugin->includeClass('class.ilFileDataNoticeboard.php');

		$filesys = new ilFileDataNoticeboard();
		$filesys->unlinkFiles($filenames);

		$ilDB->manipulateF('DELETE FROM xnob_images WHERE obj_id = %s',
			array('integer'), array($obj_id));
			
	}
	
	public static function deleteFilesByCatId($category_ids)
	{
		global $ilDB;

		$res = $ilDB->query('SELECT filename FROM xnob_images WHERE '.
			$ilDB->in('category_id', $category_ids, false, 'integer'));


		while($row = $ilDB->fetchAssoc($res))
		{
			$filenames[] = $row['filename'];
		}

		$plugin =  ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$plugin->includeClass('class.ilFileDataNoticeboard.php');

		$filesys = new ilFileDataNoticeboard();
		$filesys->unlinkFiles($filenames);

		$ilDB->manipulate('DELETE FROM xnob_images WHERE '.
			$ilDB->in('category_id', $category_ids, false, 'integer'));
	}
	
	public static function lookupFilename($image_id, $is_selected = false)
	{
		global $ilDB;
		
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

	public static function lookupSelectedFilename($notice_id)
	{
		global $ilDB;

		$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE notice_id = %s AND is_selected',
			array('integer', 'integer'), array($notice_id, 1));
	
		$row = $ilDB->fetchAssoc($res);

		return $row['filename'];
	}
}
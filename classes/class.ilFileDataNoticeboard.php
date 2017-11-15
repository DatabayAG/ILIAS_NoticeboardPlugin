<?php
	
require_once("./Services/FileSystem/classes/class.ilFileData.php");	

/**
 * Class ilFileDataNoticeboard
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */
class ilFileDataNoticeboard extends ilFileData
{
	/**
	 * @var int
	 */
	public $obj_id;
	/**
	 * @var int
	 */
	public $notice_id;
	/**
	 * @var int
	 */
	public $category_id = 0;
	/**
	 * @var string
	 */
	public $image_path;
	/**
	 * @var string
	 */
	public $preview_path;
	/**
	 * @var string
	 */
	public $thumbnail_path;
	/**
	 * @var ilPlugin
	 */
	public $pluginObj;

	/**
	 * @param $image_path
	 */
	public function setImagePath($image_path)
	{
		$this->image_path = $image_path;
	}
	
	/**
	 * @return string
	 */
	public function getImagePath()
	{
		return $this->image_path;
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
	 * @param $preview_path
	 */
	public function setPreviewPath($preview_path)
	{
		$this->preview_path = $preview_path;
	}
	
	/**
	 * @return string
	 */
	public function getPreviewPath()
	{
		return $this->preview_path;
	}
	
	/**
	 * @param $thumbnail_path
	 */
	public function setThumbnailPath($thumbnail_path)
	{
		$this->thumbnail_path = $thumbnail_path;
	}
	
	/**
	 * @return string
	 */
	public function getThumbnailPath()
	{
		return $this->thumbnail_path;
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
	 * ilFileDataNoticeboard constructor.
	 * @param int $a_obj_id
	 * @param int $a_notice_id
	 */
	public function __construct($a_obj_id = 0, $a_notice_id = 0)
	{
		parent::__construct();
		$this->image_path = ilUtil::getWebspaceDir()."/xnob";
		$this->preview_path = ilUtil::getWebspaceDir()."/xnob/img_preview";
		$this->thumbnail_path = ilUtil::getWebspaceDir()."/xnob/img_thumbnail";
		
		if(!$this->__checkPath())
		{
			$this->__initDirectory();
			$this->__initPreviewDirectory();
			$this->__initThumbnailDirectory();
		}
		$this->obj_id = $a_obj_id;
		$this->notice_id = $a_notice_id;
		
		$this->pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilNoticeboardConfig.php');
		$this->pluginObj->includeClass('class.ilNoticeRepository.php');
	}
	
	/**
	 * @return int
	 */
	public function getObjId()
	{
		return $this->obj_id;
	}

	/**
	 * Store uploaded files in filesystem
	 * 
	 * @param   array   $files  Copy of $_FILES array,
	 * @param   string  $file_type  ilNoticeImage::IMAGE | ilNoticeImage::DOCUMENT
	 * @param   int     $is_selected  image is also selected thumbnail?
	 * @return  bool
	 */
	public function storeUploadedFiles($files, $file_type = ilObjNoticeImage::IMAGE, $is_selected = 0)
	{
		if(isset($files['name']) && is_array($files['name']))
		{
			$this->pluginObj->includeClass('class.ilObjNoticeImage.php');
			foreach($files['name'] as $index => $name)
			{
				// remove trailing '/'
				while(substr($name, -1) == '/')
				{
					$name = substr($name, 0, -1);
				}
				$filename = ilUtil::_sanitizeFilemame($name);
				$temp_name = $files['tmp_name'][$index];
				$error = $files['error'][$index];
				
				if(strlen($filename) > 0 && strlen($temp_name) > 0 && $error == 0)
				{
					$random = md5(uniqid(rand(), TRUE));
					$random = substr($random, 0, 4);

					$path_parts = pathinfo($filename);
					$filename = $path_parts['filename'] . '_' . $this->obj_id . '_' . $this->notice_id . '_' . $random . '.' . $path_parts['extension'];

					$path = $this->getImagePath() . '/' . $filename;
					ilUtil::moveUploadedFile($temp_name, $filename, $path);

					// save to image db
					$objImage = new ilObjNoticeImage();
					if($file_type == ilObjNoticeImage::DOCUMENT)
					{
						$objImage->setFileType(ilObjNoticeImage::DOCUMENT);
					}
					else
					{
						$objImage->setFileType(ilObjNoticeImage::IMAGE);
					}
					$objImage->setNoticeId($this->getNoticeId());
					$objImage->setObjId($this->getObjId());
					$objImage->setCategoryId($this->getCategoryId());
					$objImage->setFilemame($filename);
					$objImage->setIsSelected($is_selected);
					$objImage->insertFile();
					
					if($file_type == ilObjNoticeImage::IMAGE)
					{
						// create preview image
						$imageSize = getImageSize($path);
	
						if(!ilNoticeRepository::existsPreviewImage($path))
						{
							if($imageSize[0] > ilNoticeboardConfig::getSetting('img_preview_width') || $imageSize[1] > ilNoticeboardConfig::getSetting('img_preview_height'))
							{
								ilNoticeRepository::createPreviewImage($path);
							}
							else
							{
								ilNoticeRepository::createPreviewImage($path, $imageSize[0], $imageSize[1]);
							}
						}
						ilNoticeRepository::createThumbnail($path, ilNoticeboardConfig::getSetting('img_thumbnail_width'), ilNoticeboardConfig::getSetting('img_thumbnail_height'));
					}
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param $a_filenames
	 * @return bool
	 */
	public function unlinkFiles($a_filenames)
	{
		if(!is_array( $a_filenames))
		{
			return true;
		}	
			
		foreach($a_filenames as $file)
		{
			$filename = basename($file);

			if(file_exists($this->image_path.'/'.$filename))
			{
				unlink($this->image_path.'/'.$filename);
			}
			if(file_exists($this->preview_path.'/'.$filename))
			{
				unlink($this->preview_path.'/'.$filename);
			}
			if(file_exists($this->thumbnail_path.'/'.$filename))
			{
				unlink($this->thumbnail_path.'/'.$filename);
			}
		}
		return true;
	}
	
	/**
	 * @return bool
	 */
	function __checkPath()
	{
		if(!@file_exists($this->getImagePath()))
		{
			return false;
		}
		$this->__checkReadWrite();
		return true;
	}
	
	/**
	 * @return bool
	 */
	private function __checkReadWrite()
	{
		if(is_writable($this->image_path) && is_readable($this->image_path))
		{
			return true;
		}
		else
		{
			$this->ilias->raiseError("Forum directory is not readable/writable by webserver",$this->ilias->error_obj->FATAL);
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function __initDirectory()
	{
		if(is_writable($this->getPath()))
		{
			if(mkdir($this->getPath().'/xnob'))
			{
				if(chmod($this->getPath().'/xnob',0755))
				{
					$this->image_path = $this->getPath().'/xnob';
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @return bool
	 */
	private function __initPreviewDirectory()
	{
		if(is_writable($this->getPath()))
		{
			if(mkdir($this->getPath().'/xnob/img_preview'))
			{
				if(chmod($this->getPath().'/xnob/img_preview', 0755))
				{
					$this->image_path = $this->getPath().'/xnob/img_preview';
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @return bool
	 */
	private function __initThumbnailDirectory()
	{
		if(is_writable($this->getPath()))
		{
			if(mkdir($this->getPath().'/xnob/img_thumbnail'))
			{
				if(chmod($this->getPath().'/xnob/img_thumbnail', 0755))
				{
					$this->image_path = $this->getPath().'/xnob/img_thumbnail';
					return true;
				}
			}
		}
		return false;
	}
}

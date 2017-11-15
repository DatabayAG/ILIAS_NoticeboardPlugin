<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

/**
 * Class ilNoticeRepositoryImage
 */
class ilNoticeRepositoryImage
{
	/**
	 * @var
	 */
	public $image;
	/**
	 * @var
	 */
	public $image_type;
	
	/**
	 * Creates an image from given source file.
	 * @param string $filename Filename (incl. path) of source file
	 */
	public function load($filename)
	{
		$image_info       = getimagesize($filename);
		$this->image_type = $image_info[2];
		if($this->image_type == IMAGETYPE_JPEG)
		{
			$this->image = imagecreatefromjpeg($filename);
		}
		elseif($this->image_type == IMAGETYPE_GIF)
		{
			$this->image = imagecreatefromgif($filename);
		}
		elseif($this->image_type == IMAGETYPE_PNG)
		{
			$this->image = imagecreatefrompng($filename);
		}
	}
	
	/**
	 * @param string       $filename
	 * @param int          $image_type  Type of the target image (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
	 * @param int          $compression Compression (JPEGs only)
	 * @param null|integer $permissions
	 */
	public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null)
	{
		
		if($image_type == IMAGETYPE_JPEG)
		{
			imagejpeg($this->image, $filename, $compression);
		}
		elseif($image_type == IMAGETYPE_GIF)
		{
			imagegif($this->image, $filename);
		}
		elseif($image_type == IMAGETYPE_PNG)
		{
			imagepng($this->image, $filename);
		}
		if($permissions != null)
		{
			chmod($filename, $permissions);
		}
	}
	
	/**
	 * @param int $image_type Type of the image (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
	 */
	public function output($image_type = IMAGETYPE_JPEG)
	{
		if($image_type == IMAGETYPE_JPEG)
		{
			imagejpeg($this->image);
		}
		elseif($image_type == IMAGETYPE_GIF)
		{
			imagegif($this->image);
		}
		elseif($image_type == IMAGETYPE_PNG)
		{
			imagepng($this->image);
		}
	}
	
	/**
	 * Returns width of the image
	 * @return integer
	 */
	public function getWidth()
	{
		return imagesx($this->image);
	}
	
	/**
	 * Returns height of the image
	 * @return integer
	 */
	public function getHeight()
	{
		return imagesy($this->image);
	}
	
	/**
	 * Resize image to given height and keep dimensions ratio
	 * @param integer $height Resize target image to this height
	 */
	public function resizeToHeight($height)
	{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width, $height);
	}
	
	/**
	 * Resize image to given width and keep dimensions ratio
	 * @param integer $width Resize target image to this width
	 */
	public function resizeToWidth($width)
	{
		$ratio  = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width, $height);
	}
	
	/**
	 * Resize image to given percentage of the original width and height
	 * @param integer $scale Resize image to this percentage of the original width and height
	 */
	public function scale($scale)
	{
		$width  = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;
		$this->resize($width, $height);
	}
	
	/**
	 * Resize image to given width and height (does not keep dimensions ratio)
	 * @param integer $width  Resize image to this width
	 * @param integer $height Resize image to this height
	 */
	public function resize($width, $height)
	{
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}
}

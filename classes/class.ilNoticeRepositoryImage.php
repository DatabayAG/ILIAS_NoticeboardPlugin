<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

/**
 * Based on...
 * File: SimpleImage.php
 * Author: Simon Jarvis
 * Copyright: 2006 Simon Jarvis
 * Date: 08/11/06
 * Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * http://www.gnu.org/licenses/gpl.html
 */
class ilNoticeRepositoryImage {

   var $image;
   var $image_type;

   /**
    * Creates an image from given source file.
    * 
    * @param string $filename	Filename (incl. path) of source file
    * @access public
    */
   public function load($filename) {
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
         $this->image = imagecreatefrompng($filename);
      }
   }

   /**
    * Saves image by type to given target file
    *
    * @param string $filename		Filename (incl. path) of target file
    * @param string $image_type		Type of the target image (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
    * @param integer $compression	Compression (JPEGs only)
    * @param integer $permissions	Permissions to be set for target image file after saving it
    * @access public
    */
   public function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {

      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image,$filename);
      }
      if( $permissions != null) {
         chmod($filename,$permissions);
      }
   }

   /**
    * Returns image by type for output
    *
    * @param string $image_type		Type of the image (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
    * @access public
    */
   public function output($image_type=IMAGETYPE_JPEG) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image);
      }
   }

   /**
    * Returns width of the image
    *
    * @return integer
    * @access public
    */
   public function getWidth() {
      return imagesx($this->image);
   }

   /**
    * Returns height of the image
    *
    * @return integer
    * @access public
    */
   public function getHeight() {
      return imagesy($this->image);
   }

   /**
    * Resize image to given height and keep dimensions ratio
    *
    * @param integer $height	Resize target image to this height
    * @access public
    */
   public function resizeToHeight($height) {

      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }

   /**
    * Resize image to given width and keep dimensions ratio
    *
    * @param integer $width	Resize target image to this width
    * @access public
    */
   public function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }

   /**
    * Resize image to given percentage of the original width and height
    *
    * @param integer $scale 	Resize image to this percentage of the original width and height
    * @access public
    */
   public function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }

   /**
    * Resize image to given width and height (does not keep dimensions ratio)
    *
    * @param integer $width	Resize image to this width
    * @param integer $height	Resize image to this height
    * @access public
    */
   public function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
   }
}

?>
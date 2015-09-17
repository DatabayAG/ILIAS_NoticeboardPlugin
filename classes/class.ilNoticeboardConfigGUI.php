<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
 
/**
 * GUI for notice board configuration
 *
 * @author Jens Conze <jc@databay.de>
 * @version $Id$
 */
class ilNoticeboardConfigGUI extends ilPluginConfigGUI
{
	public $pluginObj = null;
	
	/**
	 * Handles all commands, default is 'configure'
	 *
	 * @access public
	 */
	public function performCommand($cmd)
	{
		$this->pluginObj = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilNoticeboardConfig.php');
		
		switch ($cmd)
		{
			case 'configure':
			case 'save':
				$this->$cmd();
				break;
		}
	}

	/**
	 * Configure screen
	 *
	 * @access public
	 */
	public function configure()
	{
		global $tpl;

		$form = $this->initConfigurationForm();
		$tpl->setContent($form->getHTML());
	}
	
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 * @access public
	 */
	public function initConfigurationForm()
	{
		global $lng, $ilCtrl;
		
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		$form = new ilPropertyFormGUI();
	
		$form->addCommandButton('save', $lng->txt('save'));
	                
		$form->setTitle($this->pluginObj->txt('noticeboard_plugin_configuration'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		
		$validity = new ilNumberInputGUI($this->pluginObj->txt('days'), 'validity');

		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0') >= 0)
		{
			$validity->allowDecimals(false);
		}
		$validity->setRequired(true);
		$validity->setValue(ilNoticeboardConfig::getSetting('validity'));
		
		$form->addItem($validity);

		$img_height = new ilNumberInputGUI($this->pluginObj->txt('preview_height_in_px'), 'img_preview_height');
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0') >= 0)
		{
			$img_height->allowDecimals(false);
		}
		$img_height->setRequired(true);
		$img_height->setValue(ilNoticeboardConfig::getSetting('img_preview_height'));
		$img_height->setInfo($this->pluginObj->txt('img_preview_height_info'));
		$form->addItem($img_height);
		
		$img_width = new ilNumberInputGUI($this->pluginObj->txt('preview_width_in_px'), 'img_preview_width');
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0') >= 0)
		{
			$img_width->allowDecimals(false);
		}
		$img_width->setRequired(true);
		$img_width->setValue(ilNoticeboardConfig::getSetting('img_preview_width'));
		$img_width->setInfo($this->pluginObj->txt('img_preview_width_info'));
		$form->addItem($img_width);


		$img_height = new ilNumberInputGUI($this->pluginObj->txt('thumbnail_height_in_px'), 'img_thumbnail_height');
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0') >= 0)
		{
			$img_height->allowDecimals(false);
		}
		$img_height->setRequired(true);
		$img_height->setValue(ilNoticeboardConfig::getSetting('img_thumbnail_height'));
		$img_height->setInfo($this->pluginObj->txt('img_thumbnail_height_info'));
		$form->addItem($img_height);

		$img_width = new ilNumberInputGUI($this->pluginObj->txt('thumbnail_width_in_px'), 'img_thumbnail_width');
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0') >= 0)
		{
			$img_width->allowDecimals(false);
		}
		$img_width->setRequired(true);
		$img_width->setValue(ilNoticeboardConfig::getSetting('img_thumbnail_width'));
		$img_width->setInfo($this->pluginObj->txt('img_thumbnail_width_info'));
		$form->addItem($img_width);

		$file_types = new ilTextInputGUI($this->pluginObj->txt('supported_doc_types'), 'doc_file_types');
		
		$file_type_settings = ilNoticeboardConfig::getSetting('doc_file_types');
		$default_settings = array('doc', 'txt', 'pdf');
		$file_types->setValue($file_type_settings ? $file_type_settings : implode(',', $default_settings));
		$file_types->setInfo($this->pluginObj->txt('define_allowed_file_types'));
		$form->addItem($file_types);
		
		return $form;
	}
	
	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function save()
	{
		/**
		 * @var $tpl $tpl
		 * @var $lng $lng
		 */
		global $tpl, $lng;
	
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			ilNoticeboardConfig::setSetting('validity', $form->getInput('validity'));
			ilNoticeboardConfig::setSetting('img_preview_height', $form->getInput('img_preview_height'));
			ilNoticeboardConfig::setSetting('img_preview_width', $form->getInput('img_preview_width'));
			ilNoticeboardConfig::setSetting('img_thumbnail_height', $form->getInput('img_thumbnail_height'));
			ilNoticeboardConfig::setSetting('img_thumbnail_width', $form->getInput('img_thumbnail_width'));

			ilNoticeboardConfig::setSetting('doc_file_types', $form->getInput('doc_file_types'));
			
			ilUtil::sendSuccess($lng->txt('saved_successfully'), true);
			$this->configure();
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}
}

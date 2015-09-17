<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once './Services/Repository/classes/class.ilObjectPluginListGUI.php';

/**
 * ListGUI implementation for Noticeboard object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author Jens Conze <jc@databay.de>
 * @version	$Id$
 */
class ilObjNoticeboardListGUI extends ilObjectPluginListGUI
{
	
	/**
	 * Init type
	 *
	 * @access public
	 */
	public function initType()
	{
		$this->setType('xnob');
	}
	
	/**
	 * Get name of gui class handling the commands
	 *
	 * @access public
	 */
	public function getGuiClass()
	{
		return 'ilObjNoticeboardGUI';
	}
	
	/**
	 * Get commands
	 *
	 * @access public
	 */
	public function initCommands()
	{
		return array
		(
			array(
				'permission' => 'read',
				'cmd' => 'showBoard',
				'default' => true),
			array(
				'permission' => 'write',
				'cmd' => 'editProperties',
				'txt' => $this->txt('edit'),
				'default' => false),
		);
	}

	/**
	 * Get item properties
	 *
	 * @return	array		array of property arrays:
	 *						'alert' (boolean) => display as an alert property (usually in red)
	 *						'property' (string) => property name
	 *						'value' (string) => property value
	 * @access public
	 */
	public function getProperties()
	{
		$props = array();
		
		$this->plugin->includeClass('class.ilObjNoticeboardAccess.php');

		return $props;
	}
}
?>
<?php
/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

require_once 'Services/Table/classes/class.ilTable2GUI.php';
/**
 * Class ilNoticeboardPermissionsTableGUI
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */
class ilNoticeboardPermissionsTableGUI extends ilTable2GUI
{
	private $category_id = 0;
	
	/**
	 * ilNoticeboardPermissionsTableGUI constructor.
	 * @param object $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param string $a_template_context
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
	{
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
		$this->parent_obj = $a_parent_obj;
		$this->init();
	}

	public function init()
	{
		$this->setFormAction($this->parent_obj->ctrl->getFormAction($this->parent_obj, 'saveCategoryPermissions'));

		$this->addColumn('', 'cat_id', '1%');
		$this->addColumn($this->lng->txt('title'), 'role_title', '30%');
		$this->addColumn($this->lng->txt('write'), 'xnob_write', '30%');

		$this->setRowTemplate('tpl.permissions_row.html', $this->parent_obj->pluginObj->getDirectory());
		$this->addCommandButton('saveCategoryPermissions',$this->lng->txt('save'));
		$this->addCommandButton('showCategories',$this->lng->txt('cancel'));

		$this->setDescription($this->parent_obj->pluginObj->txt('permissions_info'));
	}
	
	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set)
	{
		foreach($a_set as $key => $value)
		{
			$this->tpl->setVariable(strtoupper($key),  $value);
		}
	}
	
	/**
	 * @param $cat_id
	 */
	public function setCategoryId($cat_id)
	{
		$this->category_id = $cat_id;
	}
	
	public function getCategoryId()
	{
		return $this->category_id;
	}
}
<?php
/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

require_once 'Services/Table/classes/class.ilTable2GUI.php';

/***
 * @author Nadia Ahmad <nahmad@databay.de>
 * @version $Id$
 */

class ilPermissionsTableGUI extends ilTable2GUI
{
	private $category_id = 0;
	
	public function __construct($parent_obj, $a_parent_cmd = "")
	{
		parent::__construct($parent_obj);
		
		$this->parent_obj = $parent_obj;
		$this->init();
	}

	public function init()
	{
		$this->setFormAction($this->parent_obj->ctrl->getFormAction($this->parent_obj, 'saveCategoryPermissions'));

		$this->addColumn('', 'cat_id', '1%');
		$this->addColumn($this->lng->txt('title'), 'role_title', '30%');
//		$this->addColumn($this->lng->txt('read'), 'xnob_read', '30%');
		$this->addColumn($this->lng->txt('write'), 'xnob_write', '30%');

		$this->setRowTemplate('tpl.permissions_row.html', $this->parent_obj->pluginObj->getDirectory());
		$this->addCommandButton('saveCategoryPermissions',$this->lng->txt('save'));
		$this->addCommandButton('showCategories',$this->lng->txt('cancel'));

		$this->setDescription($this->parent_obj->pluginObj->txt('permissions_info'));
	}
	
	public function fillRow($a_set)
	{
		foreach($a_set as $key => $value)
		{
			$this->tpl->setVariable(strtoupper($key),  $value);
		}
	}
	
	public function setCategoryId($cat_id)
	{
		$this->category_id = $cat_id;
	}
	
	public function getCategoryId()
	{
		return $this->category_id;
	}
}
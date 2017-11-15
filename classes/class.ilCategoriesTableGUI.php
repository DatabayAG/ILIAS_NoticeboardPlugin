<?php

/* Copyright (c) 2013 Databay AG, Freeware, see license.txt */
require_once 'Services/Table/classes/class.ilTable2GUI.php';
require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';

/**
 *	Categories list TableGUI.
 *
 *	This class intends to allow displaying a list
 *	of notice categories.
 *
 *	@version $Id$
 */
class ilCategoriesTableGUI extends ilTable2GUI
{
	/**
	 * @var ilPlugin
	 */
	protected $plugin;
	
	/**
	 * ilCategoriesTableGUI constructor.
	 * @param object $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param string $a_template_context
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '')
	{
		$this->plugin = $a_parent_obj->pluginObj;
		$this->plugin->includeClass('class.ilNoticeCategory.php');
		
		$this->setId("xnob_categories_list_" . $a_parent_obj->object->getId());
		$this->setTitle($this->plugin->txt('categories'));
		$this->setDescription($this->plugin->txt('categories_description'));

		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

		$this->addColumn('', '', '1%',true);
		$this->addColumn($this->plugin->txt('title'), 'category_title');
		$this->addColumn($this->plugin->txt('actions'), '','1');

		/* Configure renderer */
		$this->setRowTemplate('tpl.category_row.html', $this->plugin->getDirectory());

		$this->setSelectAllCheckbox('category_id[]');
		$this->addMultiCommand('confirmDeleteCategory', $this->plugin->txt('delete_categories'));
		$this->addCommandButton('addCategory', $this->plugin->txt('add_category'));

		$formAction = $this->parent_obj->ctrl->getFormAction($this->parent_obj, $this->parent_cmd);
		$this->setFormAction($formAction);

		$this->setDefaultOrderField('nt_mod_date');
		$this->setDefaultOrderDirection('desc');
	}

	/**
	 * @param array $category
	 */
	public function fillRow( $category )
	{
		$this->tpl->setVariable('CHECKBOX_CAT_ID', $category['category_id']);

		$this->tpl->setVariable('TITLE', $category['category_title']);

		$action = new ilAdvancedSelectionListGUI();
		$action->setId('asl_' . $category['category_id']);
		$action->setListTitle($this->lng->txt('actions'));

		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'category_id', $category['category_id']);
		$action->addItem($this->lng->txt('edit'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'editCategory'));
		$action->addItem($this->lng->txt('delete'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'confirmDeleteOneCategory'));
		$action->addItem($this->lng->txt('perm_settings'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'showPermissions'));

		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'category_id', '');

		$this->tpl->setVariable('ACTIONS', $action->getHtml());
	}
	
	/**
	 * @param $field
	 * @return bool
	 */
	public function numericOrdering($field)
	{
		switch($field) {
			case 'nt_created_date':
			case 'nt_mod_date':
				return true;
		}

		return false;
	}
}

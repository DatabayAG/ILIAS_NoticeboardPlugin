<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

require_once 'Services/Table/classes/class.ilTable2GUI.php';
require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';

/**
 * Inherited Table2GUI:
 * Shows a list of notices
 * @author       Jens Conze <jc@databay.de>
 * @version      $Id$
 * @ilCtrl_Calls ilBoardTableGUI: ilPublicUserProfileGUI
 */
class ilBoardTableGUI extends ilTable2GUI
{
	/**
	 * @var ilPlugin
	 */
	protected $plugin;
	
	/**
	 * @var integer
	 */
	protected $categoryId;
	
	/**
	 * @var
	 */
	protected $write_access;
	
	/**
	 * @var array
	 */
	protected $hide_columns = array();
	
	/**
	 * Constructor
	 * @param ilObjectGUI $a_parent_obj
	 * @param string      $a_parent_cmd
	 * @param string      $a_template_context
	 * @access public
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '')
	{
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
		
		$this->plugin = $this->parent_obj->pluginObj;
	}

	
	/**
	 * @param int $a_cat_id
	 * @return $this
	 */
	public function init($a_cat_id)
	{
		$this->categoryId = $a_cat_id;
		
		if($a_cat_id == ilNoticeCategory::NOTICE_CATEGORY_ALL)
		{
			$title = $this->plugin->txt('all_notices');
		}
		else
		{
			$currentCategory = new ilNoticeCategory($a_cat_id);
			$title           = $currentCategory->getCategoryTitle();
		}
		
		$this->setTitle($title);
		
		if($this->parent_obj->getPermission('moderate'))
			$this->addColumn('', '', '1%');
		
		$this->addColumn($this->plugin->txt('image'), 'image', '5%');
		$this->addColumn($this->plugin->txt('title'), 'nt_title', '25%');
		
		if($a_cat_id == ilNoticeCategory::NOTICE_CATEGORY_ALL)
		{
			$all_notices_priceCol = ilNoticeCategory::anyCategoryWithPrice($this->parent_obj->obj_id);
		}
		else
		{
			$all_notices_priceCol = ilNoticeCategory::anyCategoryWithPrice($this->parent_obj->obj_id, $a_cat_id);
		}
		if($all_notices_priceCol == true)
		{
			/* Category requires price for objects, show price column */
			$this->addColumn($this->plugin->txt('price'), 'nt_price', '10%');
		}
		
		$this->addColumn($this->plugin->txt('user'), 'user_name', '10%');
		
		$this->addColumn($this->plugin->txt('location'), 'nt_location_city', '20%');
		$this->addColumn($this->plugin->txt('mod_date'), 'nt_mod_date', '15%');
		$this->addColumn($this->plugin->txt('expire_date'), 'nt_until_date', '15%');
		
		$this->addColumn($this->lng->txt('actions'), '', '10%');
		
		$this->setRowTemplate('tpl.notice_row.html', $this->plugin->getDirectory());
		
		$this->setId('xnob_nt_' . $this->parent_obj->object->getId());
		$this->setPrefix('xnob_nt_' . $this->parent_obj->object->getId());
		if($this->parent_obj->getPermission('moderate'))
		{
			$this->setSelectAllCheckbox('notice_id[]');
			$this->addMultiCommand('confirmDelete', $this->plugin->txt('delete_notices'));
			$this->addMultiCommand('toggleStatus', $this->plugin->txt('toggle_status'));
			$this->addMultiCommand('moveNotice', $this->plugin->txt('move'));
		}
		if($this->hasWriteAccess() == true)
		{
			$this->addCommandButton('create', $this->plugin->txt('add_notice'));
		}
		
		$this->parent_obj->ctrl->setParameter($this->parent_obj, 'category_id', $a_cat_id);
		
		$formAction = $this->parent_obj->ctrl->getFormAction($this->parent_obj, $this->parent_cmd);
		$this->setFormAction($formAction);
		
		$this->setDefaultOrderField('nt_mod_date');
		$this->setDefaultOrderDirection('desc');
		
		return $this;
	}
	
	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set)
	{
		global $DIC;
		$ilUser = $DIC->user();
		$ilCtrl = $DIC->ctrl();
		
		/* Use Category model to check wether price column is needed */
		//		$hasPriceCol = ilNoticeCategory::isPriceEnabled($a_set['nt_category_id']);
		$all_notices_priceCol = ilNoticeCategory::anyCategoryWithPrice($this->parent_obj->obj_id);
		$cur_cat_has_price    = ilNoticeCategory::anyCategoryWithPrice($this->parent_obj->obj_id, $a_set['nt_category_id']);
		
		/* Configure template content with notice data. */
		$this->tpl->setVariable('IMAGE', $a_set['image']);
		
		$this->tpl->setVariable('TXT_CREATE_DATE', $this->plugin->txt('create_date'));
		if($this->parent_obj->getPermission('moderate'))
		{
			$this->tpl->setVariable('CHECKBOX_NT_ID', (int)$a_set['nt_id']);
		}
		
		$this->parent_obj->ctrl->setParameter($this->parent_obj, "notice_id", $a_set['nt_id']);
		
		$owner = new ilObjUser($a_set['usr_id']);
		$owner->read();
		
		$userName = $owner->getPublicName();
		
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.4.0') >= 0)
		{
			global $DIC;
			$ilUser = $DIC->user();
			
			$pp = $owner->getPref('public_profile') == 'g' || ($owner->getPref('public_profile') == 'y' && $ilUser->getId() != ANONYMOUS_USER_ID);
			$ilCtrl->setParameterByClass('ilpublicuserprofilegui', 'user', $owner->getId());
			
			if($pp)
			{
				$userName = sprintf("<a href='%s'>%s</a>",
					$this->parent_obj->ctrl->getLinkTargetByClass('ilpublicuserprofilegui', 'getHTML'),
					$owner->getPublicName());
			}
			else
			{
				$userName = $owner->getPublicName();
			}
		}
		else
		{
			if($owner->hasPublicProfile())
			{
				$ilCtrl->setParameterByClass("ilpublicuserprofilegui", "category_id", "");
				$ilCtrl->setParameterByClass("ilpublicuserprofilegui", "tab", "");
				$ilCtrl->setParameterByClass("ilpublicuserprofilegui", "user_id", $owner->getId());
				$userName = sprintf("<a href='%s'>%s</a>",
					$ilCtrl->getLinkTargetByClass("ilpublicuserprofilegui", "getHTML", "", false, false),
					$owner->getPublicName());
			}
		}
		
		$this->parent_obj->ctrl->setParameter($this->parent_obj, "category_id", (int)$a_set['nt_category_id']);
		$this->parent_obj->ctrl->setParameter($this->parent_obj, "notice_id", (int)$a_set['nt_id']);
		
		if($this->parent_cmd == 'showMyNotices')
		{
			$showLink = $this->parent_obj->ctrl->getLinkTarget($this->parent_obj, "showMyNotice", '', false, false);
		}
		else
		{
			$showLink = $this->parent_obj->ctrl->getLinkTarget($this->parent_obj, "show", '', false, false);
		}
		
		$this->tpl->setVariable('LINK', $showLink);
		$this->tpl->setVariable('NT_TITLE', $a_set['nt_title']);
		$this->tpl->setVariable('USER_NAME', $userName);
		
		if(($this->categoryId == 0 && $all_notices_priceCol == true)
			|| $cur_cat_has_price == true
		)
		{
			$currency  = $this->parent_obj->object->getCurrency();
			$itemPrice = ilNoticeboardUtil::formatPrice($a_set['nt_price'], $currency, $a_set['nt_price_type']);
			$this->tpl->setVariable('PRICE', $itemPrice);
		}
		
		$this->tpl->setVariable('NT_LOCATION_CITY', $a_set['nt_location_city']);
		
		$this->tpl->setVariable('NT_MOD_DATE', ilNoticeboardUtil::formatDate($a_set['nt_mod_date']));
		$this->tpl->setVariable('NT_CREATE_DATE', ilNoticeboardUtil::formatDate($a_set['nt_create_date']));
		
		$valid_until_suffix = '';
		if(!in_array('nt_hidden', $this->getHideColumns()))
		{
			if((int)$a_set['nt_hidden'] != 0)
			{
				$valid_until_suffix = ' [' . $this->lng->txt('inactive') . ']';
			}
		}
		$this->tpl->setVariable('NT_UNTIL_DATE', ilNoticeboardUtil::formatDate($a_set['nt_until_date']) . $valid_until_suffix);
		
		/* Configure Actions list */
		$action = new ilAdvancedSelectionListGUI();
		$action->setId('asl_' . (int)$a_set['nt_id']);
		$action->setListTitle($this->lng->txt('actions'));
		
		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'notice_id', (int)$a_set['nt_id']);
		$action->addItem($this->lng->txt('show'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'show'));
		
		if($this->parent_obj->getPermission('moderate')
			|| $a_set['usr_id'] == $ilUser->getId()
		)
		{
			/* User has moderate access OR user is owner. */
			$action->addItem($this->lng->txt('edit'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'update'));
			$action->addItem($this->lng->txt('delete'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'confirmDeleteOne'));
			
			if(!in_array('nt_hidden', $this->getHideColumns()))
			{
				
				if($a_set['nt_hidden'] != TRUE)
				{
					$action->addItem($this->lng->txt('deactivate'), '', $ilCtrl->getLinkTarget($this->getParentObject(), 'toggleStatusOne'));
				}
				else
				{
					$action->addItem($this->lng->txt('activate'), '', $ilCtrl->getLinkTarget($this->getParentObject(), 'toggleStatusOne'));
				}
			}
			$action->addItem($this->lng->txt('move'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'moveNotice'));
		}
		
		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'parent_cmd', $this->parent_cmd);
		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'notice_id', '');
		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'tab', '');
		
		$this->tpl->setVariable('ACTIONS', $action->getHtml());
	}
	
	/**
	 * @param $field
	 * @return bool
	 */
	public function numericOrdering($field)
	{
		switch($field)
		{
			case 'nt_created_date':
			case 'nt_mod_date':
				return true;
		}
		
		return false;
	}
	
	/**
	 * @return bool
	 */
	public function hasWriteAccess()
	{
		return (bool)$this->write_access;
	}
	
	/**
	 * @param $write_access
	 */
	public function setWriteAccess($write_access)
	{
		$this->write_access = (bool)$write_access;
	}
	
	/**
	 * @param $col
	 */
	public function hideColumn($col)
	{
		$this->hide_columns[] = $col;
	}
	
	/**
	 * @return array
	 */
	public function getHideColumns()
	{
		return $this->hide_columns;
	}
}
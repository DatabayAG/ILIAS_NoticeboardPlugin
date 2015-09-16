<?php/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */require_once 'Services/Table/classes/class.ilTable2GUI.php';require_once 'Services/Calendar/classes/class.ilDatePresentation.php';require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';/** * Inherited Table2GUI: * Shows user's notices * @author  Jens Conze <jc@databay.de> * @version $Id$ */class ilMyNoticesTableGUI extends ilTable2GUI{	/**	 * Constructor	 * @param ilObjectGUI $a_parent_obj	 * @param string      $a_parent_cmd	 * @param string      $a_template_context	 * @access public	 */	public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '')	{		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);	}	/**	 * Init the table with some configuration	 * @param integer $a_type Type of board: ilObjNoticeboard::NOTICE_BOARD_TYPE_...	 * @return object ilNoticeTableGUI	 * @access public	 */	public function init($a_type)	{		if($a_type == ilObjNoticeboard::NOTICE_BOARD_TYPE_FOR_SALE)		{			$title = $this->parent_obj->pluginObj->txt('for_sale');		}		else if($a_type == ilObjNoticeboard::NOTICE_BOARD_TYPE_WANTED)		{			$title = $this->parent_obj->pluginObj->txt('wanted');		}		else		{			$title = $this->parent_obj->pluginObj->txt('notices');		}		$this->setTitle($title);		$this->addColumn('', 'checkbox_nt_id', '1%');		$this->addColumn($this->parent_obj->pluginObj->txt('image'), '', '75px');		$this->addColumn($this->parent_obj->pluginObj->txt('title'), 'nt_title', '30%');		if($a_type == ilObjNoticeboard::NOTICE_BOARD_TYPE_FOR_SALE)		{			$this->addColumn($this->parent_obj->pluginObj->txt('price'), 'nt_price', '10%');			$this->addColumn($this->parent_obj->pluginObj->txt('user'), 'usr_name', '10%');		}		else		{			$this->addColumn($this->parent_obj->pluginObj->txt('user'), 'usr_name', '20%');		}		$this->addColumn($this->parent_obj->pluginObj->txt('location'), 'nt_location_city', '20%');		$this->addColumn($this->parent_obj->pluginObj->txt('mod_date'), 'nt_mod_date', '20%');		$this->addColumn($this->lng->txt('actions'), '', '10%');		$this->setRowTemplate('tpl.notice_row.html', $this->parent_obj->pluginObj->getDirectory());		$this->setId('xnob_nt_' . $this->parent_obj->object->getId());		$this->setPrefix('xnob_nt_' . $this->parent_obj->object->getId());		$this->setSelectAllCheckbox('notice_id[]');		$this->addMultiCommand('confirmDelete', $this->parent_obj->pluginObj->txt('delete_notices'));		$this->addMultiCommand('toggleStatus', $this->parent_obj->pluginObj->txt('toggle_status'));		$this->addCommandButton('create', $this->parent_obj->pluginObj->txt('add_notice'));		$this->parent_obj->ctrl->setParameter($this->parent_obj, 'type', $a_type);		$this->parent_obj->ctrl->setParameter($this->parent_obj, 'tab', 'my_notices');		$this->setFormAction($this->parent_obj->ctrl->getFormAction($this->parent_obj, $this->parent_cmd));		$this->setDefaultOrderField('nt_mod_date');		$this->setDefaultOrderDirection('desc');		return $this;	}	/**	 * @see ilTable2GUI::fillRow()	 */	public function fillRow($a_set)	{		$this->tpl->setVariable('TXT_IMAGE', $this->parent_obj->pluginObj->txt('image'));		$this->tpl->setVariable('TXT_CREATE_DATE', $this->parent_obj->pluginObj->txt('create_date'));		$this->tpl->setVariable('CHECKBOX_NT_ID', (int)$a_set['nt_id']);		if($a_set['hidden'] === TRUE)		{			$this->tpl->setVariable('TXT_HIDDEN', $this->parent_obj->pluginObj->txt('hidden'));		}		if($a_set['expired'] === TRUE)		{			$this->tpl->setVariable('TXT_EXPIRED', $this->parent_obj->pluginObj->txt('expired'));		}		foreach($a_set as $key => $value)		{			$this->tpl->setVariable(strtoupper($key), $value);		}		$action = new ilAdvancedSelectionListGUI();		$action->setId('asl_' . (int)$a_set['nt_id']);		$action->setListTitle($this->lng->txt('actions'));		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'notice_id', (int)$a_set['nt_id']);		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'tab', 'my_notices');		$action->addItem($this->lng->txt('show'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'show'));		$action->addItem($this->lng->txt('edit'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'update'));		$action->addItem($this->lng->txt('delete'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'confirmDeleteOne'));		if($a_set['hidden'] === TRUE || $a_set['expired'] === TRUE)		{			$action->addItem($this->lng->txt('activate'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'toggleStatusOne'));		}		else		{			$action->addItem($this->lng->txt('deactivate'), '', $this->parent_obj->ctrl->getLinkTarget($this->getParentObject(), 'toggleStatusOne'));		}		$this->parent_obj->ctrl->setParameter($this->getParentObject(), 'notice_id', '');		$this->parent_obj->ctrl->setParameter($this->parent_obj, 'tab', '');		$this->tpl->setVariable('ACTIONS', $action->getHtml());	}	/**	 * @see ilTable2GUI::numericOrdering()	 */	public function numericOrdering($field)	{		switch($field)		{			case 'nt_created_date':			case 'nt_mod_date':				return true;		}		return false;	}}
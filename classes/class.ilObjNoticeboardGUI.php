<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
include_once('Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * User Interface class for notice board repository object.
 * This class provides command methods (actions) which fill
 * the template object with variables and render noticeboard
 * content.
 * @author  Jens Conze <jc@databay.de>
 * @version $Id$
 *          Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *          screens) and ilInfoScreenGUI (handles the info screen).
 * @ilCtrl_isCalledBy ilObjNoticeboardGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilPublicUserProfileGUI
 * @ilCtrl_Calls      ilObjNoticeboardGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilRepositorySearchGUI, ilPublicUserProfileGUI, ilCommonActionDispatcherGUI, ilObjPortfolioGUI
 */

class ilObjNoticeboardGUI extends ilObjectPluginGUI
{
	/**
	 * Type of notice board to be shown
	 * @var integer
	 */
	protected $currentCategoryId = 0;

	/**
	 * @var $lng ilLanguage
	 */
	public $lng = NULL;

	/**
	 * @var $tabs ilTabsGUI
	 */
	protected $tabs = NULL;

	/**
	 * @var $ilCtrl ilCtrl
	 */
	public $ctrl = NULL;

	/**
	 * @var $ilAccess $ilAccess
	 */
	protected $access = NULL;

	/**
	 * @var $tpl ilTemplate
	 */
	public $tpl = NULL;

	/**
	 * @var $ilUser ilObjUser
	 */
	protected $user = NULL;

	/**
	 * User's permissions
	 * @var array
	 */
	protected $permissions = array();

	/**
	 * Notice repository
	 * @var object ilNoticeRepository
	 */
	protected $noticeRepository = NULL;

	/**
	 * Mode: Create notification template
	 * @const integer
	 */
	const MODE_CREATE = 1;
	/**
	 * Mode: Update notification template
	 * @const integer
	 */
	const MODE_UPDATE = 2;

	/**
	 * Mode: Create or update notification template
	 * @var integer
	 */
	protected $mode = self::MODE_CREATE;

	/**
	 * Status: Error while creating/updating notification template
	 * @const integer
	 */
	const STATUS_ERROR = 1;
	/**
	 * Status: Notification template successfully created/updated
	 * @const integer
	 */
	const STATUS_SUCCESSFUL = 2;

	/**
	 * Status for creating/updating notification templates
	 * @var integer
	 */
	protected $status = 0;

	/**
	 * Form GUI
	 * @var object ilPropertyFormGUI
	 */
	protected $formGui = NULL;

	/**
	 * A notice object
	 * @var object ilNotice
	 */
	protected $notice = NULL;

	public $fileObj = NULL;

	public $pluginObj = null;
	public $catListGui = null;
	public $filter = null;
	private $filter_enabled = false;


	/**
	 * Initialisation
	 * @access protected
	 */
	protected function afterConstructor()
	{
		/**
		 * @var $ilTabs   ilTabsGUI
		 * @var $ilCtrl   ilCtrl
		 * @var $ilAccess $lAccess
		 * @var $tpl      ilTemplate
		 * @var $lng      ilLanguage
		 * @var $ilUser   ilObjUser
		 */
		global $ilTabs, $ilCtrl, $ilAccess, $tpl, $lng, $ilUser;
		// anything needed after object has been constructed

		$this->pluginObj = ilPlugin::getPluginObject('Services', 'Repository', 'robj', 'Noticeboard');
		$this->pluginObj->includeClass('class.ilObjPermission.php');

		$this->pluginObj->includeClass('class.ilNotice.php');
		$this->pluginObj->includeClass('class.ilNoticeRepository.php');
		$this->pluginObj->includeClass('class.ilNoticeCategory.php');
		$this->pluginObj->includeClass('class.ilNoticeboardUtil.php');
		$this->pluginObj->includeClass('class.ilFileDataNoticeboard.php');
		$this->pluginObj->includeClass('class.ilObjNoticeImage.php');

		$this->tabs   = $ilTabs;
		$this->ctrl   = $ilCtrl;
		$this->access = $ilAccess;
		$this->lng    = $lng;
		$this->user   = $ilUser;
		$this->tpl    = $tpl;
		$this->tpl->addCss($this->pluginObj->getDirectory() . '/templates/default/xnob.css');

		if($this->object instanceof ilObjNoticeboard)
		{
			$tpl->setDescription($this->object->getDescription());
		}
	}

	/**
	 * Returns whether the user has the given permission or not
	 * @param string $a_permission    A permission like 'write' or 'read' or 'owner'
	 * @return bool
	 * @access public
	 */
	public function getPermission($a_permission)
	{
		if(!isset($this->permissions[$a_permission]))
		{
			switch($a_permission)
			{
				case 'read':
					$this->permissions[$a_permission] = $this->checkReadPermission();
					break;
				case 'write':
					$this->permissions[$a_permission] = $this->checkWritePermission();
					break;
				case 'moderate':
					$this->permissions[$a_permission] = $this->checkModeratePermission();
					break;
				default:
					$this->permissions[$a_permission] = FALSE;
			}
		}
		return $this->permissions[$a_permission];
	}

	/**
	 * Checks if the user has read permission.
	 * User may write notices.
	 * @return bool
	 * @access protected
	 */
	protected function checkReadPermission()
	{
		return ($this->access->checkAccess('read', '', $this->object->getRefId()));
	}

	/**
	 * Checks if the user has write permission or is owner of the object.
	 * User may edit properties.
	 * @return bool
	 * @access protected
	 */
	protected function checkWritePermission()
	{
		return ($this->access->checkAccess('write', '', $this->object->getRefId()) || $this->checkOwner());
	}

	/**
	 * Checks if the user is the owner of the object or has the admin role.
	 * User may delete notices of other users.
	 * @return bool
	 * @access protected
	 */
	protected function checkModeratePermission()
	{
		global $rbacreview;

		return ($this->checkOwner() || $rbacreview->isAssigned($this->user->getId(), SYSTEM_ROLE_ID));
	}

	/**
	 * Checks if the user is the owner of the object.
	 * @return bool
	 * @access protected
	 */
	protected function checkOwner()
	{
		return ($this->object->getOwner() == $this->user->getId());
	}

	/**
	 * Get type of plugin
	 * @return string
	 * @access public
	 */
	public final function getType()
	{
		return 'xnob';
	}

	/**
	 * @see ilObjectPluginGUI::executeCommand
	 *      Method implemented to fill ilPublicUserProfileGUI
	 *      requirements on user profile link click. The profile
	 *      GUI must be initialized before the command is forwarded.
	 */
	public function &executeCommand()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */

		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass($this);

		switch($next_class)
		{
			default:
				parent::executeCommand();
		}
	}

	/**
	 * Handles all commmands of this class, centralizes permission checks
	 * @param string $cmd    Command to be performed
	 * @access public
	 */
	public function performCommand($cmd)
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass($this);

		switch($next_class)
		{
			case 'ilpublicuserprofilegui':
				require_once 'Services/User/classes/class.ilPublicUserProfileGUI.php';
				$profile_gui = new ilPublicUserProfileGUI($_GET["user"]);
				$profile_gui->setBackUrl($this->ctrl->getLinkTarget($this, 'showBoard'));
				$this->tpl->setContent($this->ctrl->forwardCommand($profile_gui));
				break;
			default:
				switch($cmd)
				{
					case 'setFilter':
					case 'resetFilter':
					case 'setFilterBySubtab':
						$this->tabs->setTabActive($_SESSION['activeTab']);
						if($this->getPermission('read'))
						{
							$cmd .= 'Action';
							$this->$cmd();
						}
						break;
					case 'showMyNotice':
					case 'showMyNotices':
						$_SESSION['activeTab'] = 'showMyNotices';

						$this->tabs->setTabActive('showMyNotices');
						if($this->getPermission('read'))
						{
							$cmd .= 'Action';
							$this->$cmd();
						}
						break;

					case 'content':
						$cmd = 'showBoard';
					case 'show':
					case 'showBoard':

//					case 'setFilter':
//					case 'resetFilter':
//					case 'setFilterBySubtab':

						$_SESSION['activeTab'] = 'content';
						$this->tabs->setTabActive('content');

						if($this->getPermission('read'))
						{
							$cmd .= 'Action';
							$this->$cmd();
						}
						break;
					case 'editProperties':
					case 'updateProperties':
					case 'deleteHiddenPosts':
					case 'confirmDeleteHiddenPosts':
						$this->tabs->setTabActive('properties');
						$this->$cmd();
						break;

					case 'showCategories':
					case 'addCategory':
					case 'editCategory':
					case 'updateCategories':
						$this->tabs->setTabActive('categories');
						$this->$cmd();
						break;
					case 'showPermissions':
					case 'saveCategoryPermissions':
						$this->tabs->setTabActive('permissions');
						$this->$cmd();
						break;

					case 'create':
					case 'update':
					case 'confirmDeleteOne':
					case 'confirmDelete':
					case 'confirmDeleteOneCategory':
					case 'confirmDeleteCategory':
					case 'deleteCategory':
					case 'delete':
					case 'toggleStatusOne':
					case 'toggleStatus':
					case 'contact':
					case 'reportNotice':
					case 'recommendNotice':
					case 'lookupUsersAsync':
					case 'selectCategoryAsync':
					case 'showUserProfile':
					case 'moveNotice':
					case 'deliverDocument':


						if($this->getPermission('read'))
						{
							$cmd .= 'Action';
							$this->$cmd();
						}
						break;
				}
		}
	}

	/**
	 * After object has been created -> jump to this command
	 * @return strings
	 * @access public
	 */
	public function getAfterCreationCmd()
	{
		return 'editProperties';
	}

	/**
	 * Get standard command
	 * @return string
	 * @access public
	 */
	public function getStandardCmd()
	{
		return 'showBoard';
	}

	/**
	 * Get type of notice board from GET-Parameter
	 * (or standard type if no GET-Parameter is given)
	 * @return integer
	 * @access protected
	 */
	public function getCurrentCategoryId()
	{
		if(isset($_GET['category_id']))
		{
			$this->currentCategoryId = (int)$_GET['category_id'];
		}
		else if(isset($_POST['cat_id']))
		{
			$this->currentCategoryId = (int)$_POST['cat_id'];
		}
		return $this->currentCategoryId;
	}

	/**
	 * show information screen
	 * @access public
	 * @return void
	 */
	public function infoScreen()
	{
		$this->tabs->setTabActive('info_short');

		$this->checkPermission('visible');

		include_once('./Services/InfoScreen/classes/class.ilInfoScreenGUI.php');
		$info = new ilInfoScreenGUI($this);

		$info->addSection($this->txt('plugininfo'));
		$info->addProperty('Name', 'Noticeboard');
		$info->addProperty('Version', xnob_version);
		$info->addProperty('Developer', 'Jens Conze');
		$info->addProperty('Kontakt', 'jc@databay.de');
		$info->addProperty('&nbsp;', 'Databay AG');
		$info->addProperty('&nbsp;', '<img src="http://www.iliasnet.de/download/databay.png?plug=noticeboard" alt="Databay AG" title="Databay AG" />');
		$info->addProperty('&nbsp;', 'http://www.iliasnet.de');

		$info->enablePrivateNotes();

		// general information
		$this->lng->loadLanguageModule('meta');

		$this->addInfoItems($info);

		// forward the command
		$this->ctrl->forwardCommand($info);
	}

	/**
	 * Set tabs
	 * @access protected
	 */
	protected function setTabs()
	{
		if($this->getPermission('read'))
		{
			// tab for the 'show content' command
			$this->ctrl->setParameter($this, 'category_id', ilNotice::NOTICE_CATEGORY_ALL);
			$this->tabs->addTab('content', $this->txt('content'), $this->ctrl->getLinkTarget($this, 'showBoard'));
			$this->ctrl->setParameter($this, 'category_id', '');
		}
		// standard info screen tab
		$this->addInfoTab();

		if($this->getPermission('read'))
		{
			// tab for the 'show my notices' command
			$this->tabs->addTab('showMyNotices', $this->txt('my_notices'), $this->ctrl->getLinkTarget($this, 'showMyNotices'));
		}

		// a 'properties' tab
		if($this->getPermission('write'))
		{
			$this->tabs->addTab('categories', $this->txt('categories'), $this->ctrl->getLinkTarget($this, 'showCategories'));
			$this->tabs->addTab('properties', $this->txt('properties'), $this->ctrl->getLinkTarget($this, 'editProperties'));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}

	/**
	 *  Set subtabs
	 * @param string $a_tab    Parent tab
	 * @access protected
	 */
	protected function initCategoriesSubtabs($a_tab)
	{
		$this->noticeRepository = new ilNoticeRepository($this->object);

		if($a_tab == 'content')
		{
			$next_cmd        = 'showBoard';
			$count_all_posts = $this->noticeRepository->countCurrent();
		}
		else
		{
			$next_cmd = $a_tab;

			$count_own_entries_only = true;
			$count_all_posts        = $this->noticeRepository
				->countByUserAndCategory($this->user->getId(), 0, $count_own_entries_only);
		}

		$cmd = 'setFilterBySubtab';

		if($_SESSION['xnob_filter']['cat_id'])
		{
			$cat_id = (int)$_SESSION['xnob_filter']['cat_id'];
		}
		else if(isset($_GET['category_id']) && (int)$_GET['category_id'] == 0)
		{
			$cat_id = (int)$_GET['category_id'];
		}
		else
		{
			$cat_id = 0;
		}

		/* Add "All postings" tab. */
		$this->ctrl->setParameter($this, 'category_id', ilNoticeCategory::NOTICE_CATEGORY_ALL);
		$this->ctrl->setParameter($this, 'next_cmd', $next_cmd);

		$this->tabs->addSubTab(
			"subtab_ALL",
			$this->txt('all_notices') . "($count_all_posts)",
			$this->ctrl->getLinkTarget($this, $cmd));

		$currentCategoryId = null;
		foreach(ilNoticeCategory::getList($this->object->getId()) as $category)
		{
			if($this->currentCategoryId == ilNoticeCategory::NOTICE_CATEGORY_ALL) // && $a_tab == 'showMyNotices')
			{
				/* Change current category if tab 'All Postings' was selected */
				$this->currentCategoryId = $category->getCategoryId();
			}

			if($a_tab == 'showMyNotices')
			{
				// my_notices-tab ---> count own entries only!!
				$countEntries = $this->noticeRepository
					->countByUserAndCategory($this->user->getId(), $category->getCategoryId(), $count_own_entries_only);
			}
			else
			{
				// content-tab ---> count all notices
				/* One tab for one category. */
				$countEntries = $this->noticeRepository
					->countByUserAndCategory($this->user->getId(), $category->getCategoryId());
			}

			$categoryTitle = $category->getCategoryTitle();
			if($this->txt($category->getCategoryTitle()) != sprintf("-rep_robj_xnob_%s-", $category->getCategoryTitle()))
				$categoryTitle = $this->txt($category->getCategoryTitle());

			$categoryTitle = sprintf("%s (%d)",
				$categoryTitle,
				$countEntries);

			$this->ctrl->setParameter($this, 'category_id', $category->getCategoryId());

			if($a_tab == 'showMyNotices')
			{
				if(!ilObjPermission::hasWriteAccess($this->user->getId(), $category->getCategoryId()))
				{
					$show_cat_subtab = false;
				}
				else
				{
					$show_cat_subtab = true;
				}
			}
			else
			{
				$show_cat_subtab = true;
			}

			if($show_cat_subtab == true)
			{
				$this->tabs->addSubTab(
					'subtab_' . $category->getCategoryId(),
					$categoryTitle,
					$this->ctrl->getLinkTarget($this, $cmd));
			}
		}

		if((int)$cat_id > 0)
		{
			$this->tabs->activateSubTab('subtab_' . $cat_id);
		}
		else
		{
			$this->tabs->setSubTabActive("subtab_ALL");
		}
	}

	/**
	 * Init form.
	 * @access protected
	 */
	protected function initPropertiesForm()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$this->formGui = new ilPropertyFormGUI();

		// title
		$field = new ilTextInputGUI($this->txt('title'), 'title');
		$field->setRequired(true);
		$this->formGui->addItem($field);

		// description
		$field = new ilTextAreaInputGUI($this->txt('description'), 'desc');
		$this->formGui->addItem($field);

		// Validity
		$field = new ilNumberInputGUI($this->txt('validity'), 'validity');
		$field->setRequired(true);
		$field->setMinValue(1);
		$field->setInfo($this->txt('validity_in_days'));
		$field->setSize(5);
		$this->formGui->addItem($field);

		$this->formGui->addCommandButton('updateProperties', $this->txt('save'));

		$this->formGui->setTitle($this->txt('edit_properties'));
		$this->formGui->setFormAction($this->ctrl->getFormAction($this));
	}

	/**
	 * Get values for edit properties form
	 * @access protected
	 */
	protected function getPropertiesValues()
	{
		$values['title']    = $this->object->getTitle();
		$values['desc']     = $this->object->getDescription();
		$values['currency'] = $this->object->getCurrency();
		$values['validity'] = $this->object->getValidity();

		$this->formGui->setValuesByArray($values);
	}

	/**
	 *    Initialize the categories creation/edition form.
	 *    This method prepares the categories creation form
	 *    and injects it into the current instance.
	 */
	protected function initCategoriesForm($mode = 'create')
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$this->formGui = new ilPropertyFormGUI;
		if($mode == 'create')
		{
			$this->formGui->setTitle($this->lng->txt('add'));
		}
		elseif($mode == 'update')
		{
			$this->formGui->setTitle($this->lng->txt('edit'));
		}

		$title = new ilTextInputGUI($this->txt('title'), 'category_title');
		$title->setRequired(true);
		$this->formGui->addItem($title);

		$desc = new ilTextAreaInputGUI($this->txt('description'), 'category_description');
		$this->formGui->addItem($desc);

		$price = new ilCheckboxInputGUI($this->txt('price_on_objects'), 'price_enabled');
		$price->setInfo($this->txt('price_enabled_info'));
		$this->formGui->addItem($price);

		if($mode == 'update')
		{
			/* Update mode. */
			$catObj = new ilNoticeCategory($this->getCurrentCategoryId());
			$input  = new ilHiddenInputGUI('category_id');
			$input->setValue($this->getCurrentCategoryId());

			$this->formGui->addItem($input);

			$this->formGui->setValuesByArray($catObj->convertToArray());
		}

		$this->formGui->addCommandButton('updateCategories', $this->txt('save'));
		$this->formGui->addCommandButton('showCategories', $this->txt('cancel'));

		$this->formGui->setFormAction($this->ctrl->getFormAction($this, 'updateCategories'));
	}

	/**
	 *    Initialize the categories list table.
	 *    This method prepares the categories list and injects
	 *    the table into the current instance.
	 */
	protected function initCategoriesList()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$this->pluginObj->includeClass("class.ilCategoriesTableGUI.php");

		$this->catListGui = new ilCategoriesTableGUI($this, "showCategories");
		$this->catListGui->setData(ilNoticeCategory::getList($this->object->getId(), true));
	}

	/**
	 * Edit Properties.
	 * This commands uses the form class to display an input form.
	 * @access protected
	 */
	protected function editProperties()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$count_categories = ilNoticeCategory::countCategoriesByObjId($this->obj_id);
		if($count_categories == 0)
		{
			ilUtil::sendInfo($this->pluginObj->txt('please_create_categories'));
			return $this->showCategories();
		}

		$this->tabs->activateTab('properties');
		$this->initPropertiesForm();
		$this->getPropertiesValues();

		$form_2 = new ilPropertyFormGUI();
		$form_2->setTitle($this->pluginObj->txt('manage_inactive_posts'));
		$form_2->setFormAction($this->ctrl->getFormAction($this, 'confirmDeleteHiddenPosts'));
		$form_2->setId('frm_prop_clean_' . $this->ref_id);
		$form_2->addCommandButton('confirmDeleteHiddenPosts', $this->lng->txt('delete'));

		$info = new ilNonEditableValueGUI();
		$info->setValue($this->pluginObj->txt('delete_hidden_posts_info'));
		$form_2->addItem($info);

		$days = new ilNumberInputGUI($this->lng->txt('days'), 'delete_days');
		$days->setInfo($this->pluginObj->txt('delete_hidden_posts_days_info'));
		$days->setValue($this->object->getValidity());
		$form_2->addItem($days);

		$html = $this->formGui->getHTML();

		$html .= '<p>' . $form_2->getHTML() . '</p>';

		$this->tpl->setContent($html);
	}

	/**
	 * Update properties
	 * @access protected
	 */
	protected function updateProperties()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$this->tabs->activateTab('properties');

		$this->initPropertiesForm();

		if($this->formGui->checkInput())
		{
			$this->object->setTitle($this->formGui->getInput('title'));
			$this->object->setDescription($this->formGui->getInput('desc'));
			$this->object->setCurrency($this->formGui->getInput('currency'));
			$this->object->setValidity($this->formGui->getInput('validity'));
			$this->object->update();
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			$this->ctrl->redirect($this, 'editProperties');
		}

		$this->formGui->setValuesByPost();
		$this->tpl->setContent($this->formGui->getHtml());
	}

	public function confirmDeleteHiddenPosts()
	{
		$this->tabs->activateTab('properties');

		include_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
		$confirm = new ilConfirmationGUI();
		$confirm->setHeaderText($this->txt('confirm_delete_old_notices'));
		$this->noticeRepository = new ilNoticeRepository($this->object);

		$confirm->setCancel($this->lng->txt('cancel'), 'editProperties');
		$confirm->setConfirm($this->lng->txt('confirm'), 'deleteHiddenPosts');
		$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
		$this->ctrl->setParameter($this, 'ddays', $_POST['delete_days']);
		$this->ctrl->setParameter($this, 'tab', $_GET['tab']);
		$confirm->setFormAction($this->ctrl->getFormAction($this, 'deleteHiddenPosts'));
		$this->tpl->setContent($confirm->getHTML());
	}

	public function deleteHiddenPosts()
	{
		if(!$this->checkModeratePermission())
		{
			ilUtil::sendFailure($this->lng->txt('no_permission'));
			$this->editProperties();
		}
		else
		{
			$this->tabs->activateTab('properties');

			$delete_days = $_GET['ddays'];
			if(isset($delete_days) && (int)$delete_days >= 0)
			{
				ilNotice::deleteHiddenPosts($this->object_id, $delete_days);

				ilUtil::sendSuccess($this->txt('deleted_successfully'));
				$this->editProperties();
			}
			else
			{
				ilUtil::sendFailure($this->lng->txt('no_permission'));
				$this->editProperties();
			}
		}
	}

	/**
	 *    Command for editing the Noticeboard categories.
	 *    This command is executed to display a form
	 *    to allow the user to publish categories
	 *    on their Noticeboard.
	 */
	protected function showCategories()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$count_categories = ilNoticeCategory::countCategoriesByObjId($this->obj_id);
		if($count_categories == 0)
		{
			ilUtil::sendInfo($this->pluginObj->txt('please_create_categories'));
		}
		$this->tabs->activateTab('categories');

		$this->initCategoriesList();
		$this->tpl->setContent($this->catListGui->getHTML());
	}

	/**
	 *    Command for editing a single category.
	 *    This method is used internally as a command
	 *    to provide the user with the form filled with
	 *    the selected category's data.
	 */
	protected function editCategory()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		$this->tabs->activateTab('categories');
		$this->initCategoriesForm('update');
		$this->tpl->setContent($this->formGui->getHTML());
	}

	/**
	 *    Command for processing the categories management.
	 *    This method is executed to process the categories
	 *    management form content. This method inserts or
	 *    updates the corresponding database entry.
	 */
	protected function updateCategories()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		if(isset($_POST['category_id']) && (int)$_POST['category_id'] > 0)
		{
			$mode = 'update';
		}
		else
		{
			$mode = 'create';
		}

		$this->initCategoriesForm($mode);
		if($this->formGui->checkInput())
		{
			$this->plugin->includeClass('class.ilNoticeRepository.php');

			$checkedPrice = $this->formGui->getInput('price_enabled');
			$checkedPrice = empty($checkedPrice) ? "0" : "1";

			if($mode == 'update')
			{
				$category = new ilNoticeCategory($this->formGui->getInput('category_id'));
			}
			else
			{
				$category = new ilNoticeCategory();
			}

			$category->obj_id               = $this->object->getId();
			$category->category_title       = $this->formGui->getInput('category_title');
			$category->category_description = $this->formGui->getInput('category_description');
			$category->price_enabled        = $checkedPrice;

			if($mode == 'update')
			{
				$category->updateCategory();
			}
			else
			{
				$new_cat_id = $category->insertCategory();

				//insert category permissions
				$objPermissions = new ilObjPermission();
				$objPermissions->setCategoryId($new_cat_id);
				$objPermissions->setObjId($this->obj_id);
				$objPermissions->doAfterCreate($this->ref_id);
			}

			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			$this->ctrl->redirect($this, 'showCategories');
		}

		$this->formGui->setValuesByPost();
		$this->tpl->setContent($this->formGui->getHtml());
	}

	/**
	 * Show notice board
	 * @access protected
	 */
	protected function showBoardAction()
	{
		$this->initCategoriesSubtabs('content');

		if($this->getPermission('moderate'))
		{
			$this->showFilter();
		}
		$this->noticeRepository = new ilNoticeRepository($this->object);
		ilNotice::performValidityChecks($this->obj_id);

		$this->pluginObj->includeClass('class.ilBoardTableGUI.php');
		$table = new ilBoardTableGUI($this, 'showBoard');

		if($this->isFilterEnabled())
		{
			$nb_cat_ids = ilNoticeCategory::getCatIdsByObjId($this->object->getId());
			if(!isset($_SESSION['xnob_filter']['cat_id']) || !(int)$_SESSION['xnob_filter']['cat_id'] || !in_array((int)$_SESSION['xnob_filter']['cat_id'], $nb_cat_ids))
			{
				$this->currentCategoryId = 0;
			}
			else
			{
				// check category permissions
				if((int)$_SESSION['xnob_filter']['cat_id'] > 0 && ilObjPermission::hasWriteAccess($this->user->getId(), (int)$_SESSION['xnob_filter']['cat_id']))
				{
					$this->currentCategoryId = (int)$_SESSION['xnob_filter']['cat_id'];
					$table->setWriteAccess(true);
				}
				else
				{
					$this->currentCategoryId = 0;
				}
			}

			if(!isset($_SESSION['xnob_filter']['status']) || $_SESSION['xnob_filter']['status'] == 'all')
			{
				$hidden_status = 'both';
			}
			else
			{
				if($this->getPermission('moderate'))
				{
					$hidden_status = (int)$_SESSION['xnob_filter']['status'];
				}
				else
				{
					$hidden_status = 0;
					$table->hideColumn('nt_hidden');
				}
			}
		}
		else
		{
			$this->currentCategoryId           = $_SESSION['xnob_filter']['cat_id'];
			$_SESSION['xnob_filter']['status'] = 0;
		}

		if($this->currentCategoryId != ilNotice::NOTICE_CATEGORY_ALL)
		{
			$notices = $this->noticeRepository->findCurrentByCategory($this->currentCategoryId, $this->isFilterEnabled(), $hidden_status);
		}
		else
		{
			$notices = $this->noticeRepository->findCurrent($this->isFilterEnabled(), $hidden_status);
		}

		// check category permissions
		if($this->currentCategoryId > 0 && ilObjPermission::hasWriteAccess($this->user->getId(), $this->currentCategoryId))
		{
			$table->setWriteAccess(true);
		}

		$table->init($this->currentCategoryId);
		$table->setData($this->buildNoticeDataArray($notices));

		$html = '';
		$html .= $this->filter;
		$html .= '<br>';
		$html .= $table->getHtml();

		$this->tpl->setContent($html);
	}

	/**
	 * Show user's notices
	 * @access protected
	 */
	protected function showMyNoticesAction()
	{
		$this->initCategoriesSubtabs('showMyNotices');

		$this->pluginObj->includeClass('class.ilBoardTableGUI.php');
		$table = new ilBoardTableGUI($this, 'showMyNotices');

		$this->showFilter(true);
		if($this->isFilterEnabled())
		{
			if(!isset($_SESSION['xnob_filter']['cat_id']) || !(int)$_SESSION['xnob_filter']['cat_id'])
			{
				$this->currentCategoryId = 0;
			}
			else
			{
				// check category permissions
				if((int)$_SESSION['xnob_filter']['cat_id'] > 0 && ilObjPermission::hasWriteAccess($this->user->getId(), (int)$_SESSION['xnob_filter']['cat_id']))
				{
					$this->currentCategoryId = (int)$_SESSION['xnob_filter']['cat_id'];
					$table->setWriteAccess(true);
				}
				else
				{
					$this->currentCategoryId = 0;
				}
			}

			if(!isset($_SESSION['xnob_filter']['status']) || $_SESSION['xnob_filter']['status'] == 'all')
			{
				$hidden_status = 'both';
			}
			else
			{
				$hidden_status = (int)$_SESSION['xnob_filter']['status'];
			}
		}
		else
		{
			$this->currentCategoryId           = $_SESSION['xnob_filter']['cat_id'];
			$_SESSION['xnob_filter']['status'] = 0;
		}
		$this->noticeRepository = new ilNoticeRepository($this->object);
		$notices                = $this->noticeRepository->findByUserAndCategory($this->user->getId(), $this->currentCategoryId, $hidden_status);

		// check category permissions
		if($this->currentCategoryId > 0 && ilObjPermission::hasWriteAccess($this->user->getId(), $this->currentCategoryId))
		{
			$table->setWriteAccess(true);
		}
		$table->init($this->currentCategoryId);
		$title = $table->title;
		$title = $this->pluginObj->txt('my_notices') . ': ' . $title;
		$table->setTitle($title);
		$table->setData($this->buildNoticeDataArray($notices));

		$html = '';
		$html .= $this->filter;
		$html .= '<br>';
		$html .= $table->getHtml();
		$this->tpl->setContent($html);
	}

	/**
	 *    Format array of data to be passed to a TableGUI
	 *    This method has been implemented to group
	 *    the DATA array creation algorithm. It is used
	 *    internally by the command showBoard and showMyNotices.
	 * @params    array    $notices    array of ilNotice objects.
	 * @return array
	 */
	protected function buildNoticeDataArray(array $notices)
	{
		$data = array();
		$i    = 0;
		foreach($notices as $notice)
		{
			$this->ctrl->setParameter($this, 'notice_id', $notice->getId());
			$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
			$this->ctrl->setParameter($this, 'tab', 'showMyNotices');

			$data[$i] = $notice->getData();

			/* Images need special treatment.. */
			$image = ilObjNoticeImage::lookupSelectedFilename($notice->getId());
			if($image != '')
			{
				$data[$i]['image']     = ilUtil::getWebspaceDir() . '/xnob/img_thumbnail/' . $image;
				$data[$i]['image_css'] = 'xnob-list-image';
			}
			else
			{
				$data[$i]['image']     = $this->pluginObj->getDirectory() . '/templates/images/icon_no_image.png';
				$data[$i]['image_css'] = 'xnob-list-no-image';
			}
			$this->ctrl->setParameter($this, 'notice_id', '');
			$i++;
		}

		return $data;
	}

	/**
	 * Show notice
	 * @access protected
	 */
	protected function showAction($show_js_box = true)
	{
		global $ilToolbar, $https, $ilCtrl;

		if($show_js_box == true)
		{
			$is_hidden_or_deleted = ilNotice::isHiddenDeleted((int)$_GET['notice_id']);
			if($is_hidden_or_deleted)
			{
				$show_js_box = false;
			}
		}

		if($show_js_box == true)
		{
			if(version_compare(ILIAS_VERSION_NUMERIC, '4.2.0') >= 0)
			{
				include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
				iljQueryUtil::initjQuery();
				iljQueryUtil::initjQueryUI();
			}
			else
			{
				$scheme = "http";
				if($https->isDetected())
					$scheme = "https";

				$this->tpl->addJavaScript("$scheme://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js");
			}
		}
		$tpl = new ilTemplate('tpl.notice_details.html', TRUE, TRUE, $this->pluginObj->getDirectory());

		$this->noticeRepository = new ilNoticeRepository($this->object);

		if($ilCtrl->getCmd() == 'showMyNotice')
		{
			$notice = $this->noticeRepository->findById($_GET['notice_id']);
			if($notice->getUserId() != $this->user->getId())
			{
				/* Only owner of the notice may see it at 'my notices' */
				$notice = NULL;
			}
		}
		else
		{
			$is_admin = false;
			if($this->checkModeratePermission())
			{
				$is_admin = true;
			}

			$notice = $this->noticeRepository->findCurrentById($_GET['notice_id'], $is_admin);

			// no permission or notice is hidden or deleted
			if($notice == false)
			{
				return $this->showBoardAction();
			}
		}

		if($notice === NULL)
		{
			return $this->showBoardAction();
		}

		$data = array(
			'nt_id'          => $notice->getId(),
			'nt_title'       => $notice->getTitle(),
			'nt_description' => nl2br($notice->getDescription()),
			'user_name'      => $notice->getusername(),
			'nt_user_phone'  => $notice->getUserPhone(),
			'nt_user_email'  => $notice->getUserEmail(),
			'nt_create_date' => ilNoticeboardUtil::formatDate($notice->getCreateDate()),
			'nt_expire_date' => ilNoticeboardUtil::formatDate($notice->getUntilDate()),
			'nt_mod_date'    => ilNoticeboardUtil::formatDate($notice->getModDate())

		);

		if($notice->getLocation() != '')
		{
			$data['location'] = $notice->getLocation();
			$tpl->setVariable('TXT_LOCATION', $this->txt('location'));
		}
		if($notice->getPrice())
		{
			$itemPrice = ilNoticeboardUtil::formatPrice($notice->getPrice(), $this->object->getCurrency(), $notice->getPriceType());
			$tpl->setVariable('TXT_PRICE', $this->txt('price'));
			$tpl->setVariable('NT_PRICE', $itemPrice);
		}

		if($notice->isHidden())
		{
			$tpl->setVariable('TXT_HIDDEN', $this->txt('hidden'));
		}
		if($notice->getUntilDate() <= time())
		{
			$tpl->setVariable('TXT_EXPIRED', $this->txt('expired'));
		}

		$this->tpl->addJavaScript($this->pluginObj->getDirectory() . '/js/fancybox/source/jquery.fancybox.pack.js');
		$this->tpl->addCss($this->pluginObj->getDirectory() . '/js/fancybox/source/jquery.fancybox.css');
		$this->tpl->addJavaScript($this->pluginObj->getDirectory() . '/js/jquery.bxslider/jquery.bxslider.min.js');
		$this->tpl->addCss($this->pluginObj->getDirectory() . '/js/jquery.bxslider/jquery.bxslider.css');

		$tpl->setCurrentBlock('additional_images');

		$id          = 0;
		$objFileData = new ilFileDataNoticeboard();

		$this->pluginObj->includeClass('class.ilObjNoticeImage.php');
		$objFile = new ilObjNoticeImage($notice->getId());
		$objFile->setFileType(ilObjNoticeImage::IMAGE);
		$additional_images = $objFile->getImgFiles();

		if(count($additional_images))
		{
			foreach($additional_images as $img)
			{
				$no_existing_files = array();
				$image_source      = $objFileData->getImagePath() . '/' . $img['filename'];
				if(!file_exists($image_source))
				{
					$no_existing_files[$img['image_id']] = $img['image_id'];
					continue;
				}
				$imageSize = getImageSize($image_source);
				if(!ilNoticeRepository::existsPreviewImage($image_source))
				{
					if($imageSize[0] > ilNoticeboardConfig::getSetting('img_preview_width') || $imageSize[1] > ilNoticeboardConfig::getSetting('img_preview_height'))
					{
						$preview_img = ilNoticeRepository::createPreviewImage($image_source);
					}
					else
					{
						$preview_img = ilNoticeRepository::createPreviewImage($image_source, $imageSize[0], $imageSize[1]);
					}
				}
				else
				{
					$preview_img = ilNoticeRepository::getPreviewImage($image_source);
				}
				$tpl->setVariable('ID', $id);
				$tpl->setVariable('IMAGES', $preview_img);

				$imageSize_preview = getImageSize($preview_img);

				$tpl->setVariable('IMAGES_WIDTH', $imageSize_preview[0]);
				$tpl->setVariable('IMAGES_HEIGHT', $imageSize_preview[1]);
				$tpl->setVariable('IMAGES_LINK_LARGE_VIEW', $preview_img);
				$tpl->parseCurrentBlock();
				$id++;
			}

			if(is_array($no_existing_files) && count($no_existing_files) > 0)
			{
				$objFile->deleteFiles($no_existing_files);
			}
		}

		if($id > 0)
		{
			$tpl->setVariable('DESCRIPTION_MARGIN_LEFT', 500);
		}
		else
		{
			$tpl->setVariable('DESCRIPTION_MARGIN_LEFT', 0);
		}

		$objFileData_2 = new ilFileDataNoticeboard();

		$this->pluginObj->includeClass('class.ilObjNoticeImage.php');
		$objFile_2 = new ilObjNoticeImage($notice->getId());
		$objFile_2->setFileType(ilObjNoticeImage::DOCUMENT);
		$additional_files = $objFile_2->getDocFiles();

		$id = 0;

		$tpl->setCurrentBlock('additional_files');
		if(count($additional_files))
			$tpl->setVariable('TXT_ADDITIONAL_DOCUMENTS', $this->pluginObj->txt('additional_files'));
		foreach($additional_files as $img)
		{
			$this->ctrl->setParameter($this, 'file_id', $img['image_id']);
			$image_source = $this->ctrl->getLinkTarget($this, 'deliverDocument');

			$tpl->setVariable('F_ID', $id);
			$tpl->setVariable('FILE', $image_source);
			$tpl->setVariable('FILE_NAME', $img['filename']);

			$tpl->parseCurrentBlock();
			$id++;
		}

		foreach($data as $key => $value)
		{
			$tpl->setVariable(strtoupper($key), $value);
		}

		if($data['nt_user_phone'] != '')
		{
			$tpl->setVariable('TXT_PHONE', $this->txt('phone'));
		}

		if($show_js_box == true && ($notice->getUserId() != $this->user->getId()))
		{
			/* Fill several language template variables. */
			foreach(array(
						'create_date', 'expire_date', 'user', 'description',
						'report_notice', 'recommend_notice', 'print_notice',
						'please_select', 'spam', 'prohibited', 'other',
						'give_reason', 'close_window', 'cancel', 'report',
						'notice_reported_successfully', 'comment_missing',
						'recommend', 'to', 'your_message', 'optional',
						'notice_recommended_successfully', 'recipient_missing'
					) as $lng_key)
			{

				$tpl_field = "TXT_" . strtoupper($lng_key);
				$tpl->setVariable($tpl_field, $this->txt($lng_key));
			}

			$tpl->setVariable('REPORT_AJAX_URL', $this->ctrl->getFormAction($this, 'reportNotice', '', TRUE, FALSE));
			$tpl->setVariable('RECOMMEND_AJAX_URL', $this->ctrl->getFormAction($this, 'recommendNotice', '', TRUE, FALSE));

			/* User field autocomplete */
			$dsDataLink = $this->ctrl->getLinkTarget($this, 'lookupUsersAsync', '', true, false);
			$tpl->setVariable("SEL_AUTOCOMPLETE", "#xnob-details-form-recommend-notice-recipient");
			$tpl->setVariable("TXT_RECIPIENT_INFO", $this->pluginObj->txt('recommend_rcp_info'));

			$tpl->setVariable("URL_AUTOCOMPLETE", $dsDataLink);
		}
		else
		{
			/* Fill several language template variables. */
			foreach(array('create_date', 'expire_date', 'user', 'description') as $lng_key)
			{
				$tpl_field = "TXT_" . strtoupper($lng_key);
				$tpl->setVariable($tpl_field, $this->txt($lng_key));
			}
		}

		$this->ctrl->setParameter($this, 'category_id', 0);

		if($_SESSION['activeTab'] == 'showMyNotices')
		{
			$cmd = 'showMyNotices';
		}
		else
		{
			$cmd = 'showBoard';
		}

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, $cmd));

		if(($notice->getUserId() != $this->user->getId()) && $show_js_box == true)
		{
			$ilToolbar->addSeparator();

			$this->ctrl->setParameter($this, 'notice_id', $notice->getId());
			$ilToolbar->addButton($this->txt('contact_user'), $this->ctrl->getLinkTarget($this, 'contact'));
		}

		$this->tpl->setContent($tpl->get());
	}

	public function showMyNoticeAction()
	{
		$this->showAction(false);
	}

	/**
	 * Search ILIAS users by input of autocomplete field
	 */
	public function lookupUsersAsyncAction()
	{
		if(!isset($_GET['autoCompleteField']))
		{
			$a_fields     = array('login', 'firstname', 'lastname', 'email');
			$result_field = 'login';
		}
		else
		{
			$a_fields     = array((string)$_GET['autoCompleteField']);
			$result_field = (string)$_GET['autoCompleteField'];
		}

		include_once './Services/User/classes/class.ilUserAutoComplete.php';
		$auto = new ilUserAutoComplete();
		$auto->setSearchFields($a_fields);
		$auto->setResultField($result_field);
		$auto->enableFieldSearchableCheck(true);
		echo $auto->getList($_REQUEST['term']);
		exit();
	}

	/**
	 * Report notice to noticeboard owner if it's SPAM or a prohibited article
	 */
	protected function reportNoticeAction()
	{
		$this->notice = new ilNotice();
		if((int)$_POST['notice_id'] > 0)
		{
			$this->noticeRepository = new ilNoticeRepository($this->object);
			$this->notice           = $this->noticeRepository->findCurrentById((int)$_POST['notice_id']);
			if($this->notice->getId() == (int)$_POST['notice_id'])
			{
				$rcpId     = $this->object->getOwner();
				$recipient = ilObjUser::_lookupLogin($rcpId);
				if($recipient != '')
				{
					if($_POST['reason'] == 'OTHER')
					{
						$reason = $_POST['comment'];
					}
					else
					{
						$reason = $this->txt(strtolower($_POST['reason']));
					}
					$message = str_replace('###br###', "\n", sprintf($this->txt('report_notice_message'),
						$this->user->getFirstname() . ' ' . $this->user->getLastname(),
						$this->user->getEmail(),
						$this->object->getTitle(),
						$reason,
						$this->notice->getTitle(),
						$this->notice->getDescription()));

					include_once 'Services/Mail/classes/class.ilMail.php';
					$mail = new ilMail(ANONYMOUS_USER_ID);
					$mail->sendMail($recipient, '', '', $this->txt('report_notice') . ': ' . strip_tags($this->notice->getTitle()), strip_tags($message), array(), array('normal'));
				}
			}
		}
		exit();
	}

	/**
	 * Recommend notice to an (ILIAS) user
	 */
	protected function recommendNoticeAction()
	{
		$response          = new stdClass();
		$response->success = 0;
		$response->message = '';

		$this->notice = new ilNotice();
		if((int)$_POST['notice_id'] > 0)
		{
			$this->noticeRepository = new ilNoticeRepository($this->object);
			$this->notice           = $this->noticeRepository->findCurrentById((int)$_POST['notice_id']);
			if($this->notice->getId() == (int)$_POST['notice_id'])
			{
				$recipient = ilUtil::stripSlashes($_POST['recipient']);
				$rcpId     = ilObjUser::_lookupId($recipient);
				if($rcpId)
				{
					$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
					$link = ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTarget($this, 'show');

					include_once 'Services/Mail/classes/class.ilMail.php';
					$mail = new ilMail(ANONYMOUS_USER_ID);

					$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
					$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
					$this->ctrl->setParameter($this, 'tab', 'content');

					$message           = '';
					$noticeboard_title = ilObject::_lookupTitle($this->obj_id);

					$message .= "\n";
					$message .= sprintf($this->txt('mail_sender_info'),
						$this->user->getFirstname(),
						$this->user->getLastname(),
						$this->user->getEmail(),
						$noticeboard_title);
					$message .= "\n\n";
					$message .= "------------------------------------------------------------\n\n";
					$message .= $this->notice->getTitle() . "\n";
					$message .= $this->notice->getDescription() . "\n\n";
					$message .= $this->txt('find_id_here') . "\n";
					$message .= ilUtil::_getHttpPath() . '/' . $this->ctrl->getLinkTarget($this, 'show', '', true, false) . "\n";
					$message .= "------------------------------------------------------------\n\n";

					if(isset($_POST['message']) && strlen($_POST['message']))
					{
						$message .= $this->txt('message_for_you') . "\n";
						$message .= ($_POST['message'] != '' ? $_POST['message'] : $this->txt('none')) . "\n";
						$message .= "------------------------------------------------------------\n";
					}
					$message .= ilMail::_getInstallationSignature();

					$mail->sendMail($recipient, '', '', $this->txt('recommend_notice') . ': ' . strip_tags($this->notice->getTitle()), $message, array(), array('normal'));
					$response->success = 1;
				}
				else
				{
					$response->message           = $this->txt('recipent_not_found');
					$response->message_image_src = ilUtil::getImagePath('mess_failure.png');
				}
			}
			else
			{
				$response->message           = $this->txt('recommendation_failed');
				$response->message_image_src = ilUtil::getImagePath('mess_failure.png');
			}
		}
		else
		{
			$response->message           = $this->txt('recommendation_failed');
			$response->message_image_src = ilUtil::getImagePath('mess_failure.png');
		}
		echo json_encode($response);
		exit();
	}

	/**
	 * Asks user for confirmation to delete one notice
	 * @access protected
	 */
	protected function confirmDeleteOneAction()
	{
		$delete = array((int)$_GET['notice_id']);
		$this->confirmDeleteAction($delete);
	}

	/**
	 * Asks user for confirmation to delete notices
	 * @param array $delete (optional) List of ids of notices to be deleted
	 * @access protected
	 */
	protected function confirmDeleteAction($delete = NULL)
	{
		$this->tabs->setTabActive($_SESSION['activeTab']);
		$this->currentCategoryId = $this->getCurrentCategoryId();
		$cmd                     = $_GET['next_cmd'];

		if($delete === NULL)
		{
			$delete = (array)$_POST['notice_id'];
		}
		if(is_array($delete) && !empty($delete))
		{
			include_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
			$confirm = new ilConfirmationGUI();
			$confirm->setHeaderText($this->txt('confirm_delete_notices'));
			$this->noticeRepository = new ilNoticeRepository($this->object);
			foreach($delete as $id)
			{
				$notice = $this->noticeRepository->findById((int)$id);
				$confirm->addItem('notice_id[]', $notice->getId(), $notice->getTitle());
			}
			$confirm->setCancel($this->lng->txt('cancel'), $cmd);
			$confirm->setConfirm($this->lng->txt('confirm'), 'delete');
			$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
			$this->ctrl->setParameter($this, 'tab', $_GET['tab']);
			$confirm->setFormAction($this->ctrl->getFormAction($this, $cmd));
			$this->tpl->setContent($confirm->getHTML());
		}
	}

	/**
	 * Asks user for confirmation to delete one category
	 * @access protected
	 */
	protected function confirmDeleteOneCategoryAction()
	{
		$delete = array((int)$_GET['category_id']);
		$this->confirmDeleteCategoryAction($delete);
	}

	/**
	 * Asks user for confirmation to delete categories
	 * @param array $delete (optional) List of ids of categories to be deleted
	 * @access protected
	 */
	protected function confirmDeleteCategoryAction($delete = NULL)
	{
		$this->tabs->setTabActive('categories');
		$this->currentCategoryId = $this->getCurrentCategoryId();
		$cmd                     = 'showCategories';

		if($delete === NULL)
		{
			$delete = (array)$_POST['category_id'];
		}
		if(is_array($delete) && !empty($delete))
		{
			include_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
			$confirm = new ilConfirmationGUI();
			$confirm->setHeaderText($this->txt('confirm_delete_notices'));
			foreach($delete as $id)
			{
				$category = new ilNoticeCategory($id);
				$confirm->addItem('category_id[]', $category->getCategoryId(), $category->getCategoryTitle());
			}
			$confirm->setCancel($this->lng->txt('cancel'), $cmd);
			$confirm->setConfirm($this->lng->txt('confirm'), 'deleteCategory');
			$this->ctrl->setParameter($this, 'tab', $_GET['tab']);
			$confirm->setFormAction($this->ctrl->getFormAction($this, $cmd));
			$this->tpl->setContent($confirm->getHTML());
		}
	}

	/**
	 * Deletes one or more notices.
	 * @access protected
	 */
	protected function deleteAction()
	{
		$this->currentCategoryId = $this->getCurrentCategoryId();
		$cmd                     = $_GET['next_cmd'];

		$delete = (array)$_POST['notice_id'];
		if(is_array($delete) && !empty($delete))
		{
			$this->noticeRepository = new ilNoticeRepository($this->object);
			foreach($delete as $id)
			{
				$notice = $this->noticeRepository->findById((int)$id);
				// Notice may only be deleted if
				// - user is admin or owner of the notice board object or
				// - owner of the notice
				if($this->getPermission('moderate') || $notice->getUserId() == $this->user->getId())
				{
					$this->noticeRepository->remove($notice);
				}
			}
		}
		$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, $cmd, '', FALSE, FALSE));
	}

	/**
	 * Deletes one or more categories.
	 * @access protected
	 */
	protected function deleteCategoryAction()
	{
		if(!$this->checkModeratePermission())
		{
			ilUtil::sendFailure($this->lng->txt('no_permission'));
			$this->ctrl->redirect($this, 'showBoard');
		}

		$cmd    = 'showCategories';
		$delete = (array)$_POST['category_id'];

		if(is_array($delete) && !empty($delete))
		{
			$category = new ilNoticeCategory();

			if($this->checkOwner() || $this->checkWritePermission())
			{
				$category->deleteCategories($delete);
			}
		}

		ilUtil::redirect($this->ctrl->getLinkTarget($this, $cmd, '', FALSE, FALSE));
	}

	/**
	 * Toggle status of one notices.
	 * @access protected
	 */
	protected function toggleStatusOneAction()
	{
		$toggle = array((int)$_GET['notice_id']);
		$this->toggleStatusAction($toggle);
	}

	/**
	 * Toggle status of notices.
	 * @param array $toggle (optional) List of ids of notices to be de-/activated
	 * @access protected
	 */
	protected function toggleStatusAction($toggle = NULL)
	{
		$this->currentCategoryId = $this->getCurrentCategoryId();
		$cmd                     = $_GET['next_cmd'];

		if($toggle === NULL)
		{
			$toggle = (array)$_POST['notice_id'];
		}
		if(is_array($toggle) && !empty($toggle))
		{
			$this->noticeRepository = new ilNoticeRepository($this->object);
			foreach($toggle as $id)
			{
				$notice = $this->noticeRepository->findById((int)$id);
				// Notice' status may only be toggled if
				// - user is admin or owner of the notice board object or
				// - owner of the notice
				if($this->getPermission('moderate') || $notice->getUserId() == $this->user->getId())
				{
					if($notice->isHidden() || $this->object->isNoticeExpired($notice->getModDate()))
					{
						$notice->setHidden(0);
					}
					else
					{
						$notice->setHidden(1);
					}
					$notice->setModDate(time());
					$this->noticeRepository->update($notice);
				}
			}
		}
		$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
		$this->ctrl->setParameter($this, 'cmd', $cmd);
		ilUtil::redirect($this->ctrl->getLinkTarget($this, $cmd, '', FALSE, FALSE));
	}

	/**
	 * Creates a notice
	 * @access protected
	 * @see saveForm()
	 */
	protected function createAction()
	{
		$this->mode   = self::MODE_CREATE;
		$this->notice = new ilNotice();

		$d = date('d', time());
		$m = date('m', time());
		$y = date('Y', time());

		$until_date = mktime(23, 59, 59, $m, $d + $this->object->getValidity(), $y);
		$this->notice->setUntilDate($until_date);

		$this->saveForm();
	}

	/**
	 * Updates a notice
	 * @access protected
	 * @see saveForm()
	 */
	protected function updateAction()
	{
		$this->noticeRepository = new ilNoticeRepository($this->object);

		$this->mode = self::MODE_UPDATE;

		if((int)$_GET['notice_id'] > 0)
		{
			$this->notice = $this->noticeRepository->findById((int)$_GET['notice_id']);

			// Notice may only be updated if user is owner of it
			if($this->user->getId() == $this->notice->getUserId() || $this->checkModeratePermission())
			{
				if(isset($_GET['notice_id']) && ($this->notice->getId() == (int)$_GET['notice_id']))
				{
					$this->mode = self::MODE_UPDATE;
				}
				else
				{
					$this->ctrl->redirect($this, 'showBoard');
				}
			}
			else
			{
				$this->ctrl->redirect($this, 'showBoard');
			}
		}
		else
		{
			$this->notice = new ilNotice();
		}

		$this->saveForm();
	}

	/**
	 *
	 */
	protected function moveNoticeAction()
	{
		global $ilTabs;

		$ilTabs->setTabActive($_SESSION['activeTab']);
		/* Configure form (GET is used for single move command..) */
		$moveIds = array($_GET['notice_id']);

		if(isset($_GET['notice_id']))
		{
			$nt_user_id = ilNotice::lookupUserId($_GET['notice_id']);

			if(!$this->checkModeratePermission() && $this->user->getId() != $nt_user_id)
			{
				$this->ctrl->redirect($this, 'showBoard');
			}
		}
		if(isset($_POST['notice_id']))
		{
			$moveIds = $_POST['notice_id'];
		}

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->txt('move_notice'));

		$repo       = new ilNoticeRepository($this->object);
		$categories = array('' => $this->txt('select_category'))
			+ ilNoticeCategory::getPairs($this->object->getId());


		$select = new ilSelectInputGUI($this->lng->txt('move_to'), 'category_id');
		$select->setOptions($categories);
		$select->setRequired(true);
		$form->addItem($select);

		foreach($moveIds as $noticeId)
		{
			$notice = $repo->findById($noticeId);

			/* Backup/Remove notice's active category */
			$excludedCategory = $categories[$notice->getCategoryId()];
			unset($categories[$notice->getCategoryId()]);

			$hidden = new ilHiddenInputGUI('notice_id[]');
			$hidden->setValue($noticeId);

			$form->addItem($hidden);
			$categories[$notice->getCategoryId()] = $excludedCategory;
		}

		$form->addCommandButton('moveNotice', $this->txt('move'));
		$form->addCommandButton('showBoard', $this->txt('cancel'));
		$form->setFormAction($this->ctrl->getFormAction($this, 'moveNotice'));

		if(isset($_POST['cmd']))
		{
			/* Process notice move */
			if($form->checkInput())
			{
				foreach($moveIds as $noticeId)
				{
					$notice = $repo->findById($noticeId);

					$notice->setCategoryId($form->getInput('category_id'));
					$repo->update($notice);
				}

				ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
				$this->ctrl->redirect($this, 'showBoard');
			}
		}

		/* Display form for move process */
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 *  Command for checking the prices_on_obj setting for a given category
	 *  The selectCategoryAsyncAction() is an asynchronous executed action
	 *  which prints 1 for categories which need prices on objects it
	 *  contains or 0 if it doesn't.
	 */
	protected function selectCategoryAsyncAction()
	{
		$cat_id = $_GET['selected_id'];
		if(!is_numeric($cat_id))
			echo "0";
		else
		{
			$category = new ilNoticeCategory((int)$cat_id);
			echo $category->price_enabled;
		}
		exit;
	}

	/**
	 * Saves form data and shows form
	 * @access protected
	 * @see initForm(), showForm()
	 */
	protected function saveForm()
	{
		$this->currentCategoryId = $this->getCurrentCategoryId();

		$this->initForm();

		if($_POST['action'] == 'send')
		{
			/* Set default status. */
			$this->status = self::STATUS_ERROR;


			if($this->formGui->checkInput())
			{
				$validPriceTypes = array(
					ilNotice::PRICE_TYPE_FIXED_PRICE,
					ilNotice::PRICE_TYPE_ONO
				);

				if($this->formGui->getInput('nt_category_id') == 0)
				{
					return false;
				}

				$category = new ilNoticeCategory($this->formGui->getInput('nt_category_id'));

				if(strlen($this->formGui->getInput('nt_price')))
				{
					$form_price = str_replace(',', '.', $this->formGui->getInput('nt_price'));
				}

				if($category->getPriceEnabled()
					&& in_array($this->formGui->getInput('nt_price_type'), $validPriceTypes)
					&& (float)$form_price <= 0
				)
				{
					/* Invalid [mandatory] price. */
					$this->formGui->getItemByPostVar('nt_price')->setAlert($this->txt('price_type_info'));
					ilUtil::sendFailure($this->lng->txt("form_input_not_valid"));
				}
				else
				{
					/* Data validated, notice can be inserted. */
					$this->noticeRepository = new ilNoticeRepository($this->object);

					/* Configure notice */
					$this->notice->setCategoryId($this->formGui->getInput('nt_category_id'));
					$this->notice->setTitle($this->formGui->getInput('nt_title'));
					$this->notice->setDescription($this->formGui->getInput('nt_description'));
					$this->notice->setPriceType($this->formGui->getInput('nt_price_type'));
					$this->notice->setPrice((float)$form_price);
					$this->notice->setLocationStreet($this->formGui->getInput('nt_location_street'));
					$this->notice->setLocationZip($this->formGui->getInput('nt_location_zip'));
					$this->notice->setLocationCity($this->formGui->getInput('nt_location_city'));
					$this->notice->setUserPhone($this->formGui->getInput('nt_user_phone'));
					$this->notice->setUserEmail($this->formGui->getInput('nt_user_email'));
					$this->notice->setHidden($this->formGui->getInput('nt_hidden'));

					$form_date = $this->formGui->getInput('nt_until_date');

					$this->notice->setUntilDate(strtotime($form_date['date'] . ' 23:59:59'));
					$this->notice->setModDate(time());

					if($this->mode == self::MODE_CREATE)
					{
						$this->notice->setUserId($this->user->getId());
						$this->notice->setCreateDate(time());
						$this->noticeRepository->add($this->notice);
					}
					else
					{
						/* Update Mode */
						$this->noticeRepository->update($this->notice);
					}

					$this->mode   = self::MODE_UPDATE;
					$this->status = self::STATUS_SUCCESSFUL;

					// main image
					$objImage = new ilObjNoticeImage();
					if($this->formGui->getItemByPostVar('nt_image')->getDeletionFlag())
					{
						$objImage->deleteSelectedImage($this->notice->getId());
					}

					if($this->formGui->getInput('nt_image'))
					{
						$objFile_1 = new ilFileDataNoticeboard($this->object_id, $this->notice->getId());
						$objFile_1->setCategoryId($this->notice->getCategoryId());

						$file = $this->formGui->getInput('nt_image');
						foreach($file as $key => $value)
						{
							$new_files[$key][0] = $value;
						}
						$objFile_1->storeUploadedFiles($new_files, ilObjNoticeImage::IMAGE, 1);
					}

					// multiple files
					$img_files = $_FILES['additional_images'];

					if(is_array($img_files) && count($img_files) > 0)
					{
						$objFile_2 = new ilFileDataNoticeboard($this->object_id, $this->notice->getId());
						$objFile_2->setCategoryId($this->notice->getCategoryId());

						$objFile_2->storeUploadedFiles($img_files, ilObjNoticeImage::IMAGE);
					}
					$doc_files = $_FILES['additional_files'];
					if(is_array($doc_files) && !empty($doc_files))
					{
						$objFile = new ilFileDataNoticeboard($this->object_id, $this->notice->getId());
						$objFile->setCategoryId($this->notice->getCategoryId());

						$objFile->storeUploadedFiles($doc_files, ilObjNoticeImage::DOCUMENT);
					}

					$file2delete = $this->formGui->getInput('del_file');

					if(is_array($file2delete) && count($file2delete) > 0)
					{
						$dbImagesObj = new ilObjNoticeImage();
						$dbImagesObj->deleteFiles($file2delete);
					}

					ilUtil::sendSuccess($this->txt('notice_saved_successfully'), TRUE);

					$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
					$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
					$this->ctrl->setParameter($this, 'tab', $_GET['tab']);
					ilUtil::redirect($this->ctrl->getLinkTarget($this, 'update', '', FALSE, FALSE));
				}
			}
		}
		$this->showForm();
	}

	/**
	 * Initializes and defines form gui
	 * @access protected
	 */
	protected function initForm()
	{
		/* Dependencies:
		 * @var ilHTTPS
		 */
		global $https;
		$this->tabs->setTabActive($_SESSION['activeTab']);

		$this->pluginObj->includeClass('class.ilHtmlNoticePurifier.php');

		$currentCategory = new ilNoticeCategory($this->currentCategoryId);
		/* jQuery service available for ILIAS > v4.2 */
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.2.0') >= 0)
		{
			include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
			iljQueryUtil::initjQuery();
		}
		else
		{
			$scheme = "http";
			if($https->isDetected())
				$scheme = "https";

			$this->tpl->addJavaScript("$scheme://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js");
		}

		$this->formGui = new ilPropertyFormGUI();
		$this->formGui->setTableWidth('100%');
		$this->formGui->setId('xnob_' . $this->object->getId());

		$cat_txt = new ilNonEditableValueGUI($this->txt('category'), 'nt_category_title');
		$cat_txt->setValue($currentCategory->getCategoryTitle());
		$this->formGui->addItem($cat_txt);

		/* Notice title */
		$title = new ilTextInputGUI($this->txt('title'), 'nt_title');
		$title->setRequired(TRUE);
		$title->setSize(80);
		$this->formGui->addItem($title);

		/* Notice description text (uses TinyMCE) */
		$desc = new ilTextAreaInputGUI($this->txt('description'), 'nt_description');
		$desc->setRequired(TRUE);
		$desc->setCols(80);
		$desc->setRows(20);

		/* TinyMCE configuration */
		$desc->setUseRte(true);
		$desc->removePlugin('advlink');
		$desc->removePlugin('ibrowser');
		$desc->removePlugin('image');
		$desc->setRTERootBlockElement('');
		$desc->usePurifier(true);
		$desc->disableButtons(array(
			'charmap',
			'justifyleft',
			'justifycenter',
			'justifyright',
			'justifyfull',
			'anchor',
			'fullscreen',
			'formatselect',
			'ibrowser',
			'image'
		));
		$desc->setRTESupport($this->user->getId(), 'xnob~', 'xnob_notice', 'tpl.tinymce.html');

		$desc->setPurifier(new ilHtmlNoticePurifier());

		$this->formGui->addItem($desc);

		$fileObj = new ilObjNoticeImage($this->notice->getId());

		/* Notice image */
		$image = new ilImageFileInputGUI($this->txt('image'), 'nt_image');
		$image->setInfo($this->txt('main_image_info'));
		$this->formGui->addItem($image);

		// Multiple Image Upload
		$oFileUploadGUI = new ilFileWizardInputGUI($this->txt('additional_images'), 'additional_images');
		$oFileUploadGUI->setFilenames(array(0 => ''));
		$oFileUploadGUI->setSuffixes(array("jpg", "jpeg", "png", "gif"));

		$this->formGui->addItem($oFileUploadGUI);

		// Multiple Image Upload
		$oFileUploadGUI_2 = new ilFileWizardInputGUI($this->txt('additional_files'), 'additional_files');
		$oFileUploadGUI_2->setFilenames(array(0 => ''));

		$file_type_settings = ilNoticeboardConfig::getSetting('doc_file_types');
		$oFileUploadGUI_2->setSuffixes(explode(',', $file_type_settings));

		$this->formGui->addItem($oFileUploadGUI_2);

		// edit attachments
		if(count($fileObj->getAllFiles()) && $this->notice->getId() >= 0)
		{
			$oExistingAttachmentsGUI = new ilCheckboxGroupInputGUI($this->txt('delete_file'), 'del_file');

			foreach($fileObj->getAllFiles() as $file)
			{
				if($file['is_selected'] == 0)
				{
					$oAttachmentGUI = new ilCheckboxInputGUI($file['filename'], 'del_file');
					$oAttachmentGUI->setValue($file['image_id']);
					if($file['file_type'] == 'img')
					{
						$fields = new ilCustomInputGUI('', '');
						$tpl    = new ilTemplate('tpl.image_form.html', true, true, $this->pluginObj->getDirectory());
						$tpl->setVariable('PATH', ilUtil::getWebspaceDir() . '/xnob/img_thumbnail/' . $file['filename']);
						$fields->setHtml($tpl->get());

						$oAttachmentGUI->addSubItem($fields);
					}
					$oExistingAttachmentsGUI->addOption($oAttachmentGUI);
				}
			}
			$this->formGui->addItem($oExistingAttachmentsGUI);

		}
		if($this->currentCategoryId == 0 || $currentCategory->getPriceEnabled())
		{
			/* Create price objects ONLY if 'All postings' is selected
			   OR if the current category needs prices on objects. */

			$ptype   = new ilSelectInputGUI($this->txt('price_type'), 'nt_price_type');
			$options = array();
			foreach(ilNotice::getPriceTypes() as $val => $lang)
				$options[$val] = $this->txt($lang);

			$ptype->setOptions($options);
			$ptype->setInfo($this->txt('price_type_info'));
			$this->formGui->addItem($ptype);

			$price = new ilNumberInputGUI($this->txt('price'), 'nt_price');
			$price->setSize(5);
			$price->setInfo(sprintf($this->txt('price_in_currency'), '<b>' . $this->object->getCurrency() . '</b>'));

			$this->formGui->addItem($price);
		}

		/* Location header */
		$location = new ilFormSectionHeaderGUI();
		$location->setTitle($this->txt('location'));
		$this->formGui->addItem($location);

		/* Address fields */
		$street = new ilTextInputGUI($this->txt('street'), 'nt_location_street');
		$street->setSize(80);
		$this->formGui->addItem($street);

		$zip = new ilTextInputGUI($this->txt('zip'), 'nt_location_zip');
		$zip->setSize(20);
		$this->formGui->addItem($zip);

		$city = new ilTextInputGUI($this->txt('city'), 'nt_location_city');
		$city->setSize(80);
		$this->formGui->addItem($city);

		/* User header */
		$user = new ilFormSectionHeaderGUI();
		$user->setTitle($this->txt('user'));
		$this->formGui->addItem($user);

		/* User contact informations fields */
		$phone = new ilTextInputGUI($this->txt('phone'), 'nt_user_phone');
		$phone->setSize(80);
		$phone->setInfo($this->txt('show_user_phone'));
		$this->formGui->addItem($phone);

		$email = new ilEMailInputGUI($this->txt('email'), 'nt_user_email');
		$email->setInfo($this->txt('show_user_email'));
		$this->formGui->addItem($email);

		/* Settings header */
		$settings = new ilFormSectionHeaderGUI();
		$settings->setTitle($this->txt('settings'));
		$this->formGui->addItem($settings);

		$status  = new ilSelectInputGUI($this->txt('status'), 'nt_hidden');
		$options = array(
			0 => $this->txt('online'),
			1 => $this->txt('offline'),
		);
		$status->setOptions($options);
		$status->setInfo(sprintf($this->txt('status_info'), $this->object->getValidity()));
		$this->formGui->addItem($status);

		$frmExpireDate = new ilDateTimeInputGUI($this->txt('expire_date', 'nt_until_date'));
		$frmExpireDate->setPostVar('nt_until_date');
		$this->formGui->addItem($frmExpireDate);

		$this->formGui->addCommandButton(($this->mode == self::MODE_UPDATE ? 'update' : 'create'), $this->lng->txt('save'));
		$this->formGui->addCommandButton('showMyNotices', $this->lng->txt('cancel'));
	}

	/**
	 * Shows form for creating/editing a notice
	 * @access protected
	 * @see setFormValues()
	 */
	protected function showForm()
	{
		global $ilToolbar;

		$cmd = $_GET['next_cmd'];

		$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
		$this->ctrl->setParameter($this, 'tab', $_GET['tab']);

		if($this->mode == self::MODE_CREATE)
		{
			$this->formGui->setFormAction($this->ctrl->getFormAction($this, 'create'));
			$this->formGui->setTitle($this->txt('new_notice'));
		}
		else
		{
			$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
			$this->formGui->setFormAction($this->ctrl->getFormAction($this, 'update'));
			$this->formGui->setTitle($this->txt('edit_notice'));

			$objFileData       = new ilFileDataNoticeboard();
			$selected_filename = ilObjNoticeImage::lookupSelectedFilename($this->notice->getId());

			$image_source = $objFileData->getThumbnailPath() . '/' . $selected_filename;
			if($selected_filename)
			{
				$this->formGui->getItemByPostVar('nt_image')->setImage($image_source);
			}
		}
		if($this->status == self::STATUS_ERROR)
		{
			$this->formGui->setValuesByPost();
		}
		else
		{
			$data = $this->notice->getData();
			$data = array_merge($data, array(
				'nt_until_date' => array(
					'date' => date('Y-m-d', $data['nt_until_date']),
					'time' => date('H:i:s', $data['nt_until_date'])
				)
			));
			$this->formGui->setValuesByArray($data);
		}

		$action = new ilHiddenInputGUI('action');
		$action->setValue('send');
		$this->formGui->addItem($action);

		$cat_id = new ilHiddenInputGUI('nt_category_id');
		$cat_id->setValue($this->currentCategoryId);
		$this->formGui->addItem($cat_id);

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, $_SESSION['activeTab']));

		$asyncURL = $this->ctrl->getLinkTarget($this, 'selectCategoryAsync', "", false, false);

		$tpl = new ilTemplate('tpl.notice_form.html', TRUE, TRUE, $this->pluginObj->getDirectory());
		$tpl->setVariable("FORM", $this->formGui->getHTML());
		$tpl->setVariable("NOTICE_FORM_CATEGORY_SELECT_ACTION_URL", $asyncURL);

		$this->tpl->setContent($tpl->get());
	}

	/**
	 * Contact owner of a notice
	 * @access protected
	 * @see saveForm()
	 */
	protected function contactAction()
	{
		global $ilTabs;

		$ilTabs->setTabActive('content');

		$this->currentCategoryId = $this->getCurrentCategoryId();

		$success      = FALSE;
		$this->notice = new ilNotice();

		if((int)$_GET['notice_id'] > 0)
		{
			$this->noticeRepository = new ilNoticeRepository($this->object);
			$this->notice           = $this->noticeRepository->findCurrentById((int)$_GET['notice_id']);

			if($this->notice == false)
			{
				return $this->showBoardAction();
			}

			if($this->notice->getId() == (int)$_GET['notice_id'])
			{
				if($this->notice->getUserEmail() != '')
				{
					$recipient = $this->notice->getUserEmail();
				}
				else
				{
					$rcpId     = $this->notice->getUserId();
					$recipient = ilObjUser::_lookupLogin($rcpId);
				}

				if($recipient != '')
				{
					$this->initContactForm();

					if($_POST['action'] == 'send')
					{
						$this->status = self::STATUS_ERROR;
						if($this->formGui->checkInput())
						{
							$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
							$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
							$this->ctrl->setParameter($this, 'tab', 'content');

							include_once 'Services/Mail/classes/class.ilMail.php';
							$mail = new ilMail(ANONYMOUS_USER_ID);

							$message = str_replace('###br###', "\n", sprintf($this->txt('contact_message'), $this->user->getFirstname() . ' ' . $this->user->getLastname(), $this->formGui->getInput('email'), $this->formGui->getInput('message')));
							$mail->sendMail($recipient, '', '', strip_tags($this->formGui->getInput('subject')), $message, array(), array('normal'));

							$this->status = self::STATUS_SUCCESSFUL;

							ilUtil::sendSuccess($this->txt('message_sent_successfully'), TRUE);
							ilUtil::redirect($this->ctrl->getLinkTarget($this, 'show', '', FALSE, FALSE));
						}
					}

					$this->showContactForm();
					$success = TRUE;
				}
			}
		}
		if(!$success)
		{
			ilUtil::sendFailure($this->txt('error_sending_message'), TRUE);

			$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
			$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
			$this->ctrl->setParameter($this, 'tab', 'content');
			ilUtil::redirect($this->ctrl->getLinkTarget($this, 'show', '', FALSE, FALSE));
		}
	}

	/**
	 * Initializes and defines contact form gui
	 * @access protected
	 */
	protected function initContactForm()
	{
		global $https;

		if(version_compare(ILIAS_VERSION_NUMERIC, '4.2.0') >= 0)
		{
			include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
			iljQueryUtil::initjQuery();
		}
		else
		{
			if($https->isDetected())
			{
				$this->tpl->addJavaScript('https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js');
			}
			else
			{
				$this->tpl->addJavaScript('http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js');
			}
		}

		$this->formGui = new ilPropertyFormGUI();
		$this->formGui->setTableWidth('100%');
		$this->formGui->setId('xnob_' . $this->object->getId());

		// user
		$field = new ilTextInputGUI($this->txt('name'), 'name');
		$field->setDisabled(TRUE);
		$field->setSize(80);
		$this->formGui->addItem($field);

		// email
		$field = new ilEMailInputGUI($this->txt('email'), 'email');
		$field->setRequired(TRUE);
		$this->formGui->addItem($field);

		// subject
		$field = new ilTextInputGUI($this->txt('subject'), 'subject');
		$field->setRequired(TRUE);
		$field->setSize(80);
		$this->formGui->addItem($field);

		// message
		$field = new ilTextAreaInputGUI($this->txt('message'), 'message');
		$field->setRequired(TRUE);
		$field->setCols(80);
		$field->setRows(20);

		// purifier
		$this->pluginObj->includeClass('class.ilHtmlNoticePurifier.php');
		$field->setPurifier(new ilHtmlNoticePurifier());

		$this->formGui->addItem($field);

		$this->formGui->addCommandButton('contact', $this->txt('send_email'));
		$this->formGui->addCommandButton('show', $this->lng->txt('cancel'));
	}

	/**
	 * Shows form for contacting the owner of a notice
	 * @access protected
	 * @see setFormValues()
	 */
	protected function showContactForm()
	{
		global $ilToolbar;

		$this->ctrl->setParameter($this, 'category_id', $this->currentCategoryId);
		$this->ctrl->setParameter($this, 'notice_id', $this->notice->getId());
		$this->ctrl->setParameter($this, 'tab', 'content');

		$ilToolbar->addButton($this->lng->txt('back'), $this->ctrl->getLinkTarget($this, 'show', '', false, false));

		$this->formGui->setFormAction($this->ctrl->getFormAction($this, 'contact'));
		$this->formGui->setTitle($this->txt('contact_user'));
		if($this->status == self::STATUS_ERROR)
		{
			$this->formGui->setValuesByPost();
		}
		else
		{
			$data = array(
				'name'    => $this->user->getFirstname() . ' ' . $this->user->getLastname(),
				'email'   => $this->user->getEmail(),
				'subject' => $this->notice->getTitle()
			);
			$this->formGui->setValuesByArray($data);
		}

		$action = new ilHiddenInputGUI('action');
		$action->setValue('send');
		$this->formGui->addItem($action);

		$this->tpl->setContent($this->formGui->getHTML());
	}


	public function addCategory()
	{
		$this->tabs->activateTab('categories');
		$this->initCategoriesForm('create');
		$this->tpl->setContent($this->formGui->getHtml());
	}

	public function showPermissions()
	{
		/**
		 * @var $tree ilTree
		 * @var $ilCtrl ilCtrl
		 * @var $rbacreview ilRbacReview
		 */
		global $tree, $ilCtrl, $rbacreview;

		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}
		
		if(!strlen($this->getCurrentCategoryId()))
		{
			$this->ctrl->redirect($this, 'showCategories');
		}

		$this->tabs->activateTab('categories');

		$cat_permissions = ilObjPermission::getPermissionsByCatId($this->getCurrentCategoryId());

		$global_roles = $rbacreview->getGlobalRoles($this->ref_id);
		$local_roles  = $rbacreview->getLocalRoles($this->ref_id);

		$this->plugin->includeClass('class.ilPermissionsTableGUI.php');
		$tbl = new ilPermissionsTableGUI($this, 'showPermissions', $this->getCurrentCategoryId());
		$tbl->setTitle(sprintf($this->pluginObj->txt('permissions_for_category'),

		ilNoticeCategory::lookupTitle($this->getCurrentCategoryId())));
		$merge_roles = array_merge($global_roles, $local_roles);

		if($tree->checkForParentType($this->ref_id, 'crs') or
			$tree->checkForParentType($this->ref_id, 'grp')
		)
		{
			$parent_ref_id = $tree->getParentId($this->ref_id);
			$parent_roles  = $rbacreview->getLocalRoles($parent_ref_id);
			$merge_roles   = array_merge($merge_roles, $parent_roles);
		}

		$ilCtrl->setParameter($this, 'category_id', $this->getCurrentCategoryId());
		
		$i = 0;

		foreach($merge_roles as $role)
		{

			$tbl_data[$i]['cat_id']     = $this->getCurrentCategoryId();
			$role_title                 = ilObject::_lookupTitle($role);
			$tbl_data[$i]['role_title'] = $role_title;
//			$tbl_data[$i]['xnob_read'] = ilUtil::formCheckbox((int)$cat_permissions[$role]['xnob_read'], 'roles['.$role.'][xnob_read]', 1);

			$disabled = false;
			if($role_title == 'Administrator')
			{
				$disabled = true;
			}

			$tbl_data[$i]['xnob_write'] = ilUtil::formCheckbox((int)$cat_permissions[$role]['xnob_write'], 'roles[' . $role . '][xnob_write]', 1, $disabled);

			$i++;
		}
		$tbl->setData($tbl_data);
		$this->tpl->setContent($tbl->getHTML());
	}

	public function saveCategoryPermissions()
	{
		if(!$this->checkModeratePermission())
		{
			$this->ctrl->redirect($this, 'showBoard');
		}

		global $rbacreview;
		$this->tabs->activateTab('categories');
		$this->plugin->includeClass('class.ilObjPermission.php');

		$objPermission = new ilObjPermission();
		$objPermission->setCategoryId($_POST['cat_id']);
		$objPermission->setObjId($this->obj_id);

		$objPermission->resetPermissions();

		$global_roles = $rbacreview->getGlobalRoles($this->ref_id);
		foreach($global_roles as $role)
		{
			$role_title = ilObject::_lookupTitle($role);
			if($role_title == 'Administrator')
			{
				$objPermission->setRoleId($role);
				$objPermission->setXnobRead(1);
				$objPermission->setXnobWrite(1);

				$objPermission->insert();
			}
		}

		if(is_array($_POST['roles']))
		{
			foreach($_POST['roles'] as $role_id => $permissions)
			{
				$objPermission->setRoleId($role_id);

				//			disable read-right not supported for now 			
				//			$objPermission->setXnobRead($permissions['xnob_read'] ? 1 : 0);
				$objPermission->setXnobRead(1);

				$objPermission->setXnobWrite((int)$permissions['xnob_write'] ? 1 : 0);

				$objPermission->insert();
			}
		}
		ilUtil::sendSuccess($this->lng->txt('saved_successfully'));

		$this->showPermissions();
	}

	public function showFilter($in_my_notices = false)
	{
		global $lng;

		if(!isset($_SESSION['xnob_filter']))
		{
			$_SESSION['xnob_filter']['cat_id'] = 'all';
			$_SESSION['xnob_filter']['status'] = '0';
		}

		if($this->checkModeratePermission() || $in_my_notices == true)
		{
			$this->filter_enabled = true;
			$form                 = new ilPropertyFormGUI();

			$form->setTitle($lng->txt('filter'));
			$form->setFormAction($this->ctrl->getFormAction($this), 'setFilter');

			$form->addCommandButton('setFilter', $this->pluginObj->txt('update_filter'));
			$form->addCommandButton('resetFilter', $lng->txt('reset_filter'));

			$filter_cat  = new ilSelectInputGUI($this->pluginObj->txt('category'), 'filter_cat');
			$cat_options = array('all' => $this->pluginObj->txt('all'));
			$categories  = ilNoticeCategory::getPairs($this->obj_id);
			foreach($categories as $key => $value)
			{
				$cat_options[$key] = $value;
			}

			$filter_cat->setOptions($cat_options);
			$filter_cat->setValue($_SESSION['xnob_filter']['cat_id']);

			$form->addItem($filter_cat);

			$filter_status  = new ilSelectInputGUI($lng->txt('status'), 'filter_status');
			$status_options = array(
				'all' => $this->pluginObj->txt('all'),
				'0'   => $this->pluginObj->txt('active_posts'),
				'1'   => $this->pluginObj->txt('inactive_posts')
			);

			$filter_status->setOptions($status_options);
			$filter_status->setValue($_SESSION['xnob_filter']['status'] ? $_SESSION['xnob_filter']['status'] : '0');
			$form->addItem($filter_status);

			$this->filter = $form->getHTML();
		}
		else
		{
			$this->filter = "";
		}
	}

	public function setFilterAction()
	{
		if(isset($_POST['filter_cat']))
		{
			$_SESSION['xnob_filter']['cat_id'] = $_POST['filter_cat'];

		}
		if(isset($_POST['filter_status']))
		{
			$_SESSION['xnob_filter']['status'] = $_POST['filter_status'];
		}

		$this->ctrl->redirect($this, $_SESSION['activeTab']);
	}

	public function resetFilterAction()
	{
		$_SESSION['xnob_filter']['cat_id'] = 'all';
		$_SESSION['xnob_filter']['status'] = '0';
		$this->ctrl->redirect($this, $_SESSION['activeTab']);
	}

	public function isFilterEnabled()
	{
		return (bool)$this->filter_enabled;
	}

	public function setFilterBySubtabAction()
	{
		$cmd = $_GET['next_cmd'];

		$this->filter_enabled = true;

		if(isset($_GET['category_id']))
		{
			$_SESSION['xnob_filter']['cat_id'] = $_GET['category_id'];
		}
		else
		{
			$_SESSION['xnob_filter']['cat_id'] = 0;
		}

		if($cmd == 'showMyNotices')
		{
			$this->ctrl->redirect($this, 'showMyNotices');
		}
		else
		{
			$this->ctrl->redirect($this, 'showBoard');
		}
	}

	public function deliverDocumentAction()
	{
		$this->checkPermission('read');

		if(!isset($_GET['file_id']))
		{
			$this->showBoardAction();
			return;
		}

		$this->plugin->includeClass('class.ilObjNoticeImage.php');

		$filename = ilObjNoticeImage::lookupFilename((int)$_GET['file_id']);

		require_once "./Services/Utilities/classes/class.ilUtil.php";
		ilUtil::deliverFile(ilUtil::getWebspaceDir() . '/xnob/' . $filename, $filename);
	}
}
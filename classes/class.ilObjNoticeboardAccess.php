<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once('./Services/Repository/classes/class.ilObjectPluginAccess.php');

/**
 * Access/Condition checking for notice board object
 *
 * Please do not create instances of large application classes (like ilObjNoticeboard)
 * Write small methods within this class to determin the status.
 *
 * @author Jens Conze <jc@databay.de>
 * @version $Id$
 */
class ilObjNoticeboardAccess extends ilObjectPluginAccess
{
	/**
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int    $a_ref_id
	 * @param int    $a_obj_id
	 * @param string $a_user_id
	 * @return bool
	 */
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = '')
	{
		global $DIC;
		$ilUser = $DIC->user();
		$ilAccess = $DIC->access();

		if ($a_user_id == '')
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_permission)
		{
			case 'read':
				if (!$ilAccess->checkAccessOfUser($a_user_id, 'write', '', $a_ref_id))
				{
					#return false;
				}
				break;
		}
		return true;
	}
}
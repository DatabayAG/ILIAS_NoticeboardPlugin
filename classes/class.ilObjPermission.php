<?php
/**
 * @author Nadia Ahmad <nahmad@databay.de>
 * @version $Id$
 */

class ilObjPermission
{
	private $category_id = 0;
	private $role_id = 0;
	private $obj_id = 0;
	
	// we do not support a changeable read-right for now
	private $xnob_read = 1;
	
	private $xnob_write = 0;
	private $is_global_role = 0;

	public function setCategoryId($category_id)
	{
		$this->category_id = $category_id;
	}

	public function getCategoryId()
	{
		return $this->category_id;
	}

	public function setIsGlobalRole($is_global_role)
	{
		$this->is_global_role = $is_global_role;
	}

	public function getIsGlobalRole()
	{
		return $this->is_global_role;
	}

	public function setObjId($obj_id)
	{
		$this->obj_id = $obj_id;
	}

	public function getObjId()
	{
		return $this->obj_id;
	}

	public function setRoleId($role_id)
	{
		$this->role_id = $role_id;
	}

	public function getRoleId()
	{
		return $this->role_id;
	}

	public function setXnobRead($xnob_read)
	{
		$this->xnob_read = $xnob_read;
	}

	public function getXnobRead()
	{
		return $this->xnob_read;
	}

	public function setXnobWrite($xnob_write)
	{
		$this->xnob_write = $xnob_write;
	}

	public function getXnobWrite()
	{
		return $this->xnob_write;
	}
	
	public function __construct()
	{
	}
	
	/***
	 * @param integer $cat_id
	 */
	public function getPermissionObj($cat_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryF('SELECT * FROM xnob_cat_permissions WHERE category_id = %s',
		array('integer'), array((int)$cat_id));
		
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$this->setCategoryId($cat_id);
			$this->setRoleId($row['role_id']);
			$this->setObjId($row['obj_id']);
			$this->getXnobRead((int)$row['xnob_read']);
			$this->getXnobWrite((int)$row['xnob_write']);
			$this->getIsGlobalRole((int)$row['is_global_role']);
		}
	}
	
	public function insert()
	{
		global $ilDB;
		
		$ilDB->insert('xnob_cat_permissions',
		array(
			'category_id' => array('integer', $this->getCategoryId()),
			'role_id' => array('integer', $this->getRoleId()),
			'obj_id' => array('integer', $this->getObjId()),
			'xnob_read' => array('integer', $this->getXnobRead()),
			'xnob_write' => array('integer', $this->getXnobWrite()),
			'is_global_role' => array('integer', $this->getIsGlobalRole()) 
		));
	}
	
	public function resetPermissions()
	{
		global $ilDB;
		
		$ilDB->manipulateF('DELETE FROM xnob_cat_permissions WHERE category_id = %s',
		array('integer'), array($this->getCategoryId()));
	}

	/**
	 * @param integer $cat_id
	 */
	public static function deletePermissionsByCategoryId($cat_id)
	{
		global $ilDB;
		
		$ilDB->manipulateF('DELETE FROM xnob_cat_permissions WHERE cat_id = %s',
		array('integer'), array((int)$cat_id));
	}

	/**
	 * @param integer $obj_id
	 */
	public static function deletePermissionsByObjId($obj_id)
	{
		global $ilDB;

		$ilDB->manipulateF('DELETE FROM xnob_cat_permissions WHERE $obj_id = %s',
			array('integer'), array((int)$obj_id));	
	}

	/**
	 * @param integer $user_id
	 * @param integer $category_id
	 * @return bool
	 */
	public static function hasReadAccess($user_id, $category_id)
	{
		global $ilDB;

		// is owner? 
		if(ilObject::_lookupOwner(self::lookupObjId($category_id)) == $user_id)
		{
			return true;
		}
		
		// get rbac roles of user
		$assigned_roles = ilRbacReview::assignedRoles($user_id);

		// get roles with category read permissions
		$res = $ilDB->queryF('SELECT role_id FROM xnob_cat_permissions 
		WHERE category_id = %s
		AND xnob_read = %s',
			array('integer', 'integer'), array($category_id, 1));

		$permissions_roles = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$permissions_roles[ $row['role_id']] = $row['role_id'];
		}
		
		foreach($assigned_roles as $role)
		{
			if(array_key_exists($role, $permissions_roles))
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * @param integer $user_id
	 * @param integer $category_id
	 * @return bool
	 */
	public static function hasWriteAccess($user_id, $category_id)
	{
		global $ilDB;

		// is owner? 
		if(ilObject::_lookupOwner(self::lookupObjId($category_id)) == $user_id)
		{
			return true;
		}

		// get rbac roles of user
		$assigned_roles = ilRbacReview::assignedRoles($user_id);

		// get roles with category read permissions
		$res = $ilDB->queryF('SELECT role_id FROM xnob_cat_permissions 
		WHERE category_id = %s
		AND xnob_write = %s',
			array('integer', 'integer'), array($category_id, 1));

		while($row = $ilDB->fetchAssoc($res))
		{
			$permissions_roles[ $row['role_id']] = $row['role_id'];
		}

		if(!is_array($permissions_roles))
		{
			return false;
		}
		
		foreach($assigned_roles as $role)
		{
			if(array_key_exists($role, $permissions_roles))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param integer $category_id
	 * @return int obj_id
	 */
	public static function lookupObjId($category_id)
	{
		global $ilDB;
		
		$ilDB->setLimit(1);
		$res = $ilDB->queryf('SELECT obj_id FROM xnob_cat_permissions WHERE category_id = %s',
		array('integer'), array($category_id));
		$row = $ilDB->fetchAssoc($res);
		
		return  $row['obj_id'] ? $row['obj_id'] : 0;
	}

	/**
	 * @param integer $category_id
	 * @return array  
	 */
	public static function getPermissionsByCatId($category_id)
	{
		global $ilDB;

		$res = $ilDB->queryF('SELECT * FROM xnob_cat_permissions WHERE category_id = %s',
			array('integer'), array($category_id));
		
		
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$cat_permissions[$row['role_id']] = $row;
		}

		return $cat_permissions ? $cat_permissions : array();
	}

	/**
	 * @param integer $ref_id
	 */
	public function doAfterCreate($ref_id)
	{
		global $ilDB, $rbacreview, $tree;
		
		// create "default category permissions" after "create" of category
		$global_roles = $rbacreview->getGlobalRoles($ref_id);
		$local_roles = $rbacreview->getLocalRoles($ref_id);

		if(is_array($global_roles) && count($global_roles) > 0)
		{
			foreach($global_roles as $role)
			{
				if(ilObject::_lookupTitle($role) == 'User')
				{
					$ilDB->insert('xnob_cat_permissions',
						array(
							'category_id'    => array('integer', $this->getCategoryId()),
							'role_id'        => array('integer', $role),
							'obj_id'         => array('integer', $this->getObjId()),
							'xnob_read'      => array('integer', 1),
							'xnob_write'     => array('integer', 0),
							'is_global_role' => array('integer', 1)
						));
				}
				else if(ilObject::_lookupTitle($role) == 'Administrator')
				{
					$ilDB->insert('xnob_cat_permissions',
						array(
							'category_id'    => array('integer', $this->getCategoryId()),
							'role_id'        => array('integer', $role),
							'obj_id'         => array('integer', $this->getObjId()),
							'xnob_read'      => array('integer', 1),
							'xnob_write'     => array('integer', 1),
							'is_global_role' => array('integer', 1)
						));
				}
				else
				{
					$ilDB->insert('xnob_cat_permissions',
						array(
							'category_id'    => array('integer', $this->getCategoryId()),
							'role_id'        => array('integer', $role),
							'obj_id'         => array('integer', $this->getObjId()),
							'xnob_read'      => array('integer', 1),
							'xnob_write'     => array('integer', 0),
							'is_global_role' => array('integer', 1)
						));
				}
			}
		}

		if ($tree->checkForParentType($ref_id, 'crs') or
			$tree->checkForParentType($ref_id, 'grp'))
		{
			$parent_ref_id = $tree->getParentId($ref_id);
			$local_parent_roles = $rbacreview->getLocalRoles($parent_ref_id);

			$local_roles = array_merge($local_roles, $local_parent_roles);
		}

		if(is_array($local_roles) && count($local_roles) > 0)
		{
			foreach($local_roles as $role)
			{
				$ilDB->insert('xnob_cat_permissions',
					array(
						'category_id'    => array('integer', $this->getCategoryId()),
						'role_id'        => array('integer', $role),
						'obj_id'         => array('integer', $this->getObjId()),
						'xnob_read'      => array('integer', 1),
						'xnob_write'     => array('integer', 0),
						'is_global_role' => array('integer', 0)
					));
			}
		}
	}
	
	public function doBeforeDelete()
	{
		// delete all permissions of ilias object before ilias object is deleted completely
		// do nothing ...if(isTrashbinEnabled()) ... 
		
	}
}
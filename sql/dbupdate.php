<#1>
<?php
if(!$ilDB->tableExists('xnob_notices'))
{
	$fields = array(
		'nt_id'				=> array('type' => 'integer', 'length'  => 4,'notnull' => TRUE, 'default' => 0),
		'nt_obj_id'			=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
		'nt_usr_id'			=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
		'nt_type'			=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
		'nt_title'			=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'nt_description'	=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 4000),
		'nt_price'			=> array('type' => 'float', 'notnull' => TRUE, 'default' => 0),
		'nt_price_type'		=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
		'nt_location_street'=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'nt_location_zip'	=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'nt_location_city'	=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'nt_user_phone'		=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'nt_user_email'		=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'nt_create_date'	=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
		'nt_mod_date'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
		'nt_deleted'		=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
		'nt_hidden'			=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
	);
	$ilDB->createTable('xnob_notices', $fields);
	$ilDB->addPrimaryKey('xnob_notices', array('nt_id'));
	$ilDB->addIndex('xnob_notices', array('nt_obj_id', 'nt_deleted', 'nt_create_date', 'nt_type'), 'i1');
	$ilDB->createSequence('xnob_notices');
}
?>
<#2>
<?php
if(!$ilDB->tableExists('xnob_properties'))
{
	$fields = array(
		'pt_obj_id'			=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
		'pt_currency'		=> array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255),
		'pt_validity'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
	);
	$ilDB->createTable('xnob_properties', $fields);
	$ilDB->addPrimaryKey('xnob_properties', array('pt_obj_id'));
}
?>
<#3>
<?php
ilUtil::makeDirParents(ilUtil::getWebspaceDir().'/xnob/cache');
?>
<#4>
<?php
if (!$ilDB->tableColumnExists('xnob_notices', 'nt_image'))
{
	$ilDB->addTableColumn('xnob_notices', 'nt_image', array('type' => 'text', 'notnull' => FALSE, 'default' => NULL, 'length' => 255));
}
?>
<#5>
<?php
	if ($ilDB->tableColumnExists('xnob_notices', 'nt_type')) {
		$ilDB->renameTableColumn('xnob_notices', 'nt_type', 'nt_category_id');
	}
?>
<#6>
<?php
?>
<#7>
<?php

	if( $ilDB->tableExists("xnob_categories"))
	{
		$ilDB->dropTable('xnob_categories');
	}

	if (! $ilDB->tableExists("xnob_categories") ) 
	{
		$fields = array(
			'category_id'	=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'price_enabled'	=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
	
		);
		$ilDB->createTable('xnob_categories', $fields);
		$ilDB->addPrimaryKey('xnob_categories', array('category_id'));
	}
?>
<#8>
<?php
	if (! $ilDB->tableColumnExists('xnob_categories', 'category_title'))
	{
		$ilDB->addTableColumn('xnob_categories', 'category_title', array('type' => "text", "notnull" => TRUE, 'length' => 255, 'default' => ''));
	}
	if (! $ilDB->tableColumnExists('xnob_categories', 'category_description'))
	{
		$ilDB->addTableColumn('xnob_categories', 'category_description', array('type' => "text", "notnull" => FALSE, 'length' => 255, 'default' => ''));
	}
	if (! $ilDB->tableColumnExists('xnob_categories', 'obj_id'))
	{
		$ilDB->addTableColumn('xnob_categories', 'obj_id', array('type' => "integer", 'length' => 4, "notnull" => TRUE, 'default' => 0));
	}
?>
<#9>
<?php
//
if($ilDB->tableColumnExists('xnob_notices', 'nt_type') &&
	!$ilDB->tableColumnExists('xnob_notices', 'nt_category_id')
)
{
	$ilDB->renameTableColumn('xnob_notices', 'nt_type', 'nt_category_id');
	$ilDB->modifyTableColumn('xnob_notices', 'nt_category_id', array(
		"type"    => "integer",
		'length'  => 4,
		"notnull" => true,
		"default" => 0
	));
}
else if(!$ilDB->tableColumnExists('xnob_notices', 'nt_category_id'))
{
	$ilDB->addTableColumn('xnob_notices', 'nt_category_id', array('type' => "integer", 'length' => 4, "notnull" => TRUE, 'default' => 0));
}
else
{
	$ilDB->modifyTableColumn('xnob_notices', 'nt_category_id', array(
		"type"    => "integer",
		'length'  => 4,
		"notnull" => true,
		"default" => 0
	));
}

// drop primary . create new primary key (3 fields)

$res = $ilDB->query("SELECT count(*) cnt_notices FROM xnob_notices");
$row = $ilDB->fetchAssoc($res);
if (! empty($row['cnt_notices'])) 
{
	/* ILIAS already has notices */

	/* First update category_id as the 'Default' category is not 0 but 1 now ; */
	$ilDB->manipulate("
		UPDATE xnob_notices
			SET nt_category_id = nt_category_id + 1
	");
}
?>
<#10>
<?php
	if($ilDB->tableColumnExists('xnob_notices', 'nt_type') &&
		!$ilDB->tableColumnExists('xnob_notices', 'nt_category_id')
	)
	{
		$ilDB->renameTableColumn('xnob_notices', 'nt_type', 'nt_category_id');
		$ilDB->modifyTableColumn('xnob_notices', 'nt_category_id', array(
			"type"    => "integer",
			'length'  => 4,
			"notnull" => true,
			"default" => 0
		));
	}
	else if(!$ilDB->tableColumnExists('xnob_notices', 'nt_category_id'))
	{
		$ilDB->addTableColumn('xnob_notices', 'nt_category_id', array('type' => "integer", 'length' => 4, "notnull" => TRUE, 'default' => 0));
	}
	else
	{
		$ilDB->modifyTableColumn('xnob_notices', 'nt_category_id', array(
			"type"    => "integer",
			'length'  => 4,
			"notnull" => true,
			"default" => 0
		));
	}
	
	if(!$ilDB->tableColumnExists('xnob_categories', 'price_enabled'))
	{
		$ilDB->addTableColumn('xnob_notices', 'price_enabled', array('type' => 'integer', 'length' => 1, 'notnull' => TRUE, 'default' => 0));
	}
	
	$res = $ilDB->query("SELECT DISTINCT nt_category_id, nt_obj_id FROM xnob_notices");

	while($row = $ilDB->fetchAssoc($res))
	{
		if($row['nt_category_id'] == "1")
		{
			$cat_title = 'category_default';
			$cat_desc = 'category_default_description';
		}
		else if($row['nt_category_id'] == "2")
		{
			$cat_title = 'category_for_sale';
			$cat_desc = 'category_for_sale_description';
		}
		elseif($row['nt_category_id'] == "3")
		{
			$cat_title = 'category_wanted';
			$cat_desc = 'category_wanted_description';
		}
		
		$price_res = $ilDB->queryF('
		SELECT MAX(nt_price_type) max_price_type
		FROM xnob_notices 
		WHERE nt_obj_id = %s 
		AND nt_category_id = %s',
		array('integer', 'integer'),
		array($row['nt_obj_id'],$row['nt_category_id']));
		
		if($row['max_price_type'] > 0)
		{
			$price_enabled = 1;
		}
		else
		{
			$price_enabled = 0;
		}

		$catres = $ilDB->query('SELECT MAX(category_id) max_cat_id FROM xnob_categories');
		$row_max_id = $ilDB->fetchAssoc($catres);
		$next_id = (int)$row_max_id['max_cat_id'] + 1;
		
		if($next_id == null)
		{
		    $next_id = 1;
		}
		
		$ilDB->insert('xnob_categories',
			array('category_id' => array('integer', $next_id),
				  'category_title' => array('text', $cat_title),
				  'category_description' => array('text', $cat_desc),
				  'price_enabled' => array('integer', $price_enabled),
				  'obj_id' => array('integer', $row['nt_obj_id'])
			));

		$ilDB->update('xnob_notices',
			array('nt_category_id' => array('integer',$next_id)),
			array(
				'nt_obj_id'   => array('integer',$row['nt_obj_id']),
				'nt_category_id' => array('integer',$row['nt_category_id'])
			));
		}
?>
<#11>
<?php
	if(!$ilDB->tableColumnExists('xnob_notices', 'nt_validity'))
	{
		$ilDB->addTableColumn('xnob_notices','nt_validity', array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0));
	}
	
	$res = $ilDB->query('SELECT pt_obj_id, pt_validity FROM xnob_properties');
	while($row = $ilDB->fetchAssoc($res))
	{
		$ilDB->update('xnob_notices',
			array('nt_validity' => array('integer', $row['pt_validity'])),
			array('nt_obj_id'	=> array('integer', $row['pt_obj_id'])));
	}
?>
<#12>
<?php
	if(!$ilDB->tableExists('xnob_cat_permissions'))
	{
		$fields = array(
			'category_id'	=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'role_id'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'obj_id'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'xnob_read'		=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
			'xnob_write'	=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0),
			'is_global_role'=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0)
		);
	
		$ilDB->createTable('xnob_cat_permissions', $fields);
		$ilDB->addPrimaryKey('xnob_cat_permissions', array('category_id', 'role_id'));
	}
?>
<#13>
<?php
	// update permisssions for existing categories
	global $rbacreview, $tree;
	
	$res = $ilDB->query('SELECT * FROM xnob_categories');
	while($row = $ilDB->fetchAssoc($res))
	{
		$categories[] = $row;
	}
if(is_array($categories) && count($categories) > 0)
{
	foreach($categories as $cat)
	{
		$obj_refs = ilObject::_getAllReferences($cat['obj_id']);
		foreach($obj_refs as $ref_id)
		{
			$global_roles = $rbacreview->getGlobalRoles($ref_id);
			$local_roles  = $rbacreview->getLocalRoles($ref_id);

			if(is_array($global_roles) && count($global_roles) > 0)
			{
				foreach($global_roles as $role)
				{
					if(ilObject::_lookupTitle($role) == 'User')
					{
						$ilDB->insert('xnob_cat_permissions',
							array(
								'category_id'    => array('integer', $cat['category_id']),
								'role_id'        => array('integer', $role),
								'obj_id'         => array('integer', $cat['obj_id']),
								'xnob_read'      => array('integer', 1),
								'xnob_write'     => array('integer', 1),
								'is_global_role' => array('integer', 1)
							));
					}
					else if(ilObject::_lookupTitle($role) == 'Administrator')
					{
						$ilDB->insert('xnob_cat_permissions',
							array(
								'category_id'    => array('integer', $cat['category_id']),
								'role_id'        => array('integer', $role),
								'obj_id'         => array('integer', $cat['obj_id']),
								'xnob_read'      => array('integer', 1),
								'xnob_write'     => array('integer', 1),
								'is_global_role' => array('integer', 1)
							));
					}
					else
					{
						$ilDB->insert('xnob_cat_permissions',
							array(
								'category_id'    => array('integer', $cat['category_id']),
								'role_id'        => array('integer', $role),
								'obj_id'         => array('integer', $cat['obj_id']),
								'xnob_read'      => array('integer', 1),
								'xnob_write'     => array('integer', 0),
								'is_global_role' => array('integer', 1)
							));
					}
				}
			}

			if($tree->checkForParentType($ref_id, 'crs') or
				$tree->checkForParentType($ref_id, 'grp')
			)
			{
				$parent_ref_id      = $tree->getParentId($ref_id);
				$local_parent_roles = $rbacreview->getLocalRoles($parent_ref_id);

				$local_roles = array_merge($local_roles, $local_parent_roles);
			}

			if(is_array($local_roles) && count($local_roles) > 0)
			{
				foreach($local_roles as $role)
				{
					$ilDB->insert('xnob_cat_permissions',
						array(
							'category_id'    => array('integer', $cat['category_id']),
							'role_id'        => array('integer', $role),
							'obj_id'         => array('integer', $cat['obj_id']),
							'xnob_read'      => array('integer', 1),
							'xnob_write'     => array('integer', 0),
							'is_global_role' => array('integer', 0)
						));
				}
			}
		}
	}
}
?>
<#14>
<?php
	if($ilDB->tableExists('xnob_categories'))
	{
		$res = $ilDB->query('SELECT MAX(category_id) current_cat_id FROM xnob_categories');
		$row = $ilDB->fetchAssoc($res);
		$next_id = (int)$row['current_cat_id'] + 1;
		$ilDB->createSequence('xnob_categories', $next_id);
	}
?>
<#15>
<?php
	if(! $ilDB->tableExists('xnob_images'))
	{
		$fields = array
		(
			'image_id'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'obj_id'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'category_id'	=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'notice_id'		=> array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0),
			'filename'		=> array('type' => 'text', 'length'  => 255, 'notnull' => FALSE, 'default' => NULL),
			'is_selected' 	=> array('type' => 'integer', 'length'  => 1, 'notnull' => TRUE, 'default' => 0)
		);

		$ilDB->createTable('xnob_images', $fields);
		$ilDB->createSequence('xnob_images');
	}
?>
<#16>
<?php
	//migrate existing images 1
	$res = $ilDB->query('SELECT nt_id, nt_obj_id, nt_category_id, nt_image FROM xnob_notices');

	while($row = $ilDB->fetchAssoc($res))
	{
		if($row['nt_image'] != NULL)
		{
			$next_id = $ilDB->nextId('xnob_images');
			$ilDB->insert('xnob_images',
				array(
					'image_id' 	=> array('integer', $next_id),
					'obj_id'	=> array('integer', $row['nt_obj_id']),
					'category_id'=>array('integer', $row['nt_category_id']),
					'notice_id'	=> array('integer', $row['nt_id']),
					'filename'	=> array('text', $row['nt_image']),
					'is_selected'=> array('integer', 1)));
		}
	}
?>
<#17>
<?php
// 	migrate existing images 2

	$image_path = ilUtil::getWebspaceDir().'/xnob';

	if(is_dir($image_path))
	{
		$dp = opendir($image_path);

		$files = array();

		while($file = readdir($dp))
		{
			if(is_file($file))
			{
				continue;
			}

			list($obj_id, $notice_id, $rest) = explode('_', $file, 3);

			if(!is_dir($image_path.'/'.$file))
			{
				$files[] = array(
					'path' 	   => $image_path.'/'.$file,
					'name'     => $file,
					'obj_id' => $obj_id,
					'notice_id' => $notice_id
				);
			}
		}

		foreach($files as $file)
		{
			$res = $ilDB->queryF(
				'SELECT nt_id, nt_obj_id, nt_category_id FROM xnob_notices
				WHERE nt_obj_id = %s AND nt_id = %s',
				array('integer', 'integer'),
				array($file['obj_id'], $file['notice_id'])
			);

			while($row = $ilDB->fetchAssoc($res))
			{
				$next_id = $ilDB->nextId('xnob_images');
				$ilDB->insert('xnob_images',
					array(
						'image_id' 	=> array('integer', $next_id),
						'obj_id'	=> array('integer', $row['nt_obj_id']),
						'category_id'=>array('integer', $row['nt_category_id']),
						'notice_id'	=> array('integer', $row['nt_id']),
						'filename'	=> array('text', $file['name']),
						'is_selected'=> array('integer', 0)));
			}
		}
		closedir($dp);
	}
?>
<#18>
<?php
	if(! $ilDB->tableExists('xnob_settings'))
	{
		$fields = array(
			'keyword' => array(
				'type' => 'text',
				'length' => 50,
				'notnull' => true,
			),
			'value' => array(
				'type' => 'text',
				'length' => 4000,
				"notnull" => false,
				"default" => null
			));

		$ilDB->createTable("xnob_settings", $fields);
		$ilDB->addPrimaryKey('xnob_settings', array('keyword'));
	}
?>
<#19>
<?php
	$ilDB->insert('xnob_settings',
		array(
			'keyword' => array('text', 'validity'),
			'value'   => array('text', '28')
		));

	$ilDB->insert('xnob_settings',
		array(
			'keyword' => array('text', 'img_height'),
			'value'   => array('text', '450')
		));

	$ilDB->insert('xnob_settings',
		array(
			'keyword' => array('text', 'img_width'),
			'value'   => array('text', '450')
		));
?>
<#20>
<?php
	if($ilDB->tableExists('xnob_images'))
	{
		if (! $ilDB->tableColumnExists('xnob_images', 'file_type'))
		{
			$ilDB->addTableColumn('xnob_images', 'file_type', array('type' => "text", "notnull" => TRUE, 'length' => 3, 'default' => 'img'));
		}
	}
?>
<#21>
<?php
	$source_image_path = ilUtil::getDataDir().'/xnob/';
	$source_preview_path = ilUtil::getDataDir().'/xnob/img_preview/';
	$target_image_path = ilUtil::getWebspaceDir().'/xnob';
	$target_preview_path = ilUtil::getWebspaceDir().'/xnob/img_preview';

	ilUtil::makeDir($target_preview_path);

	if(is_dir($source_image_path))
	{
		$dp = opendir($source_image_path);
		while($file = readdir($dp))
		{
			if(is_file($source_image_path.'/'.$file))
			{
				copy($source_image_path.'/'.$file, $target_image_path.'/'.$file);
			}
		}
	}

	if(is_dir($source_preview_path))
	{
		$dp = opendir($source_preview_path);
		while($file = readdir($dp))
		{
			if(is_file($source_preview_path.'/'.$file))
			{
				copy($source_preview_path.'/'.$file, $target_preview_path.'/'.$file);
			}
		}
	}
?>
<#22>
<?php
	if(!$ilDB->tableColumnExists('xnob_notices', 'nt_until_date'))
	{
		$ilDB->addTableColumn('xnob_notices', 'nt_until_date',
			array('type' => 'integer', 'length'  => 4, 'notnull' => TRUE, 'default' => 0));	
	}
?>
<#23>
<?php
	if($ilDB->tableColumnExists('xnob_notices', 'nt_validity') 
	&& $ilDB->tableColumnExists('xnob_notices', 'nt_until_date'))
	{
		$res = $ilDB->query('SELECT nt_id, nt_create_date, nt_validity FROM xnob_notices');
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$until_date = strtotime('+'. $row['nt_validity'].' days', $row['nt_create_date']);
			
			$ilDB->update('xnob_notices',
			array('nt_until_date' => array('integer', $until_date)),
			array('nt_id' => array('integer', $row['nt_id'])));
		}
	}
?>	
<#24>
<?php
	include_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Noticeboard/classes/class.ilNoticeRepository.php';
	include_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Noticeboard/classes/class.ilNoticeboardConfig.php';

	$res = $ilDB->query('SELECT nt_id, nt_obj_id, nt_category_id, nt_image FROM xnob_notices WHERE nt_image ORDER BY nt_obj_id ASC');

	$target_image_path = ilUtil::getWebspaceDir().'/xnob';
	$target_preview_path = ilUtil::getWebspaceDir().'/xnob/img_preview';

	foreach(array('img_preview_height' => 450, 'img_preview_width' => 450) as $keyword => $value)
	{
		$res = $ilDB->queryF("SELECT keyword, value FROM xnob_settings WHERE keyword = %s", array('text'), array($keyword));
		$row = $ilDB->fetchAssoc($res);

		if(!is_array($row) || !is_numeric($row['value']))
		{
			if(!is_array($row))
			{
				$ilDB->insert('xnob_settings',
					array(
						'keyword' => array('text', $keyword),
						'value'	  => array('text', $value)
					)
				);
			}
			else
			{
				$ilDB->update('xnob_settings',
					array(
						'value'	  => array('text', $value)
					),
					array(
						'keyword' => array('text', $keyword)
					)
				);
			}
		}
	}

	while($row = $ilDB->fetchAssoc($res))
	{
		$notice_id = $row['nt_id'];
		$obj_id = $row['nt_obj_id'];
		$cat_id = $row['nt_category_id'];
		$filename = $row['nt_image'];

		$old_file = $target_image_path.'/'.$obj_id.'/'.$filename;
		$new_file = $target_image_path.'/'.$obj_id.'_'.$notice_id.'_'.$filename;
		$new_filename = $obj_id.'_'.$notice_id.'_'.$filename;

		if(is_file($old_file))
		{
			copy($old_file, $new_file);

			if(!is_file($new_file))
			{
				$GLOBALS['ilLog']->write(sprintf("Noticeboard: Could not create copy from file %s to file %s", $old_file, $new_file));
				continue;
			}

			$imageSize = getImageSize($new_file);

			if(!$imageSize)
			{
				$GLOBALS['ilLog']->write(sprintf("Noticeboard: Could not determine image dimesions of file: %s", $new_file));
				continue;
			}

			if(!ilNoticeRepository::existsPreviewImage($new_file))
			{
				if(
					$imageSize[0] > ilNoticeboardConfig::getSetting('img_preview_width') ||
					$imageSize[1] > ilNoticeboardConfig::getSetting('img_preview_height')
				)
				{
					ilNoticeRepository::createPreviewImage($new_file);
				}
				else
				{
					ilNoticeRepository::createPreviewImage($new_file, $imageSize[0], $imageSize[1]);
				}
			}

		}

		$next_id = $ilDB->nextId('xnob_images');

		$ilDB->insert('xnob_images',
			array(	'image_id' => array('integer', $next_id),
					  'obj_id' 	=> array('integer', $obj_id),
					  'category_id' => array('integer', $cat_id),
					  'notice_id'	=> array('integer', $notice_id),
					  'filename' => array('text', $new_filename),
					  'is_selected' => array('integer', 1),
					  'file_type' => array('text', 'img'))
		);
	}
?>
<#25>
<?php

	include_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/Noticeboard/classes/class.ilNoticeRepository.php';
	$res = $ilDB->queryF('SELECT filename FROM xnob_images WHERE file_type = %s',
	array('text'), array('img'));
	
	while($row = $ilDB->fetchAssoc($res))
	{
		$path = ilUtil::getWebspaceDir().'/xnob/'.$row['filename'];
		ilNoticeRepository::createThumbnail($path, 65, 65);	
	}
?>	
<#26>
<?php

$ilDB->manipulateF('DELETE FROM xnob_settings WHERE keyword = %s OR keyword = %s',
array('text','text'), array('img_thumbnail_height', 'img_thumbnail_width'));


$ilDB->insert('xnob_settings',
array('keyword' => array('text', 'img_thumbnail_height'),
	'value'	=> array('text', '65')
));

$ilDB->insert('xnob_settings',
	array('keyword' => array('text', 'img_thumbnail_width'),
		  'value'	=> array('text', '65')
));
?>
<#27>
<?php
foreach(array('img_preview_height' => 450, 'img_preview_width' => 450) as $keyword => $value)
{
	$res = $ilDB->queryF("SELECT keyword, value FROM xnob_settings WHERE keyword = %s", array('text'), array($keyword));
	$row = $ilDB->fetchAssoc($res);

	if(!is_array($row) || !is_numeric($row['value']))
	{
		if(!is_array($row))
		{
			$ilDB->insert('xnob_settings',
				array(
					'keyword' => array('text', $keyword),
					'value'	  => array('text', $value)
				)
			);
		}
		else
		{
			$ilDB->update('xnob_settings',
				array(
					'value'	  => array('text', $value)
				),
				array(
					'keyword' => array('text', $keyword)
				)
			);
		}
	}
}
?>
	
	
	
	

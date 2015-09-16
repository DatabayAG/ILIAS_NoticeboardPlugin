<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

include_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');
 
/**
 * Notice board repository object plugin
 * Documentation: http://www.ilias.de/docu/ilias.php?ref_id=42&from_page=29964&cmd=layout&cmdClass=illmpresentationgui&cmdNode=e&baseClass=ilLMPresentationGUI&obj_id=29962
 *
 * @author Jens Conze <jc@databay.de>
 * @version $Id$
 */
class ilNoticeboardPlugin extends ilRepositoryObjectPlugin
{
	/**
	 * Returns name of the plugin
	 *
	 * @return <string
	 * @access public
	 */
	public function getPluginName()
	{
		return 'Noticeboard';
	}

	/**
	 * 
	 */
	protected function uninstallCustom()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		if($ilDB->tableExists('xnob_notices'))
		{
			$ilDB->dropTable('xnob_notices');
		}

		if($ilDB->sequenceExists('xnob_notices'))
		{
			$ilDB->dropSequence('xnob_notices');
		}

		if($ilDB->tableExists('xnob_categories'))
		{
			$ilDB->dropTable('xnob_categories');
		}

		if($ilDB->sequenceExists('xnob_categories'))
		{
			$ilDB->dropSequence('xnob_categories');
		}
		
		if($ilDB->tableExists('xnob_cat_permissions'))
		{
			$ilDB->dropTable('xnob_cat_permissions');
		}
		
		if($ilDB->sequenceExists('xnob_cat_permissions'))
		{
			$ilDB->dropSequence('xnob_cat_permissions');
		}

		if($ilDB->tableExists('xnob_properties'))
		{
			$ilDB->dropTable('xnob_properties');
		}

		if($ilDB->tableExists('xnob_settings'))
		{
			$ilDB->dropTable('xnob_settings');
		}
		
		if($ilDB->tableExists('xnob_images'))
		{
			$ilDB->dropTable('xnob_images');
		}
		
		if($ilDB->sequenceExists('xnob_images_seq'))
		{
			$ilDB->dropSequence('xnob_images_seq');
		}

		ilUtil::delDir(ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR .'xnob');
	}
}
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
}
?>

<?php

/**
 * @author Nadia Ahmad <nahmad@databay.de>
 */

class ilNoticeboardConfig
{
	public static function setSetting($keyword, $value = '')
	{
		/** 
		 * @var $ilDB ilDB */
		global $ilDB;

		$ilDB->manipulatef('DELETE FROM xnob_settings WHERE keyword = %s',
			array('text'), array($keyword));

		$ilDB->insert('xnob_settings',
			array(
				'keyword' => array('text', $keyword),
				'value'   => array('text', $value)
			));
	}

	public static function getSetting($keyword)
	{
		global $ilDB;

		$res = $ilDB->queryF('SELECT value FROM xnob_settings WHERE keyword = %s',
			array('text'), array($keyword));

		$row = $ilDB->fetchAssoc($res);
		return $row['value'];
	}
}	
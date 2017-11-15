<?php

/**
 * Class ilNoticeboardConfig
 * @author Nadia Matuschek <nmatuschek@databay.de> 
 */
class ilNoticeboardConfig
{
	/**
	 * @param string $keyword
	 * @param string $value
	 */
	public static function setSetting($keyword, $value = '')
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$ilDB->manipulatef('DELETE FROM xnob_settings WHERE keyword = %s',
			array('text'), array($keyword));

		$ilDB->insert('xnob_settings',
			array(
				'keyword' => array('text', $keyword),
				'value'   => array('text', $value)
			));
	}
	
	/**
	 * @param string $keyword
	 * @return string
	 */
	public static function getSetting($keyword)
	{
		global $DIC;
		$ilDB = $DIC->database();

		$res = $ilDB->queryF('SELECT value FROM xnob_settings WHERE keyword = %s',
			array('text'), array($keyword));

		$row = $ilDB->fetchAssoc($res);
		return $row['value'];
	}
}	
<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

require_once 'Services/Calendar/classes/class.ilDatePresentation.php';

/**
 * Class ilNoticeboardUtil
 */
class ilNoticeboardUtil
{
	/**
	 * Returns formatted price
	 * @param float     $a_price
	 * @param string    $a_cur
	 * @param int       $a_type
	 * @return string
	 */
	static public function formatPrice($a_price, $a_cur, $a_type)
	{
		global $DIC;

		$val = '';
		if ((float)$a_price > 0) 
		{
			$val = number_format($a_price, 2, '.', '') . ' ' . $a_cur;
			if ($a_type == ilNotice::PRICE_TYPE_ONO)
			{
				$val .= ' (' . $DIC->language()->txt('rep_robj_xnob_ono') . ')';
			}
		}
		else if ($a_type == ilNotice::PRICE_TYPE_FOR_FREE)
		{
			$val = $DIC->language()->txt('rep_robj_xnob_for_free');
		}
		
		return $val;
	}

	/**
	 * Returns formatted date
	 *
	 * @param integer $a_date Date (Unix-Timestamp)
	 * @return string
	 */
	static public function formatDate($a_date)
	{
		return ilDatePresentation::formatDate(new ilDateTime((int)$a_date, IL_CAL_UNIX));
	}
}

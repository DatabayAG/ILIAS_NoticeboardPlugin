<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

require_once 'Services/Calendar/classes/class.ilDatePresentation.php';

/**
 *	Utility class.
 *
 *	This class implements some utility methods used
 *	in the development process of the Noticeboard
 *	plugin. Mainly this class offers convenient
 *	data formatting processes.
 *
 *	@author Grégory Saive <gsaive@databay.de>
 */
class ilNoticeboardUtil
{
	/**
	 * Returns formatted price
	 *
	 * @param float $a_price Price
	 * @param integer $a_type Type of price: 'fix price', 'on nearest offer' or 'for free'
	 * @return string
	 * @access protected
	 */
	static public function formatPrice($a_price, $a_cur, $a_type)
	{
		global $lng;

		$val = '';
		if ((float)$a_price > 0) {
			$val = number_format($a_price, 2, '.', '') . ' ' . $a_cur;
			if ($a_type == ilNotice::PRICE_TYPE_ONO)
				$val .= ' ('.$lng->txt('rep_robj_xnob_ono').')';
		}
		else if ($a_type == ilNotice::PRICE_TYPE_FOR_FREE)
			$val = $lng->txt('rep_robj_xnob_for_free');

		return $val;
	}

	/**
	 * Returns formatted date
	 *
	 * @param integer $a_date Date (Unix-Timestamp)
	 * @return string
	 * @access protected
	 */
	static public function formatDate($a_date)
	{
		return ilDatePresentation::formatDate(new ilDateTime((int)$a_date, IL_CAL_UNIX));
	}

}

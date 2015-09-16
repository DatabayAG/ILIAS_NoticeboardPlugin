<?php

/* Copyright (c) 2011 Databay AG, Freeware, see license.txt */

require_once 'Services/Html/classes/class.ilHtmlPurifierAbstractLibWrapper.php';

/** 
 * Concrete class for sanitizing html of forum posts
 * 
 * @author	Michael Jansen <mjansen@databay.de>
 * @author	Jens Conze <jc@databay.de>
 * @version	$Id$
 */
class ilHtmlNoticePurifier extends ilHtmlPurifierAbstractLibWrapper
{	
	/** 
	 * Type of purifier
	 *
	 * @var		string
	 * @access	public
	 * @static
	 */
	public static $_type = 'xnob_notice';
	
	/** 
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/** 
	 * Concrete function which builds a html purifier config instance
	 *
	 * @access	protected
	 * @return	HTMLPurifier_Config Instance of HTMLPurifier_Config
	 */
	protected function getPurifierConfigInstance()
	{
		include_once 'Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php';
		
		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.DefinitionID', 'ilias notice');
		$config->set('HTML.DefinitionRev', 1);
		$config->set('Cache.SerializerPath', ilHtmlPurifierAbstractLibWrapper::_getCacheDirectory());
		$config->set('HTML.Doctype', 'XHTML 1.0 Strict');		
		
		// Bugfix #5945: Necessary because TinyMCE does not use the "u" 
		// html element but <span style="text-decoration: underline">E</span>
		$tags = ilObjAdvancedEditing::_getAllHTMLTags();		
		if(in_array('u', $tags) && !in_array('span', $tags)) $tags[] = 'span';	
				
		$unsupportedElements = array('object', 'applet', 'area', 'base', 'basefont', 'button', 'center', 'dir', 'fieldset', 'font', 'form', 'iframe', 'input', 'isindex', 'label', 'legend', 'link', 'map', 'menu', 'optgroup', 'option', 's', 'select', 'textarea');
		foreach($unsupportedElements as $elm)
		{
			$key = array_search($elm, $tags);
			if($key !== null)
			{
				unset($tags[$key]);
			}
		}		
		
		$config->set('HTML.AllowedElements', $this->removeUnsupportedElements($tags));
		$config->set('HTML.ForbiddenAttributes', 'div@style');
		
		if ($def = $config->maybeGetRawHTMLDefinition()) {		
			$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');			
		}		

		return $config;
	}	
}
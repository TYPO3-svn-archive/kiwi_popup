<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Kiwi <kiefer-a@web.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'Kiwi Popup' for the 'kiwi_popup' extension.
 *
 * @author	Kiwi <kiefer-a@web.de>
 * @package	TYPO3
 * @subpackage	tx_kiwipopup
 */
class tx_kiwipopup_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_kiwipopup_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_kiwipopup_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'kiwi_popup';	// The extension key.
	var $pi_checkCHash = true;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		// GET FLEXFORM DATA
		$this->pi_initPIflexForm();
		$piFlexForm = $this->cObj->data['pi_flexform'];
		if (is_array($piFlexForm['data'])) {
			foreach ( $piFlexForm['data'] as $sheet => $data ) {
				foreach ( $data as $lang => $value ) {
					foreach ( $value as $key => $val ) {
						$this->ffdata[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
					}
				}
			}
		}

		// show only once per session (if activated in ff)
		// if (!$this->ffdata['sessionStorage'] || !t3lib_div::inArray($this->sD['shown'], $this->cObj->data['uid'])) {
		if (!$this->ffdata['sessionStorage'] || !$this->alreadyShown()) {

			// IMAGE
			if ($this->ffdata['type'] == 'IMAGE') {
				// generate file path
				$imageConf['file'] = 'uploads/tx_kiwipopup/'.$this->ffdata['popupfile'];
				if (intval($this->ffdata['imageMaxW'])) $imageConf['file.']['maxW'] = intval($this->ffdata['imageMaxW']);
				if (intval($this->ffdata['imageMaxH'])) $imageConf['file.']['maxH'] = intval($this->ffdata['imageMaxH']);
				$content = $this->cObj->IMAGE($imageConf);
			}

			// HTML
			if ($this->ffdata['type'] == 'HTML') {
				$content = $this->ffdata['popupcontent'];
			}

			// COBJ
			if ($this->ffdata['type'] == 'COBJ') {
				$cObjConf = array(
					'tables' => 'tt_content',
					'source' => $this->ffdata['cObject'],
					'dontCheckPid' => 1
				);
				$content = $this->cObj->RECORDS($cObjConf);
			}

			// render content
			if (!empty($content)) {

				// get html template
				$this->templateFile = t3lib_extMgm::siteRelPath($this->extKey).'res/kiwi_popup.tmpl';
				$this->templateCode = $this->cObj->fileResource($this->templateFile);

				// init Fluid
				$this->initFluid();

				// include jQuery library?
				if ($this->ffdata['jQueryInclude']) $this->jQueryInclude();

				// loadCSS
				$cssfile = t3lib_extMgm::siteRelPath($this->extKey).'res/kiwi_popup.css';
				$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<link rel="stylesheet" type="text/css" href="'.$cssfile.'" />';

				// get html template
				$this->templateFile = t3lib_extMgm::siteRelPath($this->extKey).'pi1/'.$this->prefixId.'.html';
				$this->templateCode = $this->cObj->fileResource($this->templateFile);

				// show caption?
				if ($this->ffdata['showCaption']) {
					$this->renderer->assign('caption', $this->ffdata['captionText']);
				}

				// autoclose enabled?
				if ($this->ffdata['autoClose']) {
					$autoclose = intval($this->ffdata['autoCloseSeconds']);
					// hide close button?
					if ($this->ffdata['hideCloseButton']) $hideclosebutton = 1;
					else $hideclosebutton = 0;
				} else {
					$autoclose = 0;
					$hideclosebutton = 0;
				}
				$this->renderer->assign('autoclose', $autoclose);
				$this->renderer->assign('hideclosebutton', $hideclosebutton);



				// link popuup?
				if ($this->ffdata['link']) {
					unset($linkconf);
					$linkconf['parameter'] = $this->ffdata['link'];
					$linkURL = $this->cObj->typoLink_URL($linkconf);
					$this->renderer->assign('link', $linkURL);
				}

				// assign content to renderer
				$this->renderer->assign('content', $content);

				// store in session
				if ($this->ffdata['sessionStorage']) {
					$sessionVars = $this->generateSessionData();
				}

				// render
				return $this->pi_wrapInBaseClass($this->renderer->render());

			}
			return $this->pi_wrapInBaseClass($content);
		}
	}

	/*
	 * function generateSessionData
	 */
	function generateSessionData() {
		$sessionVars = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_kiwipopup');
		switch ($this->ffdata['sessionStorageOption']) {
			case 'general': // GENERAL
				$sessionVars['general'] = 1;
				break;
			case 'page': // PAGE ID
				$sessionVars['page'][$GLOBALS['TSFE']->id] = 1;
				break;
			case 'plugin': // PLUGIN UID
			default:
				$sessionVars['plugin'][$this->cObj->data['uid']] = 1;
				break;
		}
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_kiwipopup', $sessionVars);
		$GLOBALS['TSFE']->storeSessionData();
	}


	/*
	 * function alreadyShown
	 */
	function alreadyShown() {

		// do not process check if session storage is disabled
		if (!$this->ffdata['sessionStorage']) return false;

		// get session data
		$this->sD = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_kiwipopup');

		// check if session entry for current element exists
		switch($this->ffdata['sessionStorageOption']) {
			case 'general': // GENERAL
				if ($this->sD['general'] == 1) return true;
				break;
			case 'page': // PAGE ID
				if ($this->sD['page'][$GLOBALS['TSFE']->id]==1) return true;
				break;
			case 'plugin': // PLUGIN UID
				if ($this->sD['plugin'][$this->cObj->data['uid']] == 1) return true;
				break;
		}
		// return false if no session var found
		return false;
	}

	/*
	 * function initFluid
	 * @param $arg
	 */
	function initFluid() {
		$this->renderer = t3lib_div::makeInstance('Tx_Fluid_View_TemplateView');
		$controllerContext = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_ControllerContext');
		$controllerContext->setRequest(t3lib_div::makeInstance('Tx_Extbase_MVC_Request'));
		$this->renderer->setControllerContext($controllerContext);
		$this->renderer->setTemplatePathAndFilename($this->templateFile);
	}


	/*
	 * function jQueryInclude
	 */
	function jQueryInclude() {
		$jQuerySrc = t3lib_extMgm::siteRelPath($this->extKey).'res/jQuery/jquery-1.7.1.min';
		$GLOBALS['TSFE']->additionalHeaderData['kiwipopup_jquery'] = '<script type="text/javascript" src="'.$jQuerySrc.'"></script>';
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kiwi_popup/pi1/class.tx_kiwipopup_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kiwi_popup/pi1/class.tx_kiwipopup_pi1.php']);
}

?>

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

		if ($this->ffdata['sessionStorage']) {
			// get session data
			$this->sD = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_kiwipopup');
		}


		// show only once per session (if activated in ff)
		if (!$this->ffdata['sessionStorage'] || $this->sD['shown'] != $GLOBALS['TSFE']->id) {

			// generate file path
			$filePath = 'uploads/tx_kiwipopup/'.$this->ffdata['popupfile'];

			// check if path is valid
			if (t3lib_div::validPathStr($filePath)) {

				// loadCSS
				$cssfile = t3lib_extMgm::siteRelPath($this->extKey).'res/kiwi_popup.css';
				$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<link rel="stylesheet" type="text/css" href="'.$cssfile.'" />';

				// include js
				$lightboxPath = t3lib_extMgm::siteRelPath($this->extKey).'res/lightbox.js';
				$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script type="text/javascript" src="'.$lightboxPath.'"></script>';

				// generate fake link
				unset($linkconf);
				$linkconf['parameter'] = $filePath;
	 			$fileLink =  $this->cObj->typoLink('text',$linkconf);
				// print fake link and
				$content = '
					<span style="display:none;"><a href="'.$filePath.'" rel="lightbox" id="kiwi_popup_element">image #1</a></span>
					<script type="text/javascript">
						var elem = document.getElementById("kiwi_popup_element");
						initLightbox();
						showLightbox(elem);
					</script>';
				$sessionVars['shown'] = $GLOBALS['TSFE']->id;

				if ($this->ffdata['sessionStorage']) {
					$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_kiwipopup', $sessionVars);
					$GLOBALS['TSFE']->storeSessionData();
				}

			}
			return $this->pi_wrapInBaseClass($content);
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kiwi_popup/pi1/class.tx_kiwipopup_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kiwi_popup/pi1/class.tx_kiwipopup_pi1.php']);
}

?>
<?php
namespace TYPO3\GoMapsExt\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Marc Hirdes <Marc_Hirdes@gmx.de>, clickstorm GmbH
 *  (c) 2013 Mathias Brodala <mbrodala@pagemachine.de>, PAGEmachine AG
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 *
 *
 * @package go_maps_ext
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class MapController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * mapRepository
	 *
	 * @var \TYPO3\GoMapsExt\Domain\Repository\MapRepository
	 */
	protected $mapRepository;
	
	/**
	 * addressRepository
	 *
	 * @var \TYPO3\GoMapsExt\Domain\Repository\AddressRepository
	 */
	protected $addressRepository;

	/**
	 * injectMapRepository
	 *
	 * @param \TYPO3\GoMapsExt\Domain\Repository\MapRepository $mapRepository
	 * @return void
	 */
	public function injectMapRepository(\TYPO3\GoMapsExt\Domain\Repository\MapRepository $mapRepository) {
		$this->mapRepository = $mapRepository;
	} 
	
	/**
	 * injectAddressRepository
	 *
	 * @param \TYPO3\GoMapsExt\Domain\Repository\AddressRepository $addressRepository
	 * @return void
	 */
	public function injectAddressRepository(\TYPO3\GoMapsExt\Domain\Repository\AddressRepository $addressRepository) {
		$this->addressRepository = $addressRepository;
	} 
	
	public function initializeAction() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['go_maps_ext']);
        $pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$addJsMethod = 'addJs';

        if($this->extConf['footerJS'] == 1) {
            $addJsMethod = 'addJsFooter';
        }

		$googleMapsLibrary = $this->extConf['googleMapsLibrary'] ?
			htmlentities($this->extConf['googleMapsLibrary']) : 
			'http://maps.google.com/maps/api/js?v=3.13&sensor=false';
        if($this->settings['language']) {
            $googleMapsLibrary .= '&language=' . $this->settings['language'];
        }

        $pageRenderer->{$addJsMethod . 'Library'}('googleMaps', $googleMapsLibrary, 'text/javascript', FALSE, FALSE, '', TRUE);

		$this->extConf['openByClick'] = $this->settings['infoWindow']['openByClick'];
		$this->extConf['closeByClick'] = $this->settings['infoWindow']['closeByClick'];
		
		if($this->extConf['include_library'] == 1) {
            $pageRenderer->{$addJsMethod . 'Library'}('jQuery', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->request->getControllerExtensionKey()) . 'Resources/Public/Scripts/jquery.min.js');
		}

        $scripts[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->request->getControllerExtensionKey()) . 'Resources/Public/Scripts/markerclusterer_compiled.js';

        $scripts[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->request->getControllerExtensionKey()) . 'Resources/Public/Scripts/jquery.gomapsext.js';

        foreach ($scripts as $script) {
            $pageRenderer->{$addJsMethod . 'File'}($script);
        }
	}
	
	/**
	 * action show
	 *
	 * @param \TYPO3\GoMapsExt\Domain\Model\Map $map
	 * @return void
	 */
	public function showAction(\TYPO3\GoMapsExt\Domain\Model\Map $map = NULL) {
		$map = $this->mapRepository->findByUid($this->settings['map']);
        $pid = $this->settings['storagePid'];
        $categoriesArray = array();

        $addresses = $this->addressRepository->findAllAddresses($map, $pid);

        if($map->isShowCategories()) {
            foreach($addresses as $address) {
                $addrCats = $address->getCategories();
                foreach($addrCats as $addrCat) {
                    $categoriesArray[$addrCat->getUid()] = $addrCat->getName();
                }
            }
        }
		
		$this->view->assignMultiple(array(
			'request' => $this->request->getArguments(),
			'map' => $map,
			'addresses' => $addresses,
			'settings' => $this->extConf,
            'categories' => $categoriesArray
		));
	} 

}
?>
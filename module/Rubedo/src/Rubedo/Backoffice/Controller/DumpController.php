<?php
/**
 * Rubedo -- ECM solution
 * Copyright (c) 2014, WebTales (http://www.webtales.fr/).
 * All rights reserved.
 * licensing@webtales.fr
 *
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license.
 *
 * @category   Rubedo
 * @package    Rubedo
 * @copyright  Copyright (c) 2012-2014 WebTales (http://www.webtales.fr)
 * @license    http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */
namespace Rubedo\Backoffice\Controller;

use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;
use Zend\Debug\Debug;
use Zend\Json\Json;
use Zend\View\Model\JsonModel;


/**
 * Controller providing zipped dump file from rubedo collections
 *
 * @author dfanchon
 * @category Rubedo
 * @package Rubedo
 *
 */
class DumpController extends DataAccessController
{

	private $_collections = [
		'Blocks',
		'ContentTypes',
		'Contents',
		'CustomThemes',
		'Dam',
		'DamTypes',
		'Directories',
		'Groups',
		'Languages',
		'Masks',
		'Pages',
		'Queries',
		'ReusableElements',
		'Shippers',
		'Sites',
		'Taxes',
		'Taxonomy',
		'TaxonomyTerms',
		'Themes',
		'UserTypes',
		'Users',
		'Workspaces'
	];
	
	private $_files = array();
	
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * The default read Action
     *
     * Return the content of the collection, get filters from the request
     * params, get sort from request params
     */
    public function indexAction()
    {
    	$collection = $this->params()->fromQuery('collection','all');
    	if ($collection=='all') {
    		$collections = $this->_collections;
    	} else {
    		$collections = [$collection];
    	}
    	   	
    	foreach($collections as $collection) {
	    	$this->_dataService = Manager::getService($collection);
	    	$response = array();
	    	$response[$collection] = $this->_dataService->getList();
	    	
	    	$fileName = $collection.'.json';
	    	$filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
	    	$fp = fopen($filePath, 'w');
	    	fwrite($fp, json_encode($response));
	    	fclose($fp);
	    	$this->_files[] = $filePath;
    	}

    	$zip = new \ZipArchive();
    	$zipFileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "rubedo.zip";
    	$archive = $zip->open($zipFileName,\ZipArchive::CREATE);
    	
    	if ($archive === TRUE) {

	    	foreach ($this->_files as $file) {
	    		$zip->addFile($file, basename($file));
	    	}
	    	$zip->close();
	    	
	    	$content = file_get_contents($zipFileName);
	    	$response = $this->getResponse();
	    	$headers = $response->getHeaders();
	    	$headers->addHeaderLine('Content-Type', 'application/zip');
	    	$headers->addHeaderLine('Content-Disposition', "attachment; filename=\"rubedo.zip\"");
	    	$headers->addHeaderLine('Accept-Ranges', 'bytes');
	    	$headers->addHeaderLine('Content-Length', strlen($content));
	    	$response->setContent($content);
	    	return $response;	    	
    	}

    }



}
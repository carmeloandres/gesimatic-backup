<?php

namespace GesimaticBackup\Api;

use Gesimatic\Api\Controllers\AdminController;
use Gesimatic\Api\Base\CommonResponse;

use GesimaticStaticForms\Api\Middleware\CredentialValidator;
use GesimaticStaticForms\Api\Middleware\SignatureValidator;
use GesimaticStaticForms\Api\Middleware\RequestValidator;
use GesimaticStaticForms\Api\Middleware\ResolveRole;

//use GesimaticLoginAttempts\Core\Setup;

/**
 * Class GetBackupDownloadAction
 *
 * This class contains the code necessary to manage the data from get_backup_download api request.
 *
 * @package gesimatic-backup
 */
class GetBackupDownloadAction {

    /** 
     * To validate 
     * 
     * This method perfoms the necesaria actions to validate data.
     * 
     */
    public static function validate($params){
        // check if action is as expected
        if(isset($params['action']) && ($params['action'] === 'get_backup_download')){
            return true;
        } else return false;

        return false;
    }

    /**
     * To handle 
     * 
     * This method perfoms the necesaria actions to handle data, to perform the request.
     * 
     */
    public static function handle($validated){
        return CommonResponse::error();
    }
}
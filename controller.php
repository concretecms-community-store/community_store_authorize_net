<?php
namespace Concrete\Package\CommunityStoreAuthorizeNet;

use Package;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_authorize_net';
    protected $appVersionRequired = '5.7.5';
    protected $pkgVersion = '0.9.1';

    public function getPackageDescription()
    {
        return t("Authorize.Net Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Authorize.Net Payment Method");
    }
    
    public function install()
    {
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_authorize_net','Authorize.Net',$pkg);
        }
    }

    public function on_start() {
        require 'vendor/autoload.php';
    }

    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_authorize_net');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

}
?>
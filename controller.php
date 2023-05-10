<?php
namespace Concrete\Package\CommunityStoreAuthorizeNet;

use Concrete\Core\Package\Package;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_authorize_net';
    protected $appVersionRequired = '8.0';
    protected $pkgVersion = '1.1';


    protected $pkgAutoloaderRegistries = [
        'src/CommunityStore' => '\Concrete\Package\CommunityStoreAuthorizeNet\Src\CommunityStore',
    ];

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
        if (!@include(__DIR__ . '/vendor/autoload.php')) {
            throw new ErrorException(t('Third party libraries not installed. Use a release version of this add-on with libraries pre-installed, or run composer install against the package folder.'));
        }

        $installed = app()->make('Concrete\Core\Package\PackageService')->getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_authorize_net','Authorize.Net',$pkg);
        }
    }

    public function on_start() {
        require __DIR__ . '/vendor/autoload.php';
    }

    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_authorize_net');
        if ($pm) {
            $pm->delete();
        }
        parent::uninstall();
    }

}
?>

<?php

namespace Concrete\Package\CommunityStoreSumup;

use \Concrete\Core\Package\Package;
use \Concrete\Core\Support\Facade\Route;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;
use Whoops\Exception\ErrorException;


class Controller extends Package
{
    protected $pkgHandle = 'community_store_sumup';
    protected $appVersionRequired = '8.0';
    protected $pkgVersion = '1.0.2';
    protected $packageDependencies = ['community_store'=>'2.0'];

    public function on_start()
    {
        require __DIR__ . '/vendor/autoload.php';
        Route::register('/checkout/sumupcreatecheckout','\Concrete\Package\CommunityStoreSumup\Src\CommunityStore\Payment\Methods\CommunityStoreSumup\CommunityStoreSumupPaymentMethod::createCheckout');
        Route::register('/sumupauthreturn','\Concrete\Package\CommunityStoreSumup\Src\CommunityStore\Payment\Methods\CommunityStoreSumup\CommunityStoreSumupPaymentMethod::sumupauthreturn');
        Route::register('/checkout/sumupcompleteorder','\Concrete\Package\CommunityStoreSumup\Src\CommunityStore\Payment\Methods\CommunityStoreSumup\CommunityStoreSumupPaymentMethod::completeOrder');
    }

    protected $pkgAutoloaderRegistries = [
        'src/CommunityStore' => '\Concrete\Package\CommunityStoreSumup\Src\CommunityStore',
    ];

    public function getPackageDescription()
    {
        return t("SumUp Checkout Payment Method for Community Store");
    }

    public function getPackageName()
    {
        return t("SumUp Payment Method");
    }

    public function install()
    {
        if (!@include(__DIR__ . '/vendor/autoload.php')) {
            throw new ErrorException(t('Third party libraries not installed. Use a release version of this add-on with libraries pre-installed, or run composer install against the package folder.'));
        }

        $pkg = parent::install();
        $pm = new PaymentMethod();
        $pm->add('community_store_sumup','SumUp',$pkg);
    }
    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_sumup');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

}
?>

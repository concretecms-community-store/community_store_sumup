<?php
namespace Concrete\Package\CommunityStoreSumup\Src\CommunityStore\Payment\Methods\CommunityStoreSumup;

use Concrete\Core\Routing\Redirect;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Http\ResponseFactory;
use Concrete\Core\Support\Facade\Session;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;

class CommunityStoreSumupPaymentMethod extends StorePaymentMethod
{
    public function getName()
    {
        return 'SumUp';
    }

    private function getCurrencies()
    {
        return [
            'EUR' => t('Euro'),
            'GBP' => t('British Pounds Sterling'),
            'USD' => t('US Dollar'),
            'BRL' => t('Brazilian Real'),
            'CLP' => t('Chilean Peso'),
            'PLN' => t('Polish Zloty'),
            'CHF' => t('Swiss Franc)'),
            'SEK' => t('Swedish Krona'),
            'CZK' => t('Czech Koruna'),
            'DKK' => t('Danish Krone'),
            'HUF' => t('Hungarian Forint'),
            'BGN' => t('Bulgarian Lev')
        ];
    }

    public function dashboardForm()
    {
        $this->set('sumupPayToEmail', Config::get('community_store_sumup.payToEmail'));
        $this->set('sumupCurrency', Config::get('community_store_sumup.currency'));
        $this->set('sumupShowZip', Config::get('community_store_sumup.showZip'));
        $this->set('sumupClientID', Config::get('community_store_sumup.clientID'));
        $this->set('sumupClientSecret', Config::get('community_store_sumup.clientSecret'));
        $this->set('sumupRefreshToken', Config::get('community_store_sumup.refreshToken'));
        $this->set('sumupCurrencies', $this->getCurrencies());
        $this->set('form', Application::getFacadeApplication()->make("helper/form"));
    }

    public function save(array $data = [])
    {
        Config::save('community_store_sumup.payToEmail', $data['sumupPayToEmail']);
        Config::save('community_store_sumup.showZip', $data['sumupShowZip']);
        Config::save('community_store_sumup.currency', $data['sumupCurrency']);
        Config::save('community_store_sumup.clientID', $data['sumupClientID']);
        Config::save('community_store_sumup.clientSecret', $data['sumupClientSecret']);
    }

    public function validate($args, $e)
    {
        return $e;
    }

    public function checkoutForm()
    {
        $pmID = StorePaymentMethod::getByHandle('community_store_sumup')->getID();
        $this->set('sumupCurrency', Config::get('community_store_sumup.currency'));
        $this->set('locale', str_replace('_', '-', Localization::activeLocale()));
        $this->set('showZip', (bool)Config::get('community_store_sumup.showZip'));
        $this->set('pmID', $pmID);
    }

    public function submitPayment()
    {
        $checkoutID = $_POST['sumupCheckoutID'];

        if (!$checkoutID) {
            $checkoutID = $_GET['checkout_id'];
        }

        $clientID =  Config::get('community_store_sumup.clientID');
        $clientSecret = Config::get('community_store_sumup.clientSecret');
        $refreshToken = Config::get('community_store_sumup.refreshToken');

        if ($checkoutID) {
            try {
                $sumup = new \SumUp\SumUp([
                    'app_id' => $clientID,
                    'app_secret' => $clientSecret,
                    'scopes' => ['payments'],
                    'refresh_token' => $refreshToken
                ]);

                $refreshedAccessToken = $sumup->refreshToken();
                $value = $refreshedAccessToken->getValue();

                Config::save('community_store_sumup.refreshToken', $refreshedAccessToken->getRefreshToken());

               $checkoutService = $sumup->getCheckoutService($refreshedAccessToken);
               $checkout =  $checkoutService->findById($checkoutID);

            } catch (\SumUp\Exceptions\SumUpAuthenticationException $e) {
                return ['error' => 1, 'errorMessage' =>$e->getMessage()];
            } catch (\SumUp\Exceptions\SumUpResponseException $e) {
                return ['error' => 1, 'errorMessage' =>$e->getMessage()];
            } catch (\SumUp\Exceptions\SumUpSDKException $e) {
                return ['error' => 1, 'errorMessage' =>$e->getMessage()];
            }

            if ($checkout->getBody()->status == 'PAID') {
                $transID = $checkout->getBody()->transaction_code;
                return ['error' => 0, 'transactionReference' => $transID];
            }

        }

         return ['error' => 1, 'errorMessage' =>t('Transaction did not complete')];

    }

    public function createCheckout()
    {
        $this->set('currency', Config::get('community_store_sumup.currency'));
        $currency = Config::get('community_store_sumup.currency');
        $price = number_format(StoreCalculator::getGrandTotal(), 2, '.', '');

        $checkoutRef = 'CO' . time();

        $clientID =  Config::get('community_store_sumup.clientID');
        $clientSecret = Config::get('community_store_sumup.clientSecret');
        $refreshToken = Config::get('community_store_sumup.refreshToken');
        $payToEmail = Config::get('community_store_sumup.payToEmail');
        $checkoutId = '';

        try {
            $sumup = new \SumUp\SumUp([
                'app_id'        => $clientID,
                'app_secret'    => $clientSecret,
                'scopes'      => ['payments'],
                'refresh_token' => $refreshToken
            ]);

            $refreshedAccessToken = $sumup->refreshToken();
            Config::save('community_store_sumup.refreshToken', $refreshedAccessToken->getRefreshToken());

            $checkoutService = $sumup->getCheckoutService($refreshedAccessToken);
            $checkoutResponse = $checkoutService->create($price, $currency, $checkoutRef, $payToEmail);
;            $checkoutId = $checkoutResponse->getBody()->id;

        } catch (\SumUp\Exceptions\SumUpAuthenticationException $e) {
            echo 'Authentication error: ' . $e->getMessage();
        } catch (\SumUp\Exceptions\SumUpResponseException $e) {
            echo 'Response error: ' . $e->getMessage();
        } catch(\SumUp\Exceptions\SumUpSDKException $e) {
            echo 'SumUp SDK error: ' . $e->getMessage();
        }

        $return = [
            'checkoutId'=>$checkoutId,
        ];

        echo json_encode($return);
        exit();
    }

    public function sumupauthreturn() {
        $code = $this->request->query->get('code');

        $clientID =  Config::get('community_store_sumup.clientID');
        $clientSecret = Config::get('community_store_sumup.clientSecret');

        if ($code) {

            try {
                $sumup = new \SumUp\SumUp([
                    'app_id' => $clientID,
                    'app_secret' => $clientSecret,
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'scopes' => ['payments'],
                ]);

                $refreshToken = $sumup->getAccessToken()->getRefreshToken();

                if ($refreshToken) {
                    Config::save('community_store_sumup.refreshToken', $refreshToken);

                    $factory = $this->app->make(ResponseFactory::class);
                    return $factory->redirect(Url::to('/dashboard/store/settings#settings-payments'));
                }
            } catch (\Exception $e) {
                    echo t('An error occurred fetching an authorization code. Please check the Client ID, Client Secret values you are using and that the Authorized redirect URL is set within SumUp correctly.');
            }
        }
    }

    public function completeOrder() {
        $pm = PaymentMethod::getByHandle('community_store_sumup');

        $langpath = '';

        if (false === $pm || (Cart::isShippable() && !Session::get('community_store.smID'))) {
            return Redirect::to($langpath . '/checkout');
        }

        // if no more items in cart, refresh the checkout page
        if (Cart::getTotalItemsInCart() == 0) {
            return Redirect::to($langpath . '/checkout');
        }

        $payment = $this->submitPayment();

        if (1 == $payment['error']) {
            $errors = $payment['errorMessage'];
            Session::set('paymentErrors', $errors);

            return Redirect::to($langpath . '/checkout/failed#payment');

        } else {
            $transactionReference = $payment['transactionReference'];

            $order = Order::add($pm, $transactionReference);

            // unset the shipping type, as next order might be unshippable
            Session::set('community_store.smID', '');
            Session::set('notes', '');
            return Redirect::to($order->getOrderCompleteDestination());
        }
    }

    public function headerScripts($view) {
        $view->addHeaderItem('<script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script><script>var sumupformobserver = false;var sumupCard = false;</script>');
    }

    public function getPaymentMinimum()
    {
        return 0.5;
    }

}

return __NAMESPACE__;

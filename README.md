# Community Store SumUp
SumUp payment add-on for Community Store for Concrete CMS - https://sumup.com/

### Prerequisite
To be able to process transactions through a website your SumUp account must have the an authorization 'scope' named `payments` enabled.
The `payments` scope is _restricted_ and not enabled by default - contact [SumUp](https://cloud.crm.sumup.com/sumup-developers-contact-form) to request that this `scope' be enabled on your account for the purpose of processing website payments.

## Setup
Install Community Store First.
Download a 'release' zip of the add-on, unzip this to the packages folder of your Concrete CMS install (alongside the community_store folder) and install via the dashboard.
Once installed, configure the payment method through the Settings/Payments dashboard section for 'Store'.

### Configuration
After installing the payment add-on:
- Log into your SumUp Dashboard
- Click on your profile in the top right, and select 'For Developers'
- Complete the required details of the Consent screen
- Open up the Concrete dashboard, Store->Settings->Payments tab, and view the SumUp section. You will see a URL for the 'Authorized redirect URL'. Copy that URL.
- Back within the SumUP Dashboard, at the bottom of the screen select 'Create client credentials'
  - Select Web
  - Type in an appropriate name, e.g. 'Concrete Website Integration'
  - Paste the previously copied URL into the Authorized redirect URL field
  - Press Save
- Download the JSON file for the newly created credentials, and open it in a text editor, and return to your Concrete dashboard page
  - Within the JSON file you will see client_id and client_secret values - copy these into the fields on the Concrete configuration screen
  - Enter your account's email address into the Pay To Email field
  - Ensure you have selected the correct currency
  - Save the payment settings
  - Once the page has refreshed, you should see a 'Start Authorization code flow' link - click this link, login and authorize the usage. You will be redirected back to your site after approving. If successful, you should see 'saved' within the Refresh Code field.

Once the above steps are complete, the payment fields should appear within the checkout and live transactions can be made.
There are no separate testing and live credentails - if you are using a test account and swap to a live account, simple repeat the above steps and replace the entered configuration values.

If your site has a period of time longer than six months where no activity takes place (i.e. the checkout is never used), you may need to repeat the Authorization code flow step again to re-authorize your site against SumUP (refresh codes expire after 6 months).


**Development note:** due to missing functionality in the SumUp PHP SDK a file within the vendor directory has been manually altered.

The manual change is the addition of the following line:

```$payload['redirect_url'] =  (string)\Concrete\Core\Support\Facade\Url::to('/checkout/sumupcompleteorder');```

at line 91 of vendor/sumup/sumup-ecom-php-sdk/src/SumUp/Services/Checkouts.php

SumUp may fix/improve the SDK in the future to remove this need for a manual fix.

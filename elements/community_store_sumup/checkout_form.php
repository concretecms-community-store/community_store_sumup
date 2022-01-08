<?php
defined('C5_EXECUTE') or die("Access Denied.");
extract($vars);
?>

<script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>

<div id="sumup-card"></div>
<input type="hidden" value="" name="sumupCheckoutID" id="sumupCheckoutID" />
<script type="text/javascript">

        var sumupCard = false;

        window.addEventListener('load', function() {
            var e = document.getElementById('store-checkout-form-group-payment')
            var observer = new MutationObserver(function (event) {
                // get checkout ID here

                var req = new XMLHttpRequest();
                req.onreadystatechange = processResponse;
                req.open("GET", "<?= \Concrete\Core\Support\Facade\Url::to('/checkout/sumupcreatecheckout'); ?>");
                req.send();

                function processResponse() {
                    if (req.readyState !== 4) return; // State 4 is DONE
                    var data = JSON.parse(req.responseText);

                    document.getElementById('sumupCheckoutID').value = data.checkoutId;
                    document.getElementById('sumup-card').innerHTML = '';

                    sumupCard = SumUpCard.mount({
                        checkoutId: data.checkoutId,
                        onResponse: function(type, body) {
                            // Verify the checkout is processed correctly.
                            // Display success message to the user and destroy the SumUpCard object:

                            if (type === 'success') {
                                sumupCard.unmount();
                                document.getElementById('store-checkout-form-group-payment').submit();
                            }

                            if (type === 'invalid' || type === 'error') {
                                var button = document.querySelector("[data-payment-method-id='<?= $pmID; ?>'] .store-btn-complete-order");
                                button.disabled = false;
                                button.value = button.getAttribute('data-value');
                            }

                        },
                        showSubmitButton: false,
                        showFooter: false,
                        locale: '<?= $locale; ?>',
                        showZipCode: <?= $showZip ? 'true' : 'false'; ?>
                    });

                }


            })

            observer.observe(e, {
                attributes: true,
                attributeFilter: ['class'],
                childList: false,
                characterData: false
            })


            var button = document.querySelector("[data-payment-method-id='<?= $pmID; ?>'] .store-btn-complete-order");
            button.addEventListener('click', function(event) {
                event.preventDefault();

                button.disabled = true;
                button.setAttribute('data-value', button.value);
                button.value = '<?= t('Processing...'); ?>';

                sumupCard.submit();
            });

         });
</script>

<style>
    #sumup-card {
        margin-left: -20px;
        margin-right: -20px;
    }
</style>

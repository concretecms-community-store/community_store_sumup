<?php
defined('C5_EXECUTE') or die("Access Denied.");
extract($vars);
?>


<div id="sumup-card"></div>
<input type="hidden" value="" name="sumupCheckoutID" id="sumupCheckoutID"/>
<script type="text/javascript">


    window.addEventListener('load', function () {

        var button = document.querySelector("[data-payment-method-id='<?= $pmID; ?>'] .store-btn-complete-order");
        button.disabled = true;
        button.setAttribute('data-value', button.value);

        if (!sumupformobserver) {
            var e = document.getElementById('store-checkout-form-group-payment')
            sumupformobserver = new MutationObserver(function (event) {
                // get checkout ID here

                if (event[0].target.classList.contains('store-active-form-group')) {
                    var req = new XMLHttpRequest();
                    req.onreadystatechange = processResponse;
                    req.open("GET", "<?= \Concrete\Core\Support\Facade\Url::to('/checkout/sumupcreatecheckout'); ?>");
                    req.send();
                }

                function processResponse() {
                    if (req.readyState !== 4) return; // State 4 is DONE
                    var data = JSON.parse(req.responseText);

                    document.getElementById('sumupCheckoutID').value = data.checkoutId;
                    document.getElementById('sumup-card').innerHTML = '';

                    sumupCard = SumUpCard.mount({
                        checkoutId: data.checkoutId,
                        onResponse: function (type, body) {
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

                    var button = document.querySelector("[data-payment-method-id='<?= $pmID; ?>'] .store-btn-complete-order");
                    button.disabled = false;
                    button.value = button.getAttribute('data-value');
                }
            })

            sumupformobserver.observe(e, {
                attributes: true,
                attributeFilter: ['class'],
                childList: false,
                characterData: false
            })
        }


        button.addEventListener('click', function (event) {
            event.preventDefault();

            button.disabled = true;
            button.setAttribute('data-value', button.value);
            button.value = '<?= t('Processing...'); ?>';

            sumupCard.submit();
        });

    }, {once: true});

</script>

<style>
    #sumup-card {
        margin-bottom: 20px;
    }

    [data-sumup-id="widget__container"] {
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
    }
</style>

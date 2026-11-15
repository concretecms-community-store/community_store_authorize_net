<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>
<script src="<?= h(str_replace('/index.php/', '/', (string) \Concrete\Core\Support\Facade\Url::to('packages/community_store_authorize_net/js/card-input.js'))); ?>"></script>
<script type="text/javascript" src="https://js<?php echo ($mode == 'test' ? 'test' : '');?>.authorize.net/v1/Accept.js" charset="utf-8"></script>

<script>
    function an_getForm() {
        return document.getElementById('store-checkout-form-group-payment');
    }

    function an_getSubmitButton() {
        return an_getForm().querySelector('[data-payment-method-id="<?= (int) $pmID; ?>"] .store-btn-complete-order');
    }

    function an_responseHandler(response) {
        if (response.messages.resultCode === 'Error') {
            an_handleError(response);
        } else {
            an_handleSuccess(response.opaqueData);
        }
    }

    function an_handleSuccess(responseData) {
        var form = an_getForm();

        [['dataValue', responseData.dataValue], ['dataDesc', responseData.dataDescriptor]].forEach(function (field) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = field[0];
            input.value = field[1];
            form.appendChild(input);
        });

        // Resubmit the form to the server
        //
        // Only the payment nonce will be submitted to your server. The
        // browser ignores the original card inputs because they don't
        // have their 'name' attribute set.
        form.submit();
    }

    function an_handleError(response) {
        var form = an_getForm(),
            submitButton = an_getSubmitButton(),
            errorContainer = form.querySelector('.an-payment-errors');

        for (var i = 0; i < response.messages.message.length; i++) {
            var error = document.createElement('p');
            error.className = 'alert alert-danger';
            error.textContent = response.messages.message[i].text;
            errorContainer.appendChild(error);
        }

        errorContainer.style.display = '';

        // Re-enable the submit button
        submitButton.disabled = false;
        submitButton.value = <?= json_encode(t('Complete Order')); ?>;
    }

    // 1. Wait for the page (and payment methods) to load
    window.addEventListener('load', function () {
        var form = an_getForm();

        // the payment methods can be loaded more than once, only initialise once
        if (!form || form.classList.contains('an-payment-init')) {
            return;
        }
        form.classList.add('an-payment-init');

        var card = communityStoreCardInput;
        var numberField = document.getElementById('an-cc-number');
        var expiryField = document.getElementById('an-cc-exp');
        var cvcField = document.getElementById('an-cc-cvc');

        card.formatCardNumber(numberField);
        card.formatCardExpiry(expiryField);
        card.formatCardCVC(cvcField);

        // mark a field as valid or invalid
        var setValid = function (field, valid) {
            var group = field.closest('.form-group');
            if (group) {
                group.classList.toggle('has-error', !valid);
            }
            field.classList.toggle('is-invalid', !valid);
        };

        // clear errors as soon as a field becomes valid
        var clearWhenValid = function (field, isValid) {
            ['keyup', 'change'].forEach(function (eventName) {
                field.addEventListener(eventName, function () {
                    if (isValid()) {
                        setValid(field, true);
                    }
                });
            });
        };

        clearWhenValid(numberField, function () {
            return card.validateCardNumber(numberField.value);
        });
        clearWhenValid(expiryField, function () {
            var expiry = card.cardExpiryVal(expiryField.value);
            return card.validateCardExpiry(expiry.month, expiry.year);
        });
        clearWhenValid(cvcField, function () {
            return card.validateCardCVC(cvcField.value);
        });

        // 2. Add a submit handler
        form.addEventListener('submit', function (e) {
            var selected = form.querySelector('input[name="payment-method"]:checked');

            if (!selected || selected.getAttribute('data-payment-method-id') != <?= json_encode((string) $pmID); ?>) {
                return;
            }

            e.preventDefault();

            var submitButton = an_getSubmitButton(),
                errorContainer = form.querySelector('.an-payment-errors');

            var validcard = card.validateCardNumber(numberField.value);
            var expiry = card.cardExpiryVal(expiryField.value);
            var validexpiry = card.validateCardExpiry(expiry.month, expiry.year);
            var validcv = card.validateCardCVC(cvcField.value);

            setValid(numberField, validcard);
            setValid(expiryField, validexpiry);
            setValid(cvcField, validcv);

            if (!validcard) {
                numberField.focus();
                return;
            }
            if (!validexpiry) {
                expiryField.focus();
                return;
            }
            if (!validcv) {
                cvcField.focus();
                return;
            }

            // Clear previous errors
            errorContainer.innerHTML = '';
            errorContainer.style.display = 'none';

            // Disable the submit button to prevent multiple clicks
            submitButton.disabled = true;
            submitButton.value = <?= json_encode(t('Processing...')); ?>;

            var secureData = {
                cardData: {
                    cardNumber: numberField.value.replace(/\s/g, ''),
                    month: expiry.month > 9 ? '' + expiry.month : '0' + expiry.month,
                    year: String(expiry.year),
                    cardCode: cvcField.value.replace(/\s/g, '')
                },
                authData: {
                    apiLoginID: <?= json_encode((string) $loginID); ?>,
                    clientKey: <?= json_encode((string) $clientKey); ?>
                }
            };

            Accept.dispatchData(secureData, 'an_responseHandler');
        });
    });
</script>


<div class="panel panel-default credit-card-box">
    <div class="panel-body">
        <div style="display:none;" class="store-payment-errors an-payment-errors">
        </div>
        <div class="row  ">
            <div class="col-xs-12 mb-2">
                <div class="form-group">
                    <label for="cardNumber"><?= t('Card Number');?></label>
                    <div class="input-group">
                        <input
                            type="tel"
                            class="form-control"
                            id="an-cc-number"
                            placeholder="<?= t('Card Number');?>"
                            autocomplete="cc-number"
                            />
                        <span class="input-group-addon input-group-text"><i class="fa fa-credit-card"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row ">
            <div class="col-xs-7 col-md-7 mb-2">
                <div class="form-group">
                    <label for="cardExpiry"><?= t('Expiration Date');?></label>
                    <input
                        type="tel"
                        class="form-control"
                        id="an-cc-exp"
                        placeholder="MM / YY"
                        autocomplete="cc-exp"
                        />
                </div>
            </div>
            <div class="col-xs-5 col-md-5 mb-2">
                <div class="form-group">
                    <label for="cardCVC"><?= t('CV Code');?></label>
                    <input
                        type="tel"
                        class="form-control"
                        id="an-cc-cvc"
                        placeholder="<?= t('CVC');?>"
                        autocomplete="off"
                        />
                </div>
            </div>
        </div>
    </div>
</div>

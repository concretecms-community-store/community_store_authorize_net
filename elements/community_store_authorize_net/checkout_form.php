<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>
<script src="<?= str_replace('/index.php/', '/', URL::to('packages/community_store_authorize_net/js/jquery.payment.min.js'));?>"></script>
<script type="text/javascript" src="https://js<?php echo ($mode == 'test' ? 'test' : '');?>.authorize.net/v1/Accept.js" charset="utf-8"></script>

<script>

    function an_responseHandler(response) {
        if (response.messages.resultCode === 'Error') {
            an_handleError(response);
        } else {
            an_handleSuccess(response.opaqueData)
        }
    }

    function an_handleSuccess(responseData) {

        var form = $('#store-checkout-form-group-payment');

        $('<input>')
            .attr({type: 'hidden', name: 'dataValue'})
            .val(responseData.dataValue)
            .appendTo(form);

        $('<input>')
            .attr({type: 'hidden', name: 'dataDesc'})
            .val(responseData.dataDescriptor)
            .appendTo(form);

        // Resubmit the form to the server
        //
        // Only the card_token will be submitted to your server. The
        // browser ignores the original form inputs because they don't
        // have their 'name' attribute set.
        form.get(0).submit();
    }

    function an_handleError(response) {

        var form = $('#store-checkout-form-group-payment'),
            submitButton = form.find("[data-payment-method-id=\"<?= $pmID; ?>\"] .store-btn-complete-order"),
            errorContainer = form.find('.an-payment-errors');

        for (var i = 0; i < response.messages.message.length; i++) {
            $('<p class="alert alert-danger">').text(response.messages.message[i].text).appendTo(errorContainer);
        }

        errorContainer.show();

        // Re-enable the submit button
        submitButton.removeAttr('disabled');
        submitButton.val('<?= t('Complete Order'); ?>');
    }

    // 1. Wait for the page to load
    $(window).on('load', function() {

        $('#an-cc-number').payment('formatCardNumber');
        $('#an-cc-exp').payment('formatCardExpiry');
        $('#an-cc-cvc').payment('formatCardCVC');

        $('#an-cc-number').bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());

            if (validcard) {
                $(this).closest('.form-group').removeClass('has-error');
            }
        });

        $('#an-cc-exp').bind("keyup change", function(e) {
            var validcard = $.payment.validateCardNumber($(this).val());

            var expiry = $(this).payment('cardExpiryVal');
            var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

            if (validexpiry) {
                $(this).closest('.form-group').removeClass('has-error');
            }
        });

        $('#an-cc-cvc').bind("keyup change", function(e) {
            var validcv = $.payment.validateCardCVC($(this).val());

            if (validcv) {
                $('#an-cc-cvc').closest('.form-group').removeClass('has-error');
            }
        });


        var form = $('#store-checkout-form-group-payment'),
            submitButton = form.find("[data-payment-method-id=\"<?= $pmID; ?>\"] .store-btn-complete-order"),
            errorContainer = form.find('.an-payment-errors');

        // 3. Add a submit handler
        form.submit(function(e) {
            var currentpmid = $('input[name="payment-method"]:checked:first').data('payment-method-id');

            if (currentpmid == <?= $pmID; ?>) {
                e.preventDefault();

                var allvalid = true;

                var validcard = $.payment.validateCardNumber($('#an-cc-number').val());

                if (!validcard) {
                    $('#an-cc-number').closest('.form-group').addClass('has-error').find('input').addClass('is-invalid');
                    allvalid = false;
                } else {
                    $('#an-cc-number').closest('.form-group').removeClass('has-error').find('input').removeClass('is-invalid');
                }

                var expiry = $('#an-cc-exp').payment('cardExpiryVal');
                var validexpiry = $.payment.validateCardExpiry(expiry.month, expiry.year);

                if (!validexpiry) {
                    $('#an-cc-exp').closest('.form-group').addClass('has-error').find('input').addClass('is-invalid');
                    allvalid = false;
                } else {
                    $('#an-cc-exp').closest('.form-group').removeClass('has-error').find('input').removeClass('is-invalid');
                }

                var validcv = $.payment.validateCardCVC($('#an-cc-cvc').val());

                if (!validcv) {
                    $('#an-cc-cvc').closest('.form-group').addClass('has-error').find('input').addClass('is-invalid');
                    allvalid = false;
                } else {
                    $('#an-cc-cvc').closest('.form-group').removeClass('has-error').find('input').removeClass('is-invalid');
                }

                if (!allvalid) {
                    if (!validcard) {
                        $('#an-cc-number').focus()
                    } else {
                        if (!validexpiry) {
                            $('#an-cc-exp').focus()
                        } else {
                            if (!validcv) {
                                $('#an-cc-cvc').focus()
                            }
                        }
                    }

                    return false;
                }

                // Clear previous errors
                errorContainer.empty();
                errorContainer.hide();

                // Disable the submit button to prevent multiple clicks
                submitButton.attr({disabled: true});
                submitButton.val('<?= t('Processing...'); ?>');

                var secureData = {}, authData = {}, cardData = {};

                cardData.cardNumber = $('#an-cc-number').val().replace(/\s/g,'');
                cardData.month =  expiry.month > 9 ? "" + expiry.month : "0" + expiry.month;
                cardData.year = expiry.year;
                cardData.cardCode = $('#an-cc-cvc').val().replace(/\s/g,'');
                secureData.cardData = cardData;

                authData.apiLoginID = '<?php echo $loginID; ?>';
                authData.clientKey = '<?php echo $clientKey; ?>';
                secureData.authData = authData;

                Accept.dispatchData(secureData, 'an_responseHandler');
            }

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

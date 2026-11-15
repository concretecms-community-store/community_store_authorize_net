/**
 * Minimal card input formatting and validation (replaces jquery.payment).
 */
var communityStoreCardInput = (function () {
    function digits(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function isAmex(number) {
        return /^3[47]/.test(number);
    }

    function formatNumber(number) {
        number = digits(number).slice(0, isAmex(number) ? 15 : 19);

        if (isAmex(number)) {
            return [number.slice(0, 4), number.slice(4, 10), number.slice(10, 15)].filter(Boolean).join(' ');
        }

        return (number.match(/.{1,4}/g) || []).join(' ');
    }

    function formatExpiry(value) {
        var number = digits(value);

        // a first digit above 1 can only be a single digit month
        if (/^[2-9]/.test(number)) {
            number = '0' + number;
        }

        number = number.slice(0, 6);

        if (number.length > 2) {
            return number.slice(0, 2) + ' / ' + number.slice(2);
        }

        return number;
    }

    function formatCVC(value) {
        return digits(value).slice(0, 4);
    }

    // Reformat a field, keeping the caret after the same number of digits
    function attach(input, formatter) {
        input.addEventListener('input', function () {
            var caret = input.selectionStart;
            var digitsBeforeCaret = digits(input.value.slice(0, caret)).length;
            var formatted = formatter(input.value);

            input.value = formatted;

            if (document.activeElement === input && typeof input.setSelectionRange === 'function') {
                var position = 0;
                var count = 0;
                while (position < formatted.length && count < digitsBeforeCaret) {
                    if (/\d/.test(formatted[position])) {
                        count++;
                    }
                    position++;
                }
                input.setSelectionRange(position, position);
            }
        });
    }

    function validateCardNumber(value) {
        var number = digits(value);

        if (number.length < 12 || number.length > 19) {
            return false;
        }

        // Luhn checksum
        var sum = 0;
        var double = false;
        for (var i = number.length - 1; i >= 0; i--) {
            var digit = parseInt(number.charAt(i), 10);
            if (double) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            sum += digit;
            double = !double;
        }

        return sum % 10 === 0;
    }

    function cardExpiryVal(value) {
        var parts = String(value || '').split(/\s*\/\s*/);
        var month = parseInt(parts[0], 10);
        var year = parts[1] ? parts[1].trim() : '';

        if (year.length === 2 && /^\d+$/.test(year)) {
            year = String(new Date().getFullYear()).slice(0, 2) + year;
        }

        return {month: month, year: parseInt(year, 10)};
    }

    function validateCardExpiry(month, year) {
        if (!(month >= 1 && month <= 12) || !(year > 0)) {
            return false;
        }

        // valid until the end of the expiry month
        var expiry = new Date(year, month, 1);

        return expiry > new Date();
    }

    function validateCardCVC(value) {
        return /^\d{3,4}$/.test(String(value || '').trim());
    }

    return {
        formatCardNumber: function (input) {
            attach(input, formatNumber);
        },
        formatCardExpiry: function (input) {
            attach(input, formatExpiry);
        },
        formatCardCVC: function (input) {
            attach(input, formatCVC);
        },
        validateCardNumber: validateCardNumber,
        cardExpiryVal: cardExpiryVal,
        validateCardExpiry: validateCardExpiry,
        validateCardCVC: validateCardCVC
    };
})();

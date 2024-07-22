jQuery(document).ready(function ($) {
    $('#radom_pay_mainnet_tokens, #radom_pay_testnet_tokens').select2({
        placeholder: 'Select tokens',
        closeOnSelect: false,
        templateResult: formatState,
        templateSelection: formatState
    });

    function formatState(opt) {
        if (!opt.id) {
            return opt.text;
        }
        var optimage = $(opt.element).attr('data-image');
        if (!optimage) {
            return opt.text;
        } else {
            var $opt = $(
                '<span><input type="checkbox" class="select2-checkbox">' + opt.text + '</span>'
            );
            return $opt;
        }
    }
});

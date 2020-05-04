(function ($) {
    var $root = $('.i18n-snippet-comparator-table');
    if ($root.length === 0) {
        return;
    }
    // Swap filter languages button
    $root.find('#module_i18n_snippet_comparator_swap_language').click(function () {
        var $inputL1 = $(this).parent().find('[name=language1]'), $inputL2 = $(this).parent().find('[name=language2]');
        var tmp = $inputL1.val();
        $inputL1.val($inputL2.val()).change();
        $inputL2.val(tmp).change();
    });
    // Copy left/right buttons
    $root.find('.module-i18n-snippet-comparator-language-copy').click(function () {
        var $parent = $(this).parent().parent().parent();
        var $src = $parent.find('textarea[data-side=' + this.dataset.src + ']');
        var $dst = $parent.find('textarea[data-side=' + this.dataset.dst + ']');
        $dst.val($src.val()).trigger('change');
    });
    // Toggle rows (create/update)
    var $itemCheckboxLists = {
        language1: $root.find('.i18n-snippet-comparator-change[data-side=language1] input[type=checkbox]'),
        language2: $root.find('.i18n-snippet-comparator-change[data-side=language2] input[type=checkbox]'),
    };
    $root.find('.i18n-snippet-comparator-toggle').click(function () {
        $itemCheckboxLists[this.dataset.side].click();
    });
    // auto enable update on text change
    var $itemCheckboxes = {
        language1: {},
        language2: {},
    };
    $root.find('.i18n-snippet-comparator-change input[type=checkbox]').each(function () {
        $itemCheckboxes[this.parentNode.dataset.side][this.parentNode.dataset.row] = $(this);
    });
    $root.find('textarea').on('change input', function (event) {
        if (this.name.startsWith('data_update')) {
            $itemCheckboxes[this.dataset.side][this.dataset.row].prop('checked', this.value !== this.dataset.current);
        }
    }).each(function () {
        this.dataset.current = this.value;
    });
})(jQuery);

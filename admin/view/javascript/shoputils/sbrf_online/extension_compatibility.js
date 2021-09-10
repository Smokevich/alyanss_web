/*! Extension Compatibility Opencart 3.0.x => 3.1.x - v1.0 - 2018-04-26
* https://opencart.market
* Copyright (c) 2018 ShopUtils */

$(document).ready(function() {
    $('ul.breadcrumb').each(function() {
        $(this).replaceWith('<nav aria-label="breadcrumb"><ol class="breadcrumb">' + $('ul.breadcrumb').html() + '</ol></nav>');
    });

    $('ol.breadcrumb li').addClass('breadcrumb-item');

    replaceClass('pull-right', 'float-right', '.pull-right');
    replaceClass('fa', 'fas', 'i.fa');
    replaceClass('fa-pencil', 'fa-pencil-alt', 'i.fa-pencil');

    replaceClass('panel', 'card', '.panel');
    replaceClass('panel-heading', 'card-header', '.panel-heading');
    replaceClass('panel-body', 'card-body', '.panel-body');
    $('h3.panel-title i').unwrap();

    replaceClass('nav-item', 'nav-item', 'ul.nav-tabs li');
    replaceClass('nav-link', 'nav-link', 'ul.nav-tabs li a');
    $('ul.nav-tabs li:first.active').removeClass('active');
    $('ul.nav-tabs li:first a').addClass('active');

    replaceClass('row', 'row', '.form-group');
    replaceClass('control-label', 'col-form-label', 'label.control-label');
    replaceClass('well well-sm', 'form-control', '.well');

    $('span.help-block').each(function() {
        $(this).replaceWith('<small class="form-text text-muted">' + $('span.help-block').html() + '</small>');
    });

    $('label.col-form-label span[data-toggle=\'tooltip\']').parent().addClass('control-label');

    replaceClass('input-group-addon', 'input-group-text', 'span.input-group-addon');

    $('textarea[data-toggle=\'summernote\']').attr('data-toggle', 'ckeditor');
    if (!$().ckeditor) {
        $('head').append('<script type="text/javascript" src="view/javascript/ckeditor/ckeditor.js"></script>');
        $('head').append('<script type="text/javascript" src="view/javascript/ckeditor/adapters/jquery.js"></script>');
        $('textarea[data-toggle=\'ckeditor\']').each(function() {
            $(this).ckeditor();
        });
    }
});

function replaceClass(oldClass, newClass, selector) {
    $(selector).removeClass(oldClass).addClass(newClass);
}

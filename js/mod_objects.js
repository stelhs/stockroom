function submit_object(method)
{
    var form;
    form = $$("object_form");
    form.method.value = method;
    if (!form.object_name.value)
        alert("Name is empty");
    else
        form.submit();
}


function insert_attr(attr)
{
    var textarea = $$('object_attrs');
    var text = textarea.value;
    textarea.value = text + attr + ': ';
    textarea.focus();
}

function on_draw_location_path(location_id)
{
    $$("object_form").location_id.value = location_id;

    if (!first_load_page)
        $$('location_fullness').value = "";
    first_load_page = false;
    $$('location_input').value = location_id;
}

function on_draw_catalog_path(cat_id)
{
    $$("object_form").catalog_id.value = cat_id;
}

function display_catalogs_by_search(text)
{
    function result(data) {
        eval("var ret = " + data);
        if (ret['cat_id']) {
            on_select_searched_catalog(ret['cat_id']);
            hide_view('catalog_search');
            return;
        }
        $$('search_catalog_result').innerHTML=ret['content'];
    }
    mk_query('find_catalogs_by_string',
             {'mod': 'object',
              'text': text},
             result);
}

function on_select_searched_catalog(id)
{
    draw_catalog_path('catalog_path', id, true);
    $$('search_catalog_result').innerHTML="";
    hide_view('catalog_search');
}

function display_locations_by_search(text)
{
    function result(data) {
        eval("var ret = " + data);
        if (ret['loc_id']) {
            on_select_searched_location(ret['loc_id']);
            hide_view('location_search');
            return;
        }
        $$('search_location_result').innerHTML=ret['content'];
    }
    mk_query('find_locations_by_string',
             {'mod': 'object',
              'text': text},
             result);
}

function on_select_searched_location(id)
{
    draw_location_path('location_path', id, true);
    $$('search_location_result').innerHTML="";
    hide_view('location_search');
}

class QuantityDialog {
    constructor() {
        this.method = "";
        this.max_number = 0;
        this.div = $$('quantityDialogDiv');
        this.name = $$('nameQuanityDialog');
        this.inputName = 'inputQuanityDialog';
    }

    show(name, method, number, max_number, confirm_text, confirm_condition) {
        this.div.style.display='block';
        this.method = method;
        this.max_number = max_number;
        this.name.innerHTML = name + " :";
        $$(this.inputName).value = number;
        this.confirm_text = confirm_text;
        this.confirm_condition = confirm_condition;
    }

    hide() {
        this.div.style.display='none';
    }

    inc() {
        inc_input(this.inputName, 1, this.max_number)
    }

    dec() {
        dec_input(this.inputName, 1, 1);
    }

    submit() {
        var is_removed = true;
        var is_confirmed = this.confirm_text ? true : false

        if (this.confirm_condition)
            is_confirmed = this.confirm_condition();

        if (is_confirmed)
            is_removed = window.confirm(this.confirm_text);
        if (!is_removed)
            return false;

        return submit_object(this.method);
    }
}

function check_for_remove(number) {
    var entered_num = $$('inputQuanityDialog').value;
    return entered_num >= number;
}

class Photo_selector {
    constructor() {
        this.images = [];
        this.selected = NaN;
    }

    add(hash, selected) {
        this.images.push(hash)
        if (selected)
            this.selected = hash;
    }

    select(hash) {
        this.selected = hash;
        this.update();
    }

    update() {
        for (let h of this.images) {
            $$('img_label_selected_' + h).style.display = 'none';
        }
        $$('img_label_selected_' + this.selected).style.display = 'block';
        $$('label_photo').value = this.selected;
    }
}




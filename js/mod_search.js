
function on_draw_catalog_path(cat_id)
{
    $$("search_form").cat_id.value = cat_id;
}


function insert_attr(attr)
{
    var textarea = $$('object_attrs');
    var text = textarea.value;
    textarea.value = text + attr + ': ';
    textarea.focus();
}

function insert_char(char)
{
    var textarea = $$('object_attrs');
    var text = textarea.value;
    var cur = textarea.selectionStart;
    var new_text = text.substring(0, cur) + char + text.substring(cur);
    textarea.value = new_text;
    textarea.focus();
    textarea.selectionEnd = cur + 1;
}

function clear_attrs()
{
    var textarea = $$('object_attrs');
    textarea.value = "";
}

function clear_search_text()
{
    var textarea = $$('search_text');
    textarea.value = "";
}

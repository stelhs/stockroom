
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

function clear_attrs()
{
    var textarea = $$('object_attrs');
    textarea.value = "";
}
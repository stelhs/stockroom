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

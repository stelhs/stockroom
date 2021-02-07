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

function on_click_not_assigned_photo(photo_id)
{
    visible = switch_view("img_selected_" + photo_id);
    input = $$('input_selected_' + photo_id);
    input.value = visible;
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
load_tpl('location_path');
load_tpl('catalog_path');

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


display_sub_locations_mode = 0;
function display_sublocations()
{
    if (display_sub_locations_mode) {
        $$('list_sublocations').style.display = 'none';
        display_sub_locations_mode = 0;
    } else {
        $$('list_sublocations').style.display = 'inline-block';
        display_sub_locations_mode = 1;
    }
}


display_sub_catalog_mode = 0;
function display_sub_catalogs()
{
    if (display_sub_catalog_mode) {
        $$('list_sub_catalogs').style.display = 'none';
        display_sub_catalog_mode = 0;
    } else {
        $$('list_sub_catalogs').style.display = 'inline-block';
        display_sub_catalog_mode = 1;
    }
}

function draw_location_path(location_id)
{
    $$("object_form").location_id.value = location_id;
    display_sub_locations_mode = 0;

    function result(data) {
        eval("var list = " + data);
        var t = tpl_open('location_path');

        for (k in list) {
            var p = list[k];
            var last_id;

            if (k != list.length - 1)
                var block = 'location';
            else {
                var block = 'location_last';
                last_id = p['id'];
            }

            t.assign(block, {'name': p['name'],
                              'id': p['id']});
        }

        function result(data) {
            eval("var list = " + data);
            if (count(list)) {
                t.assign('select_button');
                for (k in list) {
                    var p = list[k];
                    t.assign('sub_location', {'name': p['name'],
                                          'id': p['id']});
                }
            }

            $$('location_path').innerHTML = t.result();
        }

        mk_query('get_sub_location',
                 {'mod': 'location',
                  'id': last_id}, result);
    }

    mk_query('location_path',
             {'mod': 'location',
              'id': location_id},
             result);
}

function draw_catalog_path(cat_id, display_sub)
{
    $$("object_form").catalog_id.value = cat_id;
    display_sub_catalog_mode = 0;

    function result(data) {
        eval("var list = " + data);
        var t = tpl_open('catalog_path');

        for (k in list) {
            var p = list[k];
            var last_id;

            if (k != list.length - 1)
                var block = 'catalog';
            else {
                var block = 'catalog_last';
                last_id = p['id'];
            }

            t.assign(block, {'name': p['name'],
                              'id': p['id']});
        }

        function result(data) {
            eval("var list = " + data);
            if (count(list)) {
                t.assign('select_button');
                for (k in list) {
                    var p = list[k];
                    t.assign('sub_catalog', {'name': p['name'],
                                             'id': p['id']});
                }
            }

            $$('catalog_path').innerHTML = t.result();
            if (display_sub)
                display_sub_catalogs();
        }

        mk_query('get_sub_catalog',
                 {'mod': 'catalog',
                  'id': last_id}, result);
    }
    mk_query('catalog_path',
             {'mod': 'catalog',
              'id': cat_id},
             result);
}


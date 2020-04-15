load_tpl('location_path');

function draw_location_path(location_id)
{
    $$("form_search_box").location_id.value = location_id;
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



function clear_parallelepiped()
{
    var form = $$('form_search_box');
    form.parallelepiped_size1.value = "";
    form.parallelepiped_size2.value = "";
    form.parallelepiped_size3.value = "";
}

function clear_cylinder()
{
    var form = $$('form_search_box');
    form.cylinder_diameter.value = "";
    form.cylinder_height.value = "";
}

function clear_ball()
{
    var form = $$('form_search_box');
    form.ball_diameter.value = "";
}

function calculate_volume()
{
    var form = $$('form_search_box');
    var volume_input = form.volume;
    var psize1 = form.parallelepiped_size1.value / 100;
    var psize2 = form.parallelepiped_size2.value / 100;
    var psize3 = form.parallelepiped_size3.value / 100;
    var cd = form.cylinder_diameter.value / 100;
    var ch = form.cylinder_height.value / 100;
    var bd = form.ball_diameter.value / 100;

    volume_input.value = "";

    if (psize1) {
        volume_input.value = Math.round(psize1 * psize2 * psize3 * 1000) / 1000;
        return;
    }

    if (cd) {
        var r = cd / 2;
        volume_input.value = Math.round(Math.PI * r * r * ch * 1000) / 1000;
        return;
    }

    if (bd) {
        var r = bd / 2;
        volume_input.value = Math.round(4 / 3 * Math.PI * r * r * r * 1000) / 1000;
        return;
    }
}



function on_draw_location_path(cat_id)
{
    $$("form_search_box").catalog_id.value = cat_id;
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


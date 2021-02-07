
/*
    Функция формирует асинхронный запрос на сервер.
    @param method - название запрашиваемой операции
    @param args - ассоц. массив аргументов (key => val)
    @param func - функция обработчик запроса, вызывается
                            при успешной обработке запроса
*/
function mk_query(method, args, func, async)
{
    args['method'] = method;
    return $.ajax({
          type: "POST",
          url: query_url,
          data: args,
          success: func,
          async: async
              }).responseText;
}

teamplates = [];
def_marks = [];
mk_query('load_def_marks', {},
         function(data) {eval('def_marks = ' + data)});

function load_tpl(name) {
    teamplates[name] = mk_query('load_tpl', {'name': name}, false, false);
}

function tpl_open(name)
{
    t = new strontium_tpl();
    t.open(teamplates[name], def_marks);
    return t;
}



function dec_input(id, step, min)
{
    var o = $$(id);
    if (!o.value.length)
        return;
    var v = parseInt(o.value);
    v -= step;
    if (v < min)
        v = min;
    o.value = v;
    o.style.color = 'red';
    setTimeout(function(){ o.style.color = 'lightgray'; o.style.border = "1px solid yellow" }, 300);
}

function inc_input(id, step, max)
{
    var o = $$(id);
    var val = o.value;
    if (!o.value.length)
        val = 0;
    var v = parseInt(val);
    v += step;
    if (max > 0 && v > max)
        v = max;
    o.value = v;
    o.style.color = 'red';
    setTimeout(function(){ o.style.color = 'lightgray'; o.style.border = "1px solid yellow" }, 300);
}

function switch_view(div_id)
{
    var div = $$(div_id);
    if (div.style.display != 'none') {
        div.style.display = 'none';
        return false;
    }

    div.style.display = 'inline-block';
    return true;
}

function hide_view(div_id)
{
    $$(div_id).style.display = 'none';
}

function show_view(div_id)
{
    $$(div_id).style.display = 'inline-block';
}


load_tpl('location_path');
load_tpl('catalog_path');

draw_location_path_actions = {};
function draw_location_path(div_id, location_id, highlight, action)
{
    if (action)
        draw_location_path_actions[div_id] = action;

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

            if (highlight)
                block += "_highted";

            t.assign(block, {'name': p['name'],
                              'id': p['id'],
                              'div_id': div_id});
        }

        function result(data) {
            eval("var list = " + data);
            if (count(list)) {
                t.assign('select_box', {'div_id': div_id});
                for (k in list) {
                    var p = list[k];
                    t.assign('sub_location', {'name': p['name'],
                                              'id': p['id'],
                                              'div_id': div_id});
                }
            }

            $$(div_id).innerHTML = t.result();
            if (action) {
                action(location_id);
                return;
            }

            if (count(list))
                show_view(div_id + '_list_sublocations');

            if (div_id in draw_location_path_actions)
                draw_location_path_actions[div_id](location_id);
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

draw_catalog_path_actions = {};
function draw_catalog_path(div_id, cat_id, highlight, action)
{
    if (action)
        draw_catalog_path_actions[div_id] = action;

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

            if (highlight)
                block += "_highted";

            t.assign(block, {'name': p['name'],
                              'id': p['id'],
                              'div_id': div_id});
        }

        function result(data) {
            eval("var list = " + data);
            if (count(list)) {
                t.assign('select_box', {'div_id': div_id});
                for (k in list) {
                    var p = list[k];
                    t.assign('sub_catalog', {'name': p['name'],
                                             'id': p['id'],
                                             'div_id': div_id});
                }
            }

            $$(div_id).innerHTML = t.result();
            if (action) {
                action(cat_id);
                return;
            }

            if (count(list))
                show_view(div_id + '_list_sub_catalogs');

            if (div_id in draw_catalog_path_actions)
                draw_catalog_path_actions[div_id](cat_id);
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


function light_input(input)
{
    input.style.border = "1px solid yellow";
}
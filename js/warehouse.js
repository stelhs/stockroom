
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


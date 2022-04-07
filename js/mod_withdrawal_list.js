function submit_object_form(method, obj_id)
{
    var form;
    form = $$("object_form_" + obj_id);
    form.method.value = method;
    form.submit();
}


window.onload = function (e) {
    var a = document.getElementById("billing:firstname");
    if (a !== null) {
        a.className = "validate-word " + a.className;
    }
    var b = document.getElementById("billing:lastname");
    if (b !== null) {
        b.className = "validate-word " + b.className;
    }
    var c = document.getElementById("shipping:firstname");
    if (c !== null) {
        c.className = "validate-word " + c.className;
    }
    var d = document.getElementById("shipping:lastname");
    if (d !== null) {
        d.className = "validate-word " + d.className;
    }
};


Validation.add('validate-word', 'Please use letters only (a-z or A-Z) in this field.', function (v) {
    return Validation.get('IsEmpty').test(v) || /^[a-zA-Z áéíóúàèìòùÁÉÍÓÚñÑüÜ]+$/.test(v)
})
var fire, offset;
function init(off) {
    fire = false;
    offset = off;
}

function load_products() {
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
            fire = false;
            if(xmlhttp.status == 200){
                document.getElementById('main').innerHTML += xmlhttp.responseText;
            }
        }
    };
    fire = true;
    xmlhttp.open('GET', 'loader.php?o=' + offset, true);
    xmlhttp.send();
    offset++;
}
window.addEventListener('scroll', function(event) {
    var doc = document.documentElement;
    var top = (window.pageYOffset || doc.scrollTop)  - (doc.clientTop || 0);
    var full = document.body.offsetHeight;
    if(full - top < 5000 && !fire) {
        load_products(offset);
    }

});
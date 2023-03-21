jQuery(document).ready(function($) {
    jQuery(document).on('click', 'a.spectator-item.dropdown-item', function (e) {
        e.preventDefault();
        var newCat = jQuery(this).data('prime_cat');
        setUrlParams('prime_cat',newCat);
    })


    function setUrlParams(key,newVal){
        // var paramExist = getUrlVars();
        var comUrl = window.location.href;
        var url = new URL(comUrl);
        var search_params = url.searchParams;
        search_params.set(key, newVal);
        url.search = search_params.toString();
        var new_url = url.toString();
        console.log(new_url);
        window.location.href = new_url;
    }
});
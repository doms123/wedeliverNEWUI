$(function() {
    $(".opportunityActivityReport").find("a").addClass("active");

    $(".tabularFilter").change(function() {
        var val = $(this).val();
        if(window.location.href.indexOf('filter') > -1) {
            var url = window.location.href.split('filter')[0];
            var redirectUrl = url+'filter='+val;
        }else {
            var redirectUrl = window.location.href+'?filter='+val;
        }

        window.location.href = redirectUrl;
    });

    getFilterValue();
    function getFilterValue() {
        var value = window.location.href.split('filter=')[1];
        $('.tabularFilter option[value="'+value+'"]').attr('selected','selected');
    }
});
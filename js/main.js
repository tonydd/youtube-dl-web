$(function () {

    $("html").niceScroll();
    $('div#panel-dl-playlist').niceScroll();
    $('div#console').niceScroll();

    $("#go").click(function () {
        fetchPlaylist($("#target").val());
    });

    $('button#download').click(function () {
        downloadPlaylist();
    });

    $('button#select').click(function () {
        select(true);
    });

    $('button#unselect').click(function () {
        select(false);
    });

});

function fetchPlaylist(url) {

    var source = new EventSource("app/App.php?action=fetch&target=" + url);
    var $res = $("#res");
    var $panel = $("div#panel-fetch-playlist");
    var $label = $('label#video-current-count');

    var $button = $('button#go');
    var $form = $button.parent();

    $button.remove();
    $('div#the-header').append(makeLoadingBlock('Récupération des informations en cours'));


    if (!$panel.is(':visible')) {
        $panel.fadeIn(500);
    }

    $('input#target').attr('disabled', true);

    source.onmessage = function (event) {

        var json_data = JSON.parse(event.data);

        switch (json_data.type) {
            case 'Title':
                $res.append( makePreviewLi(json_data.message, json_data.item) );
                $label.text(json_data.item);
                break;

            case 'Thumbnail':
                $res.children(':last-child').append(makePreviewThumbnail(json_data.message));

                $('body').animate({
                    scrollTop: $('body').get(0).scrollHeight
                }, 650);

                break;

            case 'END':
                source.close();
                $('div.loading').remove();
                $('button#download, button#select, button#unselect').show();
                return;
                break;
        }

    };


    source.addEventListener('open', function (e) {

    }, false);

    source.addEventListener('error', function (e) {
        console.log('error');
        console.log(e);

        if (e.readyState == EventSource.CLOSED) {
            // Connection was closed.
        }
    }, false);
};

function makePreviewLi(title, index) {
    var $li = $('<li/>')
        .addClass('list-group-item list-group-item-video');

    var $cb = $('<input type="checkbox" />')
        .addClass('dl-video')
        .attr('checked', true)
        .data('index', index);

    var $h = $('<h2/>')
        .text(title);

    $li
        .append($cb)
        .append($h);

    $cb.checkboxpicker();

    return $li;
}

function makePreviewThumbnail(src) {
    var $img = $('<img/>')
        .addClass('img-thumbnail')
        .addClass('thumb')
        .attr('src', src);

    return $img;
}

function makeLoadingBlock(text) {
    var $div = $('<div/>')
        .addClass('loading');


    var $img = $('<img/>')
        .attr('src', 'img/ajax-loader.gif');

    var $span = $('<span/>')
        .html("&nbsp;&nbsp;" + text);

    $div
        .append($img)
        .append($span);

    return $div;
}

function downloadPlaylist() {
    $('body').animate({
        scrollTop: 0
    }, 250);

    $('div.loading').show();
    $('button#download').hide();

    // -- Display corresponding elem
    $('div#panel-fetch-playlist').fadeOut(500, function () {
        $('div#panel-dl-playlist').fadeIn(500);
    });


    var $form = $('form#form-url');
    $('div#the-header-2').append(makeLoadingBlock('Let the magic happen !'));

    // -- Get videos to download
    var ids = [];
    $('.dl-video').each(function () {
        var $this = $(this);

        if ($this.is(':checked')) {

            ids.push( $this.data('index') );

        }
    });

    // -- Init server job
    var target = $('input#target').val();
    var source = new EventSource("app/App.php?action=dl&target="+target+"&targets=" + ids.join(','));

    source.onmessage = function (event) {

        var json_data = JSON.parse(event.data);
        if (json_data.type == 'END') {
            $('div.loading').remove();
            source.close();

            var filePath = json_data.message;

            $.fileDownload(document.location + filePath);

        }
        else {
            $('div#console').append('<p>' + json_data.message + '</p>');
            $('#console').animate({
                scrollTop: $('#console').get(0).scrollHeight
            }, 100);
        }


    };
}

function select(flag) {
    if (flag) {
        var target = '.btn-success';
    }
    else {
        var target = '.btn-default';
    }

    $('.btn-group ' + target).click();
}

// calendar
var tagged = {};

$('input.tagged').change(function (e) {
    var $this = $(this);
    if ($this.prop('checked')) {
        tagged[$this.attr('id')] = true;
    } else {
        delete tagged[$this.attr('id')];
    }
    filterEvents();
});

var filterEvents = function() {
    var clean = true;
    var days = $('ul.days li.day');
    var events = days.find('div.event');

    events.hide();
    for (var k in tagged) {
        clean = false;
        days.find('div.event.' + k).show();
    }
    if (clean) {
        events.show();
    }
}

var loader = $('#loader');

$(document).on('click', 'button.next', function (e) {
    if (++month > 12) {
        month = 1;
        ++year;
    }
    loadCalendar();
});

$(document).on('click', 'button.previous', function (e) {
    if (--month < 1) {
        month = 12;
        --year;
    }
    loadCalendar();
});

$(document).on('click', 'button.current', function (e) {
    var now = new Date();
    month = now.getMonth() + 1;
    loadCalendar();
});

var loadCalendar = function () {
    loader.show();
    $.get(
        document.location.pathname,
        {
            'month': month,
            'year': year
        },
        function (XHRResponse) {
            // reset the button and loader
            loader.hide();

            // display the results
            replaceCalendar(XHRResponse);
            // equalize the page
            $(document).foundation('equalizer', 'reflow');
            filterEvents();
        }
    );
};

function replaceCalendar(XHRResponse) {
    result = $(XHRResponse);
    $('#days').replaceWith(result.find('#days'));
    $('#date').replaceWith(result.find('#date'));
}
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
    var days = $('div.month');
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

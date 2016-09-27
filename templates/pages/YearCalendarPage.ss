<section role="content">
    <div class="row">
        <div class="medium-12 <% if not $Menu(2) %>small-centered<% end_if %> columns animated fadeInUpShort" data-id='3'>
            <div class="panel">
                <ul class="tabs text-center" data-tab>
                    <li class="tab-title active"><a class="tab" href="#panel1">Jaaroverzicht</a></li>
                    <li class="tab-title"><a class="tab" href="#panel2">Vakanties</a></li>
                </ul>
                <div class="tabs-content">
                    <div class="content active" id="panel1">
                        $Content
                        <div class="filter hide-for-small">
                            <form id="filter" novalidate="novalidate" class="" method="get">
                                <div class="row">
                                    <div class="large-12 columns text-center">
                                        <label></label>
                                        <% loop $Tags %>
                                            <span class="checkbox $URLSegment" style="$CssColorString">
                                                <input name="$Title" class="tagged $URLSegment" id="$URLSegment" type="checkbox">
                                                <label for="$URLSegment">$Title</label>
                                            </span>
                                        <% end_loop %>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div id="calendar-wrap">
                            <header id="date">
                                <h1>$YearCalendarItems.Month.Text $YearCalendarItems.Year</h1>
                                <button class="previous tiny left">$YearCalendarItems.PreviousMonth</button>
                                <button class="current tiny"><%t Site.CurrentMonth 'This month' %></button>
                                <button class="next tiny right">$YearCalendarItems.NextMonth</button>
                            </header>
                            <div id="calendar">
                                <ul class="weekdays">
                                    <li>Zondag</li>
                                    <li>Maandag</li>
                                    <li>Dinsdag</li>
                                    <li>Woensdag</li>
                                    <li>Donderdag</li>
                                    <li>Vrijdag</li>
                                    <li>Zaterdag</li>
                                </ul>

                                <div id="days">
                                    <% loop $YearCalendarItems.Days %>
                                        <% if $First %>
                                        <ul class="days" data-equalizer data-options="equalize_on_stack: true">
                                        <% end_if %>
                                        <li class="day<% if not Events.Count() %> empty<% end_if %><% if not $Date %> no-date<% end_if %>" data-equalizer-watch>
                                            <% if $Date %>
                                                <div class="date">$Date</div>
                                            <% end_if %>
                                            <% if $Events.Count() %>
                                                <% loop $Events %>
                                                    <div class="event<% loop $Tags %> $URLSegment<% end_loop %>" style="$Tags.first().CssColorString();">
                                                        <div class="event-desc">
                                                            $Title
                                                        </div>
                                                    </div>
                                                <% end_loop %>
                                            <% end_if %>
                                        </li>
                                        <% if $MultipleOf(7) %>
                                        </ul>
                                        <ul class="days" data-equalizer data-options="equalize_on_stack: true">
                                        <% end_if %>
                                        <% if $Last %>
                                        </ul>
                                        <% end_if %>
                                    <% end_loop %>
                                    <% if $YearCalendarItems.Events == 0 %>
                                        <div class="event show-for-small-only">
                                            <div class="event-desc">
                                                <%t Site.NoEvents 'No events this month' %>
                                            </div>
                                        </div>
                                    <% end_if %>

                                </div>

                                <div id="loader" style="display: none;">
                                    <div id="loadLoaderG">
                                        <div id="blockG_1" class="loader_blockG"></div>
                                        <div id="blockG_2" class="loader_blockG"></div>
                                        <div id="blockG_3" class="loader_blockG"></div>
                                    </div>
                                </div>

                            </div><!-- /. calendar -->
                        </div><!-- /. wrap -->
                    </div>
                    <div class="content" id="panel2">
                        $Content

                        <% if $Holidays.Count() %>
                            <table align="center">
                                <% loop $Holidays %>
                                    <tr>
                                        <td>
                                            $Title
                                        </td>
                                        <td>
                                            $DisplayFromDate('d F Y')
                                            <% if not $WholeDay %>$DisplayFromDate('H:i')<% end_if %>
                                        </td>
                                        <td>
                                            $DisplayToDate('d F Y')
                                            <% if not $WholeDay %>$DisplayToDate('H:i')<% end_if %>
                                        </td>
                                    </tr>
                                <% end_loop %>
                            </table>
                        <% end_if %>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<% include ContentFooter %>

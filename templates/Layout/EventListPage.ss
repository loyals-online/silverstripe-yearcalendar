<% include HeaderImage %>

<section class="content">
    <div class="row">
        <div class="<% if not $Menu(2) %>medium-11 large-10 small-centered<% else %>medium-12<% end_if %> columns">
            <div class="panel big">
                <div class="row">

                    <% include Submenu %>
                    <div class="<% if not $Menu(2) %>medium-12<% else %>medium-8 large-9 small-centered<% end_if %> columns">
                        $Breadcrumbs

                        <h1>$Title</h1>
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

                        <% loop $YearCalendarList %>
                            <h2>$Year</h2>
                            <% loop $Months %>
                                <h3>$Month</h3>
                                <% loop $Events %>
                                    <div class="list-item">
                                        <h2>
                                            <a href="/">
                                                $Title
                                            </a>
                                        </h2>
                                        <div class="listing-meta">

                                            $DisplayFromDate - $DisplayToDate

                                        </div>

                                        <div class="content-wrap">
                                            $Introduction
                                        </div>

                                        <a class="button" href="$Link">
                                            Read more
                                        </a>

                                    </div>
                                <% end_loop %>
                            <% end_loop %>
                        <% end_loop %>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
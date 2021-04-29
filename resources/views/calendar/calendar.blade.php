<div class="container">
    <div class="card-body" style="background: white">
        <div id="calendar"></div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var calendar = $('#calendar').fullCalendar({
            editable: true,
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            eventSources: [
                {
                    url: "http://127.0.0.1:8000/api/category/getAppointment",
                    method: 'POST',
                    data: {
                        startDate: "2021-01-27",
                        marketId: "12"
                    },
                    success: function(res) {
                        let reData = [];
                        res.forEach(employeeArray => {
                            employeeArray.forEach(employee => {
                                let newArray = {};
                                newArray.title = employee.userInfo.name;
                                newArray.start = employee.active_day+'T'+employee.start_date;
                                newArray.end = employee.active_day+'T'+employee.end_date;
                                newArray.textColor = 'white';
                                if (employee.is_active === 1) {
                                    newArray.color = 'green';
                                } else {
                                    newArray.color = 'gray';
                                }
                                reData.push(newArray);
                            });
                        });
                        return reData;
                    },
                    failure: function() {
                        alert('there was an error while fetching events!');
                    },
                    color: 'blue',   // a non-ajax option
                    textColor: 'black' // a non-ajax option
                }
            ],
            timeFormat: 'hh:mm ',
            selectable: true,
            selectHelper: true,
            select: function(start, end, allDay) {
                var title = prompt('Event Title:');

                if (title) {
                    var start = $.fullCalendar.formatDate(start, 'Y-MM-DD HH:mm:ss');

                    var end = $.fullCalendar.formatDate(end, 'Y-MM-DD HH:mm:ss');

                    $.ajax({
                        url: "http://127.0.0.1:8000/api/category/getAppointment",
                        type: "POST",
                        data: {
                            startDate: "2021-04-27",
                            marketId: "12"
                        },
                        success: function(data) {
                            console.log(data);
                            calendar.fullCalendar('refetchEvents');
                            alert("Event Created Successfully");
                        }
                    })
                }
            },
            editable: true,
            eventResize: function(event, delta) {
                var start = $.fullCalendar.formatDate(event.start, 'Y-MM-DD HH:mm:ss');
                var end = $.fullCalendar.formatDate(event.end, 'Y-MM-DD HH:mm:ss');
                var title = event.title;
                var id = event.id;
                $.ajax({
                    url: "/full-calender/action",
                    type: "POST",
                    data: {
                        title: title,
                        start: start,
                        end: end,
                        id: id,
                        type: 'update'
                    },
                    success: function(response) {
                        calendar.fullCalendar('refetchEvents');
                        alert("Event Updated Successfully");
                    }
                })
            },
            eventDrop: function(event, delta) {
                var start = $.fullCalendar.formatDate(event.start, 'Y-MM-DD HH:mm:ss');
                var end = $.fullCalendar.formatDate(event.end, 'Y-MM-DD HH:mm:ss');
                var title = event.title;
                var id = event.id;
                $.ajax({
                    url: "/full-calender/action",
                    type: "POST",
                    data: {
                        title: title,
                        start: start,
                        end: end,
                        id: id,
                        type: 'update'
                    },
                    success: function(response) {
                        calendar.fullCalendar('refetchEvents');
                        alert("Event Updated Successfully");
                    }
                })
            },

            eventClick: function(event) {
                if (confirm("Are you sure you want to remove it?")) {
                    var id = event.id;
                    $.ajax({
                        url: "/full-calender/action",
                        type: "POST",
                        data: {
                            id: id,
                            type: "delete"
                        },
                        success: function(response) {
                            calendar.fullCalendar('refetchEvents');
                            alert("Event Deleted Successfully");
                        }
                    })
                }
            }
        });

    });
</script>